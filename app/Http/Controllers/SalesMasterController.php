<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\SalesMaster;
use App\Models\ZatcaRegistration;
use App\Models\AccountLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseService;
use Illuminate\Support\Facades\Crypt;

class SalesMasterController extends Controller
{
    /**
     * List SalesMaster
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $sales = DB::select(
            'SELECT * FROM public.get_sales_master_list(?,?,?,?,?,?,?,?)',
            [
                $request->fromDate ?? null,
                $request->toDate ?? null,
                $limit,
                $page,
                (int)$request->branchId,
                $request->vouchercode ?? null,
                $request->customername ?? null,
                $request->taxType ?? null
            ]
        );

        // ✅ Get total count from first row
        $total = count($sales) > 0 ? (int)$sales[0]->total_count : 0;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Retrieved Successfully',
            'data' => $sales,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0
            ]
        ]);
    }


    public function taxshow($id)
    {
        $encryptedDb = Crypt::encryptString('finacsoftware_arr');
        DatabaseService::switchToDatabase($encryptedDb);

        $sales = DB::select('SELECT * FROM public.get_sales_master_data_byid(?)', [$id]);

        // Check if found
        if (empty($sales)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Not Found',
            ], 404);
        }

        $sales = (array) $sales[0];

        $salesDetails = DB::select('SELECT * FROM public.get_sales_details1_by_sales_master_id(?)', [$sales['salesMasterId']]);

        $salesDetails = collect($salesDetails)->map(function ($detail) {
            $detail = (array) $detail;

            // Fetch product with unit conversions and sales prices
            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {
                    // Find matching sales price for this unit
                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'units'       => $units,
            ]);
        });

        $sales['salesDetails'] = $salesDetails;

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Sales Retrieved Successfully',
            'data'    => $sales,
        ], 200);
    }

    /**
     * Get SalesMaster by id
     */
        public function show($id)
        {
            $sales = DB::select('SELECT * FROM public.get_sales_master_data_byid(?)', [$id]);

            // Check if found
            if (empty($sales)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Sales Not Found',
                ], 404);
            }

            $sales = (array) $sales[0];

            $salesDetails = DB::select('SELECT * FROM public.get_sales_details1_by_sales_master_id(?)', [$sales['salesMasterId']]);

            $salesDetails = collect($salesDetails)->map(function ($detail) {
                $detail = (array) $detail;

                // Fetch product with unit conversions and sales prices
                $product = \App\Models\Product::with([
                    'unitConversions.unit',
                    'salesPrices.unit',
                    'salesPrices.pricingLevel'
                ])->find($detail['productCode']);

                $units = [];

                if ($product && $product->unitConversions) {
                    foreach ($product->unitConversions as $conversion) {
                        // Find matching sales price for this unit
                        $salesPrice = $product->salesPrices
                            ->firstWhere('unitId', $conversion->unitId);

                        $units[] = [
                            'barcode'          => $conversion->barcode ?? null,
                            'unitid'           => $conversion->unitId,
                            'unitname'         => $conversion->unit->UnitName ?? null,
                            'unitconversionid' => $conversion->unitConversionId,
                            'conversionrate'   => $conversion->ConversionFactor,
                            'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                        ];
                    }
                }

                return array_merge($detail, [
                    'productcode' => $detail['productCode'],
                    'productname' => $product->productName ?? null,
                    'units'       => $units,
                ]);
            });

            $sales['salesDetails'] = $salesDetails;

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Sales Retrieved Successfully',
                'data'    => $sales,
            ], 200);
        }


    /**
     * Create Sales
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Check for duplicate invoiceNo
            // if ($request->has('invoiceNo') && SalesMaster::whereRaw('LOWER("invoiceNo") = ?', [strtolower($request->invoiceNo)])->exists()) {
            //     return response()->json([
            //         'status' => 409,
            //         'error' => true,
            //         'message' => 'Invoice Number Already Exists !',
            //     ], 409);
            // }
            if (
                $request->has('invoiceNo') &&
                SalesMaster::whereRaw('LOWER("invoiceNo") = ?', [strtolower($request->invoiceNo)])
                ->where('taxType', $request->taxType)
                ->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Invoice Number Already Exists !',
                ], 409);
            }
            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $vouchertype_generation = $request->taxType === 'NA' ? 'Sales Invoice-No Tax' : 'Sales Invoice';
            $codeData = voucherNoGeneration($vouchertype_generation, $branchId, $yearId, $request->taxType);
            // Insert sale
            // $sales = new SalesMaster($request->only([
            //     'date','billTime','ledgerId','pricingLevelId','employeeId',
            //     'salesAccount','currencyConversionId','taxType','GodownId','costCentreId','BatchId','customerName',
            //     'CustomerAddress','CustomerPhone','customerVATNo','RefNo','refDate','orderRefNo','orderRefDate',
            //     'creditPeriod','dueDate','deliveryDate','exchangeRate','exchangeDate','deliveryNoteMasterId',
            //     'orderMasterId','quotationMasterId','proformaMasterId','AgainstNo','lrNo','transportCompany',
            //     'vehicleNo','narration','taxableAmt','subTotal','totalTax','additionalCost','OtherChargeRemark',
            //     'otherChargeLedgerId','othercharge','billDiscount','roundOff','totalAmount','AdjustmentAmount',
            //     'AdvancePerc','RetentionLedgerId','RetentionType','RetentionDueDate','RetentionPercentage',
            //     'RetentionAmount','paymentMode','CashLedgerId','CashRefNo','CashAmount','BankLedgerId','BankRefNo',
            //     'BankAmount','BillBalanceAmount','CarMake'  ,'CarModel','Kilometer','NextService','VehicleInTime',
            //     'VehicleOutTime','preinvoiceHash','currentinvoiceHash','UUID','Zatcastatus','logdata','qr_link',
            //     'postedStatus','postedBy','postedDate','billDiscOnProductStatus','AddCostonProductStatus',
            //     'branchId', 'CreatedUser'
            // ]));

            // $sales->salesMasterId       = $codeData['table_pk'];
            // $sales->suffixPrefixId      = $codeData['suffixPrefixId'];
            // $sales->voucherNo           = $codeData['voucherNo'];
            // $sales->invoiceNo           = $codeData['voucherCode'];
            // $sales->CreatedDate 		= now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            // $sales->save();

            $total = $request->BankAmount + $request->CashAmount + $request->BillBalanceAmount;

            if ($total != $request->totalAmount) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => 'Amount mismatch Detected! Please verify the amounts.totalAmount: ' . $request->totalAmount . ', sum of BankAmount, CashAmount and BillBalanceAmount: ' . $total,
                ], 500);
            }
            $createdDate = getBranchNow($branchId);
            $masterParams = [
                $codeData['table_pk'],
                $codeData['voucherNo'],
                $codeData['voucherCode'],
                $codeData['suffixPrefixId'],
                $request->date,
                $request->billTime,
                $request->ledgerId,
                $request->pricingLevelId,
                $request->employeeId,
                $request->input('executiveId', null),
                $request->salesAccount,
                $request->currencyConversionId,
                $request->taxType,
                $request->GodownId,
                $request->costCentreId,
                $request->BatchId,
                $request->customerName,
                $request->CustomerAddress,
                $request->CustomerPhone,
                $request->customerVATNo,
                $request->RefNo,
                $request->refDate,
                $request->orderRefNo,
                $request->orderRefDate,
                $request->creditPeriod,
                $request->dueDate,
                $request->deliveryDate,
                $request->exchangeRate,
                $request->exchangeDate,
                is_array($request->deliveryNoteMasterId)
                    ? json_encode($request->deliveryNoteMasterId)
                    : $request->deliveryNoteMasterId,

                is_array($request->orderMasterId)
                    ? json_encode($request->orderMasterId)
                    : $request->orderMasterId,

                is_array($request->quotationMasterId)
                    ? json_encode($request->quotationMasterId)
                    : $request->quotationMasterId,

                is_array($request->proformaMasterId)
                    ? json_encode($request->proformaMasterId)
                    : $request->proformaMasterId,
                $request->AgainstNo,
                $request->lrNo,
                $request->transportCompany,
                $request->vehicleNo,
                $request->narration,
                $request->taxableAmt,
                $request->subTotal,
                $request->totalTax,
                $request->additionalCost,
                $request->OtherChargeRemark,
                $request->otherChargeLedgerId,
                $request->othercharge,
                $request->billDiscount,
                $request->input('billDiscountWithTax', 0), // NEW
                $request->roundOff,
                $request->totalAmount,
                $request->AdjustmentAmount,
                $request->AdvancePerc,
                $request->RetentionLedgerId,
                $request->RetentionType,
                $request->RetentionDueDate,
                $request->RetentionPercentage,
                $request->RetentionAmount,
                $request->paymentMode,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->CarMake,
                $request->CarModel,
                $request->Kilometer,
                $request->NextService,
                $request->VehicleInTime,
                $request->VehicleOutTime,
                $request->preinvoiceHash,
                $request->currentinvoiceHash,
                $request->UUID,
                $request->Zatcastatus,
                $request->logdata,
                $request->qr_link,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->billDiscOnProductStatus,
                $request->AddCostonProductStatus,
                $request->branchId,
                $request->CreatedUser,
                $request->formType,
                $createdDate

            ];

            $sql = 'SELECT * FROM public.insert_sales_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ')';

            $result = DB::select($sql, $masterParams);

            $inserted = $result[0] ?? null;

            if (!$inserted || $inserted->out_salesmasterid === null) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => $inserted->message ?? 'Failed to insert sales master',
                ], 500);
            }

            // Get Eloquent instance
            $sales = SalesMaster::find($inserted->out_salesmasterid);

            // Insert into tbl_SalesDetails1
            if ($request->has('salesDetails')) {
                foreach ($request->salesDetails as $sd) {
                    $branchNow = getBranchNow($sd['branchId']);

                    $detailParams = [
                        $sales->salesMasterId,
                        $sd['deliveryNoteDetails1Id'] ?? null,
                        $sd['orderDetails1Id'] ?? null,
                        $sd['quotationDetailsId'] ?? null,
                        $sd['proformaDetails1Id'] ?? null,
                        $sd['SlNo'],
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],
                        $sd['inclusiveRate'] ?? 0,
                        $sd['lineDiscountWithTax'] ?? 0,
                        $sd['unitId'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['PurchaseRate'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['amount'],
                        $sd['productDescription'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['otherchargeonproduct'],
                        $sd['salesManId'] ?? null,
                        $sd['GodownId'] ?? null,
                        $sd['RackId'] ?? null,
                        $sd['branchId'],
                        $request->CreatedUser,
                        null,
                        $sd['Weight'] ?? null,
                        $sd['Origin'] ?? null,

                        $branchNow
                    ];

                    $sql = 'SELECT * FROM public.insert_sales_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    $sales_details = DB::select($sql, $detailParams);
                    // $inserted = $sales_details[0];
                    // $salesDetails1Id = $inserted->out_salesdetails1id;


                    // add StockPosting
                    $outwardQty = ($sd['qty'] + $sd['freeQty']) * $sd['ConversionFactor'];
                    $voucherQty = $sd['qty'] + $sd['freeQty'];
                    $createdDate = getBranchNow($sd['branchId']);

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $sales->date,           // date
                        'Sales Invoice',        // voucherType
                        $sales->salesMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        0,                      // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $voucherQty,            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        0,                      // inwardQty
                        $outwardQty,            // outwardQty
                        $sd['PurchaseRate'],    // rate
                        $sd['baseUnitid'],      // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $sd['branchId'],        // branchId
                        $request->CreatedUser,  // CreatedUser
                        $createdDate
                    ]);

                    // Retrieve inserted stockPostingId
                    $insertedStock = $result[0];
                    $stockPostingId = $insertedStock->out_stockpostingid;
                }
            }

            // Insert Ledger Postings
            if ($sales->totalAmount > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->ledgerId,          // ledgerId
                    $sales->totalAmount,    // debit
                    0,                        // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,  // CreatedUser
                    $createdDate

                ]);

                $ledgerInserted = $result[0];
                $ledgerPostingId = $ledgerInserted->out_ledgerpostingid;
            }

            // Compute Sales Credit Amount with roundOff logic
            if ($sales->totalAmount > 0) {
                $roundOff = $sales->roundOff ?? 0;

                if ($roundOff < 0) {
                    $salesCredit = $sales->totalAmount - $sales->totalTax - $sales->othercharge - abs($roundOff);
                } else {
                    $salesCredit = $sales->totalAmount - $sales->totalTax - $sales->othercharge + $roundOff;
                }
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->salesAccount,   // ledgerId
                    0,                        // debit
                    $salesCredit,            // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // roundOff Posting
            if ($sales->roundOff != 0) {
                $roundOff = $sales->roundOff ?? 0;

                if ($roundOff > 0) {
                    $credit = 0;
                    $debit = $roundOff;
                } else {
                    $debit = 0;
                    $credit = abs($roundOff);
                }
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // VAT Posting
            if ($sales->totalTax > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    0,                        // debit
                    $sales->totalTax,       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Other Charge Posting
            if ($sales->othercharge > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->otherChargeLedgerId,  // ledgerId
                    0,                        // debit
                    $sales->othercharge,       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Sales Retention Posting
            if ($sales->RetentionAmount > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->ledgerId,  // ledgerId
                    0,                        // debit
                    $sales->RetentionAmount,       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Retention',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->RetentionLedgerId,  // ledgerId
                    $sales->RetentionAmount,    // debit
                    0,                       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Retention',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Sales Receipt Posting
            if ($sales->CashAmount > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->CashLedgerId,  // ledgerId
                    $sales->CashAmount,    // debit
                    0,                       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Receipt',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Sales Receipt Posting
            if ($sales->BankAmount > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->BankLedgerId,  // ledgerId
                    $sales->BankAmount,    // debit
                    0,                       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Receipt',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Sales Receipt Posting
            if ($sales->BankAmount > 0 || $sales->CashAmount > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->ledgerId,  // ledgerId
                    0,    // debit
                    ($sales->BankAmount + $sales->CashAmount),  // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Receipt',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Partybalance Posting
            if ($sales->BillBalanceAmount > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,                     // date
                    $sales->ledgerId,                 // ledgerId
                    'Sales Invoice',                  // voucherType
                    $sales->salesMasterId,             // voucherNo
                    'NA',                           // againstVoucherType
                    'NA',                           // againstvoucherNo
                    'New',                           // referenceType
                    $sales->BillBalanceAmount,         // debit
                    0,                              // credit
                    $sales->creditPeriod,             // creditPeriod
                    $sales->currencyConversionId,   // currencyConversionId
                    $sales->invoiceNo,                 // invoiceNo
                    $sales->RefNo,                  // referenceNo
                    $sales->totalAmount,             // BillAmount
                    $sales->date,                    // invoiceDate
                    $sales->costCentreId,              // costCentreId
                    $sales->exchangeRate,              // exchangeRate
                    $sales->exchangeDate,              // exchangeDate
                    $sales->branchId,                  // branchId
                    $request->CreatedUser,              // CreatedUser
                    $createdDate
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Sales Created Successfully',
                'data' => [
                    'salesMasterId'   => $sales->salesMasterId,
                    'invoicenumber'   => $sales->invoiceNo,
                    'qr_link'         => $sales->qr_link,
                    'prevInvoiceHash' => optional(
                        ZatcaRegistration::where('branchId', $sales->branchId)->first()
                    )->PrevInvoiceHash,
                    'payload' => [
                        'salesMaster' => [
                            ...$sales->toArray(),
                            'salesDetails' => $request->salesDetails ?? []
                        ]
                    ]
                ]
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update SalesMaster
     */
    public function update(Request $request, $id)
    {
        $sales = SalesMaster::find($id);
        $oldAmount = $sales->totalAmount;
        if (!$sales) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Not Found',
            ], 404);
        }

        DB::beginTransaction();
        try {

            $total = $request->BankAmount + $request->CashAmount + $request->BillBalanceAmount;

            if ($total != $request->totalAmount) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => 'Amount mismatch Detected! Please verify the amounts.totalAmount: ' . $request->totalAmount . ', sum of BankAmount, CashAmount and BillBalanceAmount: ' . $total,

                ], 500);
            }

            // Check for duplicate invoiceNo
            if (
                $request->has('invoiceNo') && SalesMaster::whereRaw('LOWER("invoiceNo") = ?', [strtolower($request->invoiceNo)])
                ->where('salesMasterId', '!=', $id)
                ->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Invoice Number Already Exists !',
                ], 409);
            }

            // Update sales fields
            // $sales->fill($request->only([
            // 	'date','billTime','ledgerId','pricingLevelId','employeeId','salesAccount','currencyConversionId',
            // 	'taxType','GodownId','costCentreId','BatchId','customerName','CustomerAddress','CustomerPhone',
            // 	'customerVATNo','RefNo','refDate','orderRefNo','orderRefDate','creditPeriod','dueDate','deliveryDate',
            // 	'exchangeRate','exchangeDate','deliveryNoteMasterId','orderMasterId','quotationMasterId',
            // 	'proformaMasterId','AgainstNo','lrNo','transportCompany','vehicleNo','narration','taxableAmt',
            // 	'subTotal','totalTax','additionalCost','OtherChargeRemark','otherChargeLedgerId','othercharge',
            // 	'billDiscount','roundOff','totalAmount','AdjustmentAmount','AdvancePerc','RetentionLedgerId',
            // 	'RetentionType','RetentionDueDate','RetentionPercentage','RetentionAmount','paymentMode','CashLedgerId',
            // 	'CashRefNo','CashAmount','BankLedgerId','BankRefNo','BankAmount','BillBalanceAmount','CarMake',
            // 	'CarModel','Kilometer','NextService','VehicleInTime','VehicleOutTime','preinvoiceHash',
            // 	'currentinvoiceHash','UUID','Zatcastatus','logdata','qr_link','postedStatus','postedBy','postedDate',
            // 	'billDiscOnProductStatus','AddCostonProductStatus','branchId','ModifiedUser'
            // ]));

            // $sales->ModifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            // if ($request->has('ModifiedUser')) {
            // 	$sales->ModifiedUser = $request->ModifiedUser;
            // }

            // $sales->save();

            $updateParams = [
                $id,
                $request->date,
                $request->billTime,
                $request->ledgerId,
                $request->pricingLevelId,
                $request->employeeId,
                $request->salesAccount,
                $request->currencyConversionId,
                $request->taxType,
                $request->GodownId,
                $request->costCentreId,
                $request->BatchId,
                $request->customerName,
                $request->CustomerAddress,
                $request->CustomerPhone,
                $request->customerVATNo,
                $request->RefNo,
                $request->refDate,
                $request->orderRefNo,
                $request->orderRefDate,
                $request->creditPeriod,
                $request->dueDate,
                $request->deliveryDate,
                $request->exchangeRate,
                $request->exchangeDate,
                is_array($request->deliveryNoteMasterId)
                    ? json_encode($request->deliveryNoteMasterId)
                    : $request->deliveryNoteMasterId,

                is_array($request->orderMasterId)
                    ? json_encode($request->orderMasterId)
                    : $request->orderMasterId,

                is_array($request->quotationMasterId)
                    ? json_encode($request->quotationMasterId)
                    : $request->quotationMasterId,

                is_array($request->proformaMasterId)
                    ? json_encode($request->proformaMasterId)
                    : $request->proformaMasterId,
                $request->AgainstNo,
                $request->lrNo,
                $request->transportCompany,
                $request->vehicleNo,
                $request->narration,
                $request->taxableAmt,
                $request->subTotal,
                $request->totalTax,
                $request->additionalCost,
                $request->OtherChargeRemark,
                $request->otherChargeLedgerId,
                $request->othercharge,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0),   // ✅ ADDED (new param)

                $request->roundOff,
                $request->totalAmount,
                $request->AdjustmentAmount,
                $request->AdvancePerc,
                $request->RetentionLedgerId,
                $request->RetentionType,
                $request->RetentionDueDate,
                $request->RetentionPercentage,
                $request->RetentionAmount,
                $request->paymentMode,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->CarMake,
                $request->CarModel,
                $request->Kilometer,
                $request->NextService,
                $request->VehicleInTime,
                $request->VehicleOutTime,
                $request->preinvoiceHash,
                $request->currentinvoiceHash,
                $request->UUID,
                $request->Zatcastatus,
                $request->logdata,
                $request->qr_link,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->billDiscOnProductStatus,
                $request->AddCostonProductStatus,
                $request->branchId,
                $request->ModifiedUser,
                $request->formType
            ];

            $sql = 'SELECT * FROM public.update_sales_master(' .
                implode(',', array_fill(0, count($updateParams), '?')) .
                ')';

            $result = DB::select($sql, $updateParams);

            /**
             * Update salesDetails
             */
            DB::select('SELECT delete_sales_details1(?)', [$sales->salesMasterId]);

            if ($request->has('salesDetails')) {
                foreach ($request->salesDetails as $sd) {
                    $detailParams = [
                        $sales->salesMasterId,
                        $sd['deliveryNoteDetails1Id'],
                        $sd['orderDetails1Id'],
                        $sd['quotationDetailsId'],
                        $sd['proformaDetails1Id'],
                        $sd['SlNo'],
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],

                        $sd['inclusiveRate'] ?? 0,           // ✅ ADDED
                        $sd['lineDiscountWithTax'] ?? 0,     // ✅ ADDED

                        $sd['unitId'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['PurchaseRate'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['amount'],
                        $sd['productDescription'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['otherchargeonproduct'],
                        $sd['salesManId'],
                        $sd['GodownId'],
                        $sd['RackId'],
                        $sd['branchId'],
                        $request->ModifiedUser,
                        $request->ModifiedUser
                    ];

                    $sql = 'SELECT * FROM public.insert_sales_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    $sales_details = DB::select($sql, $detailParams);

                    DB::select('SELECT delete_stock_posting(?, ?)', ['Sales Invoice', $sales->salesMasterId]);
                    // add StockPosting
                    $outwardQty = ($sd['qty'] + $sd['freeQty']) * $sd['ConversionFactor'];
                    $voucherQty = $sd['qty'] + $sd['freeQty'];

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $sales->date,           // date
                        'Sales Invoice',        // voucherType
                        $sales->salesMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        0,                      // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $voucherQty,            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        0,                      // inwardQty
                        $outwardQty,            // outwardQty
                        $sd['PurchaseRate'],    // rate
                        $sd['baseUnitid'],   // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $sd['branchId'],        // branchId
                        $request->ModifiedUser,  // CreatedUser
                    ]);
                }
            }


            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Sales Invoice', // voucherType
                $sales->salesMasterId, // voucherNo
                $request->branchId   // branchId
            ]);
            // Insert Ledger Postings
            if ($sales->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->ledgerId,          // ledgerId
                    $sales->totalAmount,    // debit
                    0,                        // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);

                $ledgerInserted = $result[0];
                $ledgerPostingId = $ledgerInserted->out_ledgerpostingid;
            }

            // Compute Sales Credit Amount with roundOff logic
            if ($sales->totalAmount > 0) {
                $roundOff = $sales->roundOff ?? 0;

                if ($roundOff < 0) {
                    $salesCredit = $sales->totalAmount - $sales->totalTax - $sales->othercharge - abs($roundOff);
                } else {
                    $salesCredit = $sales->totalAmount - $sales->totalTax - $sales->othercharge + $roundOff;
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->salesAccount,   // ledgerId
                    0,                        // debit
                    $salesCredit,            // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // roundOff Posting
            if ($sales->roundOff != 0) {
                $roundOff = $sales->roundOff ?? 0;

                if ($roundOff > 0) {
                    $credit = 0;
                    $debit = $roundOff;
                } else {
                    $debit = 0;
                    $credit = abs($roundOff);
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // VAT Posting
            if ($sales->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    0,                        // debit
                    $sales->totalTax,       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Other Charge Posting
            if ($sales->othercharge > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->otherChargeLedgerId,  // ledgerId
                    0,                        // debit
                    $sales->othercharge,       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales',                   // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Sales Retention Posting
            if ($sales->RetentionAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->ledgerId,  // ledgerId
                    0,                        // debit
                    $sales->RetentionAmount,       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Retention',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->RetentionLedgerId,  // ledgerId
                    $sales->RetentionAmount,    // debit
                    0,                       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Retention',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Sales Receipt Posting
            if ($sales->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->CashLedgerId,  // ledgerId
                    $sales->CashAmount,    // debit
                    0,                       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Receipt',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Sales Receipt Posting
            if ($sales->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->BankLedgerId,  // ledgerId
                    $sales->BankAmount,    // debit
                    0,                       // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Receipt',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Sales Receipt Posting
            if ($sales->BankAmount > 0 || $sales->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Invoice',         // voucherType
                    $sales->salesMasterId,  // voucherNo
                    $sales->ledgerId,  // ledgerId
                    0,    // debit
                    ($sales->BankAmount + $sales->CashAmount),  // credit
                    $sales->invoiceNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Receipt',       // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Partybalance Posting

            if ($sales->BillBalanceAmount > 0) {
                DB::select('SELECT delete_partybalance(?, ?)', [
                    'Sales Invoice', // voucherType
                    $sales->salesMasterId // voucherNo
                ]);
                $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,                     // date
                    $sales->ledgerId,                 // ledgerId
                    'Sales Invoice',                  // voucherType
                    $sales->salesMasterId,             // voucherNo
                    'NA',                           // againstVoucherType
                    'NA',                           // againstvoucherNo
                    'New',                           // referenceType
                    $sales->BillBalanceAmount,         // debit
                    0,                              // credit
                    $sales->creditPeriod,             // creditPeriod
                    $sales->currencyConversionId,   // currencyConversionId
                    $sales->invoiceNo,                 // invoiceNo
                    $sales->RefNo,                  // referenceNo
                    $sales->totalAmount,             // BillAmount
                    $sales->date,                    // invoiceDate
                    $sales->costCentreId,              // costCentreId
                    $sales->exchangeRate,              // exchangeRate
                    $sales->exchangeDate,              // exchangeDate
                    $sales->branchId,                  // branchId
                    $request->ModifiedUser              // CreatedUser
                ]);
            }
            ActivityHelper::logActivity(
                $request->employeeId ?? null,
                'Sales Invoice Update',
                'Sales Invoice',
                'UPDATED',
                $sales->branchId,
                $request->ModifiedUser ?? 'System',
                $sales->salesMasterId,
                $sales->invoiceNo ?? null,
                $sales->date ?? null,
                $oldAmount,
                $request->totalAmount,
                $request->systemId ?? null
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Updated Successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Sales
     */
    public function destroy($masterid, $branchid)
    {
        DB::beginTransaction();


        try {
            $sales = DB::table('tbl_SalesMaster')
                ->where('salesMasterId', $masterid)
                ->first();
            if (!$sales) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Sales Invoice not found'
                ], 404);
            }
            $oldAmount = $sales->totalAmount ?? 0;
            DB::select('SELECT delete_stock_posting(?, ?)', ['Sales Invoice', $masterid]);

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Sales Invoice', // voucherType
                $masterid, // voucherNo
                $branchid   // branchId
            ]);

            DB::select('SELECT delete_partybalance(?, ?)', [
                'Sales Invoice', // voucherType
                $masterid // voucherNo
            ]);

            DB::select('SELECT delete_sales_details1(?)', [$masterid]);

            DB::statement('SELECT public.delete_sales_master_byid(?)', [$masterid]);


            ActivityHelper::logActivity(
                $sales->employeeId ?? null,
                'Sales Invoice Delete',
                'Sales Invoice',
                'DELETED',
                $sales->branchId ?? null,
                $sales->CreatedUser ?? 'System',
                $sales->salesMasterId,
                $sales->voucherNo ?? null,
                $sales->date ?? null,
                $oldAmount,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Deleted Successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function SalesInvoiceQuotationList(Request $request)
    {
        $ledgerId = $request->p_ledgerid;
        $branchId = $request->p_branchid;
        $p_salesmasterid = $request->p_salesmasterid;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_sales_invoice_sales_quotation_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $p_salesmasterid
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Invoice Quotation List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function SalesInvoiceQuotationDetails(Request $request)
    {
        $quotationIds = (array) $request->p_quotation_master_id;

        if (empty($quotationIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Quotation ID required',
            ], 400);
        }

        // ✅ Fetch masters
        $masters = DB::select(
            'SELECT * FROM public.get_delivery_note_sales_quotation_master_byid(?, ?)',
            [
                json_encode($quotationIds),
                $request->p_branchid
            ]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Quotation Not Found',
            ], 404);
        }

        // ✅ Use first as base
        $baseMaster = (array) $masters[0];

        // ✅ Merge master fields properly
        $voucherNos = [];
        $quotationNos = [];
        $quotationMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'] ?? null;
            $quotationNos[] = $m['quotationNo'] ?? null;
            $quotationMasterIds[] = $m['quotationMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', array_filter($voucherNos));
        $baseMaster['quotationNo'] = implode(',', array_filter($quotationNos));
        $baseMaster['quotationMasterId'] = implode(',', $quotationMasterIds);

        $branchId = $baseMaster['branchId'] ?? $request->p_branchid;

        $allDetails = [];

        foreach ($quotationIds as $quotationId) {

            $details = DB::select(
                'SELECT * FROM public.get_sales_invoice_sales_quotation_details_with_remaining(?, ?, ?)',
                [
                    $quotationId,
                    $request->p_salesmasterid,
                    $branchId
                ]
            );

            $allDetails = array_merge($allDetails, $details);
        }

        // ✅ Enhance product info
        $allDetails = collect($allDetails)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel',
                'brand'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {

                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', (int) $detail['unitId'])
                ->where('branchId', (int) $branchId)
                ->value('amount');

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => optional($product->brand)->brandName,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $baseMaster['salesDetails'] = $allDetails->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Invoice Quotation Details Retrieved Successfully',
            'data' => $baseMaster
        ], 200);
    }

    public function SalesInvoiceProformaList(Request $request)
    {
        $ledgerId = $request->p_ledgerid;
        $branchId = $request->p_branchid;
        $p_salesmasterid = $request->p_salesmasterid;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_sales_invoice_proforma_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $p_salesmasterid
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Invoice Proforma List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function SalesInvoiceProformaDetails(Request $request)
    {
        $proformaIds = (array) $request->p_proformamasterid;

        if (empty($proformaIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Proforma ID required',
            ], 400);
        }

        // ✅ Fetch masters
        $masters = DB::select(
            'SELECT * FROM public.get_delivery_note_proforma_master_byid(?, ?)',
            [
                json_encode($proformaIds),
                $request->p_branchid
            ]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Proforma Not Found',
            ], 404);
        }

        // ✅ Use first as base structure
        $baseMaster = (array) $masters[0];

        // ✅ Merge master values
        $voucherNos = [];
        $proformaNos = [];
        $proformaMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'] ?? null;
            $proformaNos[] = $m['proformaNo'] ?? null;
            $proformaMasterIds[] = $m['proformaMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', array_filter($voucherNos));
        $baseMaster['proformaNo'] = implode(',', array_filter($proformaNos));
        $baseMaster['proformaMasterId'] = implode(',', $proformaMasterIds);

        $branchId = $baseMaster['branchId'] ?? $request->p_branchid;

        $allDetails = [];

        foreach ($proformaIds as $proformaId) {

            $details = DB::select(
                'SELECT * FROM public.get_sales_invoice_proforma_details_with_remaining(?, ?, ?)',
                [
                    $proformaId,
                    $request->p_salesmasterid,
                    $branchId
                ]
            );

            $allDetails = array_merge($allDetails, $details);
        }

        $allDetails = collect($allDetails)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel',
                'brand'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {

                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', (int) $detail['unitId'])
                ->where('branchId', (int) $branchId)
                ->value('amount');

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => optional($product->brand)->brandName,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $baseMaster['salesDetails'] = $allDetails->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Order Proforma Details Retrieved Successfully',
            'data' => $baseMaster,
        ], 200);
    }


    public function SalesInvoiceSalesOrderList(Request $request)
    {
        $ledgerId = $request->p_ledgerid;
        $branchId = $request->p_branchid;
        $p_salesmasterid = $request->p_salesmasterid;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_sales_invoice_sales_order_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $p_salesmasterid
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Invoice Sales Order List Retrieved Successfully',
            'data' => $results,
        ]);
    }


    public function SalesInvoiceSalesOrderDetails(Request $request)
    {
        $orderIds = (array) $request->p_ordermasterid;

        if (empty($orderIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Sales Order ID required',
            ], 400);
        }

        // ✅ Fetch masters
        $masters = DB::select(
            'SELECT * FROM public.get_delivery_note_sales_order_master_byid(?)',
            [json_encode($orderIds)]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Order Not Found',
            ], 404);
        }

        // ✅ Use first as base structure
        $baseMaster = (array) $masters[0];

        // ✅ Merge master fields
        $voucherNos = [];
        $orderNos = [];
        $orderMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'] ?? null;
            $orderNos[] = $m['orderNo'] ?? null;
            $orderMasterIds[] = $m['orderMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', array_filter($voucherNos));
        $baseMaster['orderNo'] = implode(',', array_filter($orderNos));
        $baseMaster['orderMasterId'] = implode(',', $orderMasterIds);

        $branchId = $baseMaster['branchId'] ?? $request->p_branchid;

        $allDetails = [];

        foreach ($orderIds as $orderId) {

            $details = DB::select(
                'SELECT * FROM public.get_sales_invoice_sales_order_details_with_remaining(?, ?, ?)',
                [
                    $orderId,
                    $request->p_salesmasterid,
                    $branchId,
                ]
            );

            $allDetails = array_merge($allDetails, $details);
        }

        $allDetails = collect($allDetails)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel',
                'brand'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {

                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', (int) $detail['unitId'])
                ->where('branchId', (int) $branchId)
                ->value('amount');

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => optional($product->brand)->brandName,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $baseMaster['salesDetails'] = $allDetails->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Invoice Sales Order Details Retrieved Successfully',
            'data' => $baseMaster,
        ], 200);
    }

    public function SalesInvoiceDeliveryNoteList(Request $request)
    {
        $ledgerId = $request->p_ledgerid;
        $branchId = $request->p_branchid;
        $p_salesmasterid = $request->p_salesmasterid;
        $p_isaccountsposting = $request->p_isaccountsposting;


        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_sales_invoice_delivery_note_list(?, ?, ?, ?)', [
            $ledgerId,
            $branchId,
            $p_salesmasterid,
            $p_isaccountsposting
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Invoice Delivery Note List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function SalesInvoiceDeliveryNoteDetails(Request $request)
    {
        $deliveryIds = (array) $request->p_deliverynotemasterid;

        if (empty($deliveryIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Delivery Note ID required',
            ], 400);
        }

        // ✅ Fetch masters
        $masters = DB::select(
            'SELECT * FROM public.get_sales_invoice_delivery_note_master_byid(?)',
            [json_encode($deliveryIds)]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Delivery Note Not Found',
            ], 404);
        }

        // ✅ Use first as base
        $baseMaster = (array) $masters[0];

        // ✅ Merge master values
        $voucherNos = [];
        $deliveryNos = [];
        $deliveryMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'] ?? null;
            $deliveryNos[] = $m['deliveryNoteNo'] ?? null;
            $deliveryMasterIds[] = $m['deliveryNoteMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', array_filter($voucherNos));
        $baseMaster['deliveryNoteNo'] = implode(',', array_filter($deliveryNos));
        $baseMaster['deliveryNoteMasterId'] = implode(',', $deliveryMasterIds);

        $branchId = $baseMaster['branchId'] ?? null;

        $allDetails = [];

        foreach ($deliveryIds as $deliveryId) {

            $details = DB::select(
                'SELECT * FROM public.get_sales_invoice_delivery_note_details_with_remaining(?, ?)',
                [
                    $deliveryId,
                    $request->p_salesmasterid
                ]
            );

            $allDetails = array_merge($allDetails, $details);
        }

        $allDetails = collect($allDetails)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel',
                'brand'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {

                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', (int) $detail['unitId'])
                ->where('branchId', (int) $branchId)
                ->value('amount');

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => optional($product->brand)->brandName,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $baseMaster['salesDetails'] = $allDetails->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Invoice Delivery Note Details Retrieved Successfully',
            'data' => $baseMaster,
        ], 200);
    }

    public function PriceListRepot(Request $request)
    {
        try {
            $groupId    = $request->group_id ?? 0;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."price_list_report_fill"(?, ?, ?)',
                [
                    $groupId,
                    $branchId,
                    $currencyId
                ]
            );

            if (empty($data)) {
                return response()->json([
                    'status'  => 200,
                    'error'   => false,
                    'message' => 'No data found',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Price List Report Fetched Successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function StockValueRepot(Request $request)
    {
        try {

            $branchId = $request->filled('branchId') ? (int) $request->branchId : null;

            $data = DB::select(
                "SELECT * FROM public.stock_value_report(
                ?::varchar,
                ?::varchar,
                ?::varchar,
                ?::varchar,
                ?::varchar,
                ?::varchar,
                ?::varchar,
                ?::integer,
                ?::boolean,
                ?::boolean
            )",
                [
                    $request->groupId     ?? 'All',
                    $request->productCode ?? 'All',
                    $request->brandId     ?? 'All',
                    $request->taxId       ?? 'All',
                    $request->taxType     ?? 'All',
                    $request->godownId    ?? 'All',
                    $request->rackId      ?? 'All',
                    $branchId,
                    (bool) ($request->zeroStock ?? false),
                    (bool) ($request->negativeStock ?? false),
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Stock Value Report Fetched Successfully',
                'data'    => $data
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    public function ProductStockReport(Request $request)
    {
        try {

            $groupId     = $request->groupid     ?? 'All';
            $productCode = $request->productcode ?? 'All';
            $brandId     = $request->brandid     ?? 'All';
            $taxId       = $request->taxid       ?? 'All';
            $taxType     = $request->taxtype     ?? 'All';

            $godownId    = $request->godownid    ?? 'All';
            $rackId      = $request->rackid      ?? 'All';
            $criteria    = $request->criteria    ?? 'All';
            $startText   = $request->starttext   ?? '';

            $branchId    = (int) ($request->branchid ?? 1);
            $isActive    = (bool)($request->isactive ?? true);
            $isDateWise  = (bool)($request->isdatewise ?? false);

            $fromDate    = $request->fromdate;
            $toDate      = $request->todate;

            $data = DB::select(
                'SELECT * FROM product_stock_report(
                ?,?,?,?,?, ?,?,?,?, ?,?,?,?, ?,?
            )',
                [
                    $groupId,
                    $productCode,
                    $brandId,
                    $taxId,
                    $taxType,

                    $godownId,
                    $rackId,
                    $criteria,
                    $startText,

                    $branchId,
                    $isActive,
                    $isDateWise,
                    $fromDate,
                    $toDate,
                    false
                ]
            );
            if (empty($data)) {
                return response()->json([
                    'status'  => 204,
                    'error'   => false,
                    'message' => 'No product stock data found for the given filters',
                    'data'    => []
                ], 200);
            }
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product Stock Report Fetched Successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function ProductMovement(Request $request)
    {
        try {
            $data = DB::select(
                "SELECT * FROM public.product_movement_lookup(?, ?, ?, ?, ?, ?)",
                [
                    $request->product_code,
                    $request->branch_id,
                    $request->currency_id,
                    $request->history_type,
                    $request->ledger_id,
                    $request->voucher_type
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'History Fetched Successfully',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function SalesOrderReport(Request $request)
    {
        try {
            $data = DB::select(
                "SELECT * FROM public.sales_order_report_fill_detailed(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)",
                [
                    $request->fromDate,
                    $request->toDate,
                    false,
                    null,
                    $request->dueOn,
                    $request->ledgerId,
                    $request->branchId,
                    $request->salesManId,
                    $request->currencyId,
                    $request->quotationMasterId,
                    $request->areaId,
                    null,
                    null,
                    $request->userId,
                    $request->status,
                    $request->partyName ?? 'All',
                    $request->mode ?? 'Detailed'
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Sales Order Report Fetched Successfully',
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function salesreport(Request $request)
    {
        DB::beginTransaction();

        try {


            $result = DB::select(
                'SELECT * from public.sales_invoice_report_fill_detailed(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->optional,
                    $request->ledgerId,
                    $request->branchId ?? null,
                    $request->currencyId,
                    $request->condition,
                    $request->dueOn,
                    $request->package,
                    $request->isAccountsOnly,
                    $request->costCentreId,
                    $request->partyName,
                    $request->isAccountsPosting,
                    $request->batchId,
                    $request->paymentMode,
                    $request->taxType


                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'sales invoice report retrieved Successfully',
                'data'   => $result
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function salesDayreport(Request $request)
    {
        DB::beginTransaction();

        try {


            $result = DB::select(
                'SELECT * from public.sales_invoice_day_report_fill(?,?,?,?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->branchId,
                    $request->isAccountsPosting,

                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'sales day report retrieved Successfully',
                'data'   => $result
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function areaWiseSalesReport(Request $request)
    {
        try {

            $fromDate = $request->fromDate;
            $toDate = $request->toDate;
            $employeeId = $request->employeeId ?? 0;
            $mode = $request->mode ?? null;
            $branchId = $request->branchId;
            $optional = $request->optional ?? false;
            $currencyId = $request->currencyId ?? 0;
            $routeId = $request->routeId ?? 0;
            $marketId = $request->marketId ?? 0;
            $areaId = $request->areaId ?? 0;
            $brandId = $request->brandId ?? 0;
            $decimalPoint = $request->decimalPoint ?? 2;
            $isAccountsPosting = $request->isAccountsPosting ?? false;


            $data = DB::select('
            SELECT * FROM public."areawise_sales_report_fill"(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ', [
                $fromDate,
                $toDate,
                $employeeId,
                $mode,
                $branchId,
                $optional,
                $currencyId,
                $decimalPoint,
                $routeId,
                $marketId,
                $areaId,
                $brandId,
                $isAccountsPosting
            ]);


            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'AreaWise Sales Report Fetched Successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function productVsSalesman(Request $request)
    {
        try {

            $fromDate = $request->fromDate;
            $toDate = $request->toDate;
            $groupId = $request->groupId;          // let null pass through
            $productCode = $request->productCode;  // let null pass through
            $branchId = $request->branchId;        // let null pass through
            $optional = $request->optional ?? false;
            $currencyId = $request->currencyId ?? 'All';
            $mode = $request->mode ?? 'Detailed';
            $brandId = $request->brandId;          // let null pass through
            $isAccountsPosting = $request->isAccountsPosting ?? true;

            $data = DB::select('
            SELECT * FROM public."product_vs_sales_man_report_fill"(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ', [
                $fromDate,
                $toDate,
                $groupId,
                $productCode,
                $branchId,
                $optional,
                $currencyId,
                $mode,
                $brandId,
                $isAccountsPosting
            ]);

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Product Vs Salesman Report Fetched Successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function ProductwiseSalemanFill(Request $request)
    {
        try {

            $fromDate = $request->fromDate;
            $toDate = $request->toDate;
            $salesmanId = $request->salesmanId ?? 0;

            $branchId = $request->branchId;

            $mode = $request->mode ?? 'Detailed';



            $data = DB::select('
            SELECT * FROM public."productwise_saleman_fill"(
                ?, ?, ?, ?, ?
            )
        ', [
                $fromDate,
                $toDate,
                $salesmanId,
                $branchId,


                $mode,

            ]);


            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Productwise Saleman Report Fetched Successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function SalesManwiseBillsPending(Request $request)
    {
        try {

            $fromDate = $request->fromDate;
            $toDate   = $request->toDate;

            $optional = (bool) ($request->optional ?? false);
            $branchId = (int)  $request->branchId;

            $status    = $request->status ?? 'ledger';
            $ledgerId  = (int) ($request->ledgerId ?? 0);
            $currencyId = (int) ($request->currencyId ?? 1);
            $ledgerBy  = (int) ($request->ledgerBy ?? 0);
            $employeeId = (int) ($request->employeeId ?? 0);
            $isAccountsPosting = (bool) ($request->isAccountsPosting ?? false);

            $data = DB::select(
                'SELECT * FROM public."sales_manwise_bills_receivable_report_fill"(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $fromDate,
                    $toDate,
                    $optional,
                    $branchId,
                    $status,
                    $ledgerId,
                    $currencyId,
                    $ledgerBy,
                    $employeeId,
                    $isAccountsPosting
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Salesman Bills Receivable Report Fetched Successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function UpdateZatcaFields(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->VoucherType === 'Sales Invoice') {

                DB::statement(
                    'SELECT update_salesmaster_zatca_details(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $request->salesMasterId,
                        $request->PreInvoiceHash,
                        $request->CurrentInvoiceHash,
                        $request->UUID,
                        $request->ZatcaStatus,
                        $request->LogData,
                        $request->Qrlink,
                        $request->UploadedInvoice,
                        $request->ZatcaSignedXml
                    ]
                );
            } elseif ($request->VoucherType === 'Sales Return') {
                DB::statement(
                    'SELECT update_salesreturn_zatca_details(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $request->ReturnMasterId,
                        $request->PreInvoiceHash,
                        $request->CurrentInvoiceHash,
                        $request->UUID,
                        $request->ZatcaStatus,
                        $request->LogData,
                        $request->Qrlink,
                        $request->UploadedInvoice,
                        $request->ZatcaSignedXml
                    ]
                );
            } else {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'Invalid VoucherType'
                ]);
            }

            ZatcaRegistration::where('branchId', $request->branchId)
                ->update([
                    'PrevInvoiceHash' => $request->CurrentInvoiceHash
                ]);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'ZATCA details updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // public function allSalesApis(Request $request)
    // {
    //     try {

    //         $voucherType = $request->input('voucherType', 'Sales Invoice');
    //         $branchId = $request->input('branchId', null);
    //         $yearId = $request->input('yearId', null);

    //         if (!$branchId) {
    //             return response()->json([
    //                 'status' => 400,
    //                 'error'  => true,
    //                 'message' => 'Branch ID is required',
    //                 'data'   => []
    //             ], 400);
    //         }

    //         $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

    //         $employees = DB::select('SELECT * FROM public.get_all_employee()');
    //         $costcentre = DB::select('SELECT * FROM public.get_all_costcentres()');
    //         $pricinglevel = DB::select('SELECT * FROM public.get_all_pricing_level()');

    //         $ledgerTypes = $request->input('ledgerTypes', []);

    //         if (empty($ledgerTypes)) {
    //             return response()->json([
    //                 'status' => 400,
    //                 'error' => true,
    //                 'message' => 'Not Found ledger Type',
    //                 'data' => []
    //             ], 400);
    //         }

    //         $customersupplierLedgers = AccountLedger::whereIn('ledgerType', $ledgerTypes)
    //             ->whereRaw(
    //                 'EXISTS (
    //                 SELECT 1
    //                 FROM jsonb_array_elements("branchId") AS b
    //                 WHERE (b->>\'branchId\')::int = ?
    //             )',
    //                 [$branchId]
    //             )
    //             ->orderBy('ledgerName', 'asc')
    //             ->get();

    //         $godowns = DB::select('SELECT * FROM public.get_all_godown(?)', [$branchId]);

    //         // $groupIds = $request->input('group_ids', []);
    //         $groupIds = [5, 6];
    //         if (empty($groupIds)) {
    //             return response()->json([
    //                 'status' => 400,
    //                 'error' => true,
    //                 'message' => 'No group IDs provided',
    //                 'data' => []
    //             ], 400);
    //         }

    //         // Create placeholders for safe parameter binding
    //         $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    //         $sql = "
    //         WITH RECURSIVE GroupInMainGroupP AS (
    //             SELECT
    //                 \"groupId\",
    //                 \"groupUnder\",
    //                 1 AS \"HierarchyLevel\",
    //                 ARRAY[\"groupId\"] AS path
    //             FROM \"tbl_AccountGroup\"
    //             WHERE \"groupId\" IN ($placeholders)

    //             UNION ALL

    //             SELECT
    //                 e.\"groupId\",
    //                 e.\"groupUnder\",
    //                 g.\"HierarchyLevel\" + 1,
    //                 g.path || e.\"groupId\"
    //             FROM \"tbl_AccountGroup\" e
    //             JOIN GroupInMainGroupP g
    //                 ON e.\"groupUnder\" = g.\"groupId\"
    //             WHERE
    //                 g.\"HierarchyLevel\" < 10
    //                 AND NOT e.\"groupId\" = ANY (g.path)
    //         )

    //         SELECT DISTINCT
    //             A.\"groupId\",
    //             A.\"accountGroupName\",
    //             A.\"groupUnder\"
    //         FROM \"tbl_AccountGroup\" A
    //         JOIN GroupInMainGroupP G
    //             ON A.\"groupId\" = G.\"groupId\"
    //         ORDER BY A.\"accountGroupName\" ASC
    //         ";

    //         $result = DB::select($sql, $groupIds);
    //         $excludedGroupIds = collect($result)->pluck('groupId')->toArray();

    //         $bank = AccountLedger::whereIn('groupId', $excludedGroupIds)
    //             ->whereRaw(
    //                 'EXISTS (
    //             SELECT 1
    //             FROM jsonb_array_elements("branchId") AS b
    //             WHERE (b->>\'branchId\')::int = ?
    //         )',
    //                 [$branchId]
    //             )->orderBy('ledgerName', 'asc')->get();


    //         $groupIds2 = [8];
    //         $placeholders2 = implode(',', array_fill(0, count($groupIds2), '?'));
    //         $sql2 = "
    //         WITH RECURSIVE GroupInMainGroupP AS (
    //             SELECT
    //                 \"groupId\",
    //                 \"groupUnder\",
    //                 1 AS \"HierarchyLevel\",
    //                 ARRAY[\"groupId\"] AS path
    //             FROM \"tbl_AccountGroup\"
    //             WHERE \"groupId\" IN ($placeholders2)

    //             UNION ALL

    //             SELECT
    //                 e.\"groupId\",
    //                 e.\"groupUnder\",
    //                 g.\"HierarchyLevel\" + 1,
    //                 g.path || e.\"groupId\"
    //             FROM \"tbl_AccountGroup\" e
    //             JOIN GroupInMainGroupP g
    //                 ON e.\"groupUnder\" = g.\"groupId\"
    //             WHERE
    //                 g.\"HierarchyLevel\" < 10
    //                 AND NOT e.\"groupId\" = ANY (g.path)
    //         )

    //         SELECT DISTINCT
    //             A.\"groupId\",
    //             A.\"accountGroupName\",
    //             A.\"groupUnder\"
    //         FROM \"tbl_AccountGroup\" A
    //         JOIN GroupInMainGroupP G
    //             ON A.\"groupId\" = G.\"groupId\"
    //         ORDER BY A.\"accountGroupName\" ASC
    //         ";

    //         $result2 = DB::select($sql2, $groupIds2);
    //         $excludedGroupIds2 = collect($result2)->pluck('groupId')->toArray();

    //         $cash = AccountLedger::whereIn('groupId', $excludedGroupIds2)
    //             ->whereRaw(
    //                 'EXISTS (
    //             SELECT 1
    //             FROM jsonb_array_elements("branchId") AS b
    //             WHERE (b->>\'branchId\')::int = ?
    //         )',
    //                 [$branchId]
    //             )->orderBy('ledgerName', 'asc')->get();


    //         $groupIds3 = [25];
    //         $placeholders3 = implode(',', array_fill(0, count($groupIds3), '?'));
    //         $sql3 = "
    //         WITH RECURSIVE GroupInMainGroupP AS (
    //             SELECT
    //                 \"groupId\",
    //                 \"groupUnder\",
    //                 1 AS \"HierarchyLevel\",
    //                 ARRAY[\"groupId\"] AS path
    //             FROM \"tbl_AccountGroup\"
    //             WHERE \"groupId\" IN ($placeholders3)

    //             UNION ALL

    //             SELECT
    //                 e.\"groupId\",
    //                 e.\"groupUnder\",
    //                 g.\"HierarchyLevel\" + 1,
    //                 g.path || e.\"groupId\"
    //             FROM \"tbl_AccountGroup\" e
    //             JOIN GroupInMainGroupP g
    //                 ON e.\"groupUnder\" = g.\"groupId\"
    //             WHERE
    //                 g.\"HierarchyLevel\" < 10
    //                 AND NOT e.\"groupId\" = ANY (g.path)
    //         )

    //         SELECT DISTINCT
    //             A.\"groupId\",
    //             A.\"accountGroupName\",
    //             A.\"groupUnder\"
    //         FROM \"tbl_AccountGroup\" A
    //         JOIN GroupInMainGroupP G
    //             ON A.\"groupId\" = G.\"groupId\"
    //         ORDER BY A.\"accountGroupName\" ASC
    //         ";

    //         $result3 = DB::select($sql3, $groupIds3);
    //         $excludedGroupIds3 = collect($result3)->pluck('groupId')->toArray();

    //         $salesAccount = AccountLedger::whereIn('groupId', $excludedGroupIds3)
    //             ->whereRaw(
    //                 'EXISTS (
    //             SELECT 1
    //             FROM jsonb_array_elements("branchId") AS b
    //             WHERE (b->>\'branchId\')::int = ?
    //         )',
    //                 [$branchId]
    //             )->orderBy('ledgerName', 'asc')->get();


    //         $groupIds4 = [13, 17];
    //         $placeholders4 = implode(',', array_fill(0, count($groupIds4), '?'));
    //         $sql4 = "
    //         WITH RECURSIVE GroupInMainGroupP AS (
    //             SELECT
    //                 \"groupId\",
    //                 \"groupUnder\",
    //                 1 AS \"HierarchyLevel\",
    //                 ARRAY[\"groupId\"] AS path
    //             FROM \"tbl_AccountGroup\"
    //             WHERE \"groupId\" IN ($placeholders4)

    //             UNION ALL

    //             SELECT
    //                 e.\"groupId\",
    //                 e.\"groupUnder\",
    //                 g.\"HierarchyLevel\" + 1,
    //                 g.path || e.\"groupId\"
    //             FROM \"tbl_AccountGroup\" e
    //             JOIN GroupInMainGroupP g
    //                 ON e.\"groupUnder\" = g.\"groupId\"
    //             WHERE
    //                 g.\"HierarchyLevel\" < 10
    //                 AND NOT e.\"groupId\" = ANY (g.path)
    //         )

    //         SELECT DISTINCT
    //             A.\"groupId\",
    //             A.\"accountGroupName\",
    //             A.\"groupUnder\"
    //         FROM \"tbl_AccountGroup\" A
    //         JOIN GroupInMainGroupP G
    //             ON A.\"groupId\" = G.\"groupId\"
    //         ORDER BY A.\"accountGroupName\" ASC
    //         ";

    //         $result4 = DB::select($sql4, $groupIds4);
    //         $excludedGroupIds4 = collect($result4)->pluck('groupId')->toArray();

    //         $othercharge = AccountLedger::whereIn('groupId', $excludedGroupIds4)
    //             ->whereRaw(
    //                 'EXISTS (
    //             SELECT 1
    //             FROM jsonb_array_elements("branchId") AS b
    //             WHERE (b->>\'branchId\')::int = ?
    //         )',
    //                 [$branchId]
    //             )->orderBy('ledgerName', 'asc')->get();


    //         $ledgerId = $request->input('ledgerId');
    //         $currencyId = $request->input('currencyId');

    //         $LedgerBalance = DB::select("
    //         SELECT * FROM get_current_ledger_balance(?, ?, ?)
    //     ", [$ledgerId, $branchId, $currencyId]);

    //         $financesettings = collect(DB::select('SELECT * FROM public.get_all_finance_settings()'));

    //         $defaultsaleAccountId = $financesettings
    //             ->where('branchId', $branchId)
    //             ->first()
    //             ->defaultSalesAccount ?? null;

    //         $customeraddress = AccountLedger::with([
    //             'ShippingAddress',
    //         ])->find($defaultsaleAccountId);

    //         $transactionbatch = DB::select('SELECT * FROM fn_get_transaction_batch_by_vouchertype(?,?)', [$voucherType, $branchId]);
    //         $currencywithConversion = DB::select('SELECT * FROM get_currency_with_conversion()');

    //         // =====================================================
    //         // NEW: Sales Quotation List, Proforma List,
    //         //      Sales Order List, Delivery Note List
    //         // =====================================================
    //         $p_ledgerid = $request->input('p_ledgerid', null);
    //         $p_branchid = $request->input('p_branchid', $branchId);
    //         $p_salesmasterid = $request->input('p_salesmasterid', null);
    //         $p_isaccountsposting = $request->input('p_isaccountsposting', null);

    //         $salesInvoiceAgainstData = [
    //         'salesQuotationList' => [],
    //         'proformaList'       => [],
    //         'salesOrderList'     => [],
    //         'deliveryNoteList'   => [],
    //     ];

    //        if ($p_ledgerid && $p_branchid) {
    //         // 1. Sales Quotation List
    //         $salesInvoiceAgainstData['salesQuotationList'] = DB::select(
    //             'SELECT * FROM public.get_sales_invoice_sales_quotation_list(?, ?, ?)',
    //             [$p_ledgerid, $p_branchid, $p_salesmasterid]
    //         );

    //         // 2. Proforma List
    //         $salesInvoiceAgainstData['proformaList'] = DB::select(
    //             'SELECT * FROM public.get_sales_invoice_proforma_list(?, ?, ?)',
    //             [$p_ledgerid, $p_branchid, $p_salesmasterid]
    //         );

    //         // 3. Sales Order List
    //         $salesInvoiceAgainstData['salesOrderList'] = DB::select(
    //             'SELECT * FROM public.get_sales_invoice_sales_order_list(?, ?, ?)',
    //             [$p_ledgerid, $p_branchid, $p_salesmasterid]
    //         );

    //         // 4. Delivery Note List
    //         $salesInvoiceAgainstData['deliveryNoteList'] = DB::select(
    //             'SELECT * FROM public.get_sales_invoice_delivery_note_list(?, ?, ?, ?)',
    //             [$p_ledgerid, $p_branchid, $p_salesmasterid, $p_isaccountsposting]
    //         );
    //     }

    //         return response()->json([
    //         'status'  => 200,
    //         'error'   => false,
    //         'message' => 'Sales Data',
    //         'data' => [
    //             'voucherdata'   => [
    //                 'table_pk'        => $codeData['table_pk'],
    //                 'voucherCode'     => $codeData['voucherCode'],
    //                 'voucherNo'       => $codeData['voucherNo'],
    //                 'suffixPrefixId'  => $codeData['suffixPrefixId'],
    //             ],
    //             'employees'               => $employees,
    //             'costcentre'              => $costcentre,
    //             'transactionbatch'        => $transactionbatch,
    //             'pricinglevel'            => $pricinglevel,
    //             'customersupplierLedgers' => $customersupplierLedgers,
    //             'godowns'                 => $godowns,
    //             'bank'                    => $bank,
    //             'cash'                    => $cash,
    //             'salesAccount'            => $salesAccount,
    //             'othercharge'             => $othercharge,
    //             'LedgerBalance'           => $LedgerBalance[0] ?? null,
    //             'customeraddress'         => $customeraddress,
    //             'currencywithConversion'  => $currencywithConversion,
    //             'salesInvoiceAgainstData' => $salesInvoiceAgainstData,
    //         ]
    //     ]);
    // } catch (\Exception $e) {
    //     return response()->json([
    //         'status'  => 500,
    //         'error'   => true,
    //         'message' => 'Error: ' . $e->getMessage()
    //     ]);
    // }
    // }
    public function allSalesApis(Request $request)
    {
        try {

            $voucherType = $request->input('voucherType', 'Sales Invoice');
            $branchId = $request->input('branchId', null);
            $yearId = $request->input('yearId', null);
            $ledgerId = $request->input('ledgerId', null);

            if (!$branchId) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Branch ID is required',
                    'data'   => []
                ], 400);
            }

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            $employees = DB::select('SELECT * FROM public.get_all_employee()');
            $costcentre = DB::select('SELECT * FROM public.get_all_costcentres()');
            $pricinglevel = DB::select('SELECT * FROM public.get_all_pricing_level()');

            $ledgerTypes = $request->input('ledgerTypes', []);

            if (empty($ledgerTypes)) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'Not Found ledger Type',
                    'data' => []
                ], 400);
            }

            $customersupplierLedgers = AccountLedger::whereIn('ledgerType', $ledgerTypes)
                ->whereRaw(
                    'EXISTS (
                SELECT 1
                FROM jsonb_array_elements("branchId") AS b
                WHERE (b->>\'branchId\')::int = ?
            )',
                    [$branchId]
                )
                ->orderBy('ledgerName', 'asc')
                ->get();

            $godowns = DB::select('SELECT * FROM public.get_all_godown(?)', [$branchId]);

            $groupIds = [5, 6];
            if (empty($groupIds)) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'No group IDs provided',
                    'data' => []
                ], 400);
            }

            // Create placeholders for safe parameter binding
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $sql = "
        WITH RECURSIVE GroupInMainGroupP AS (
            SELECT
                \"groupId\",
                \"groupUnder\",
                1 AS \"HierarchyLevel\",
                ARRAY[\"groupId\"] AS path
            FROM \"tbl_AccountGroup\"
            WHERE \"groupId\" IN ($placeholders)

            UNION ALL

            SELECT
                e.\"groupId\",
                e.\"groupUnder\",
                g.\"HierarchyLevel\" + 1,
                g.path || e.\"groupId\"
            FROM \"tbl_AccountGroup\" e
            JOIN GroupInMainGroupP g
                ON e.\"groupUnder\" = g.\"groupId\"
            WHERE
                g.\"HierarchyLevel\" < 10
                AND NOT e.\"groupId\" = ANY (g.path)
        )

        SELECT DISTINCT
            A.\"groupId\",
            A.\"accountGroupName\",
            A.\"groupUnder\"
        FROM \"tbl_AccountGroup\" A
        JOIN GroupInMainGroupP G
            ON A.\"groupId\" = G.\"groupId\"
        ORDER BY A.\"accountGroupName\" ASC
        ";

            $result = DB::select($sql, $groupIds);
            $excludedGroupIds = collect($result)->pluck('groupId')->toArray();

            $bank = AccountLedger::whereIn('groupId', $excludedGroupIds)
                ->whereRaw(
                    'EXISTS (
            SELECT 1
            FROM jsonb_array_elements("branchId") AS b
            WHERE (b->>\'branchId\')::int = ?
        )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();


            $groupIds2 = [8];
            $placeholders2 = implode(',', array_fill(0, count($groupIds2), '?'));
            $sql2 = "
        WITH RECURSIVE GroupInMainGroupP AS (
            SELECT
                \"groupId\",
                \"groupUnder\",
                1 AS \"HierarchyLevel\",
                ARRAY[\"groupId\"] AS path
            FROM \"tbl_AccountGroup\"
            WHERE \"groupId\" IN ($placeholders2)

            UNION ALL

            SELECT
                e.\"groupId\",
                e.\"groupUnder\",
                g.\"HierarchyLevel\" + 1,
                g.path || e.\"groupId\"
            FROM \"tbl_AccountGroup\" e
            JOIN GroupInMainGroupP g
                ON e.\"groupUnder\" = g.\"groupId\"
            WHERE
                g.\"HierarchyLevel\" < 10
                AND NOT e.\"groupId\" = ANY (g.path)
        )

        SELECT DISTINCT
            A.\"groupId\",
            A.\"accountGroupName\",
            A.\"groupUnder\"
        FROM \"tbl_AccountGroup\" A
        JOIN GroupInMainGroupP G
            ON A.\"groupId\" = G.\"groupId\"
        ORDER BY A.\"accountGroupName\" ASC
        ";

            $result2 = DB::select($sql2, $groupIds2);
            $excludedGroupIds2 = collect($result2)->pluck('groupId')->toArray();

            $cash = AccountLedger::whereIn('groupId', $excludedGroupIds2)
                ->whereRaw(
                    'EXISTS (
            SELECT 1
            FROM jsonb_array_elements("branchId") AS b
            WHERE (b->>\'branchId\')::int = ?
        )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();


            $groupIds3 = [25];
            $placeholders3 = implode(',', array_fill(0, count($groupIds3), '?'));
            $sql3 = "
        WITH RECURSIVE GroupInMainGroupP AS (
            SELECT
                \"groupId\",
                \"groupUnder\",
                1 AS \"HierarchyLevel\",
                ARRAY[\"groupId\"] AS path
            FROM \"tbl_AccountGroup\"
            WHERE \"groupId\" IN ($placeholders3)

            UNION ALL

            SELECT
                e.\"groupId\",
                e.\"groupUnder\",
                g.\"HierarchyLevel\" + 1,
                g.path || e.\"groupId\"
            FROM \"tbl_AccountGroup\" e
            JOIN GroupInMainGroupP g
                ON e.\"groupUnder\" = g.\"groupId\"
            WHERE
                g.\"HierarchyLevel\" < 10
                AND NOT e.\"groupId\" = ANY (g.path)
        )

        SELECT DISTINCT
            A.\"groupId\",
            A.\"accountGroupName\",
            A.\"groupUnder\"
        FROM \"tbl_AccountGroup\" A
        JOIN GroupInMainGroupP G
            ON A.\"groupId\" = G.\"groupId\"
        ORDER BY A.\"accountGroupName\" ASC
        ";

            $result3 = DB::select($sql3, $groupIds3);
            $excludedGroupIds3 = collect($result3)->pluck('groupId')->toArray();

            $salesAccount = AccountLedger::whereIn('groupId', $excludedGroupIds3)
                ->whereRaw(
                    'EXISTS (
            SELECT 1
            FROM jsonb_array_elements("branchId") AS b
            WHERE (b->>\'branchId\')::int = ?
        )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();


            $groupIds4 = [13, 17];
            $placeholders4 = implode(',', array_fill(0, count($groupIds4), '?'));
            $sql4 = "
        WITH RECURSIVE GroupInMainGroupP AS (
            SELECT
                \"groupId\",
                \"groupUnder\",
                1 AS \"HierarchyLevel\",
                ARRAY[\"groupId\"] AS path
            FROM \"tbl_AccountGroup\"
            WHERE \"groupId\" IN ($placeholders4)

            UNION ALL

            SELECT
                e.\"groupId\",
                e.\"groupUnder\",
                g.\"HierarchyLevel\" + 1,
                g.path || e.\"groupId\"
            FROM \"tbl_AccountGroup\" e
            JOIN GroupInMainGroupP g
                ON e.\"groupUnder\" = g.\"groupId\"
            WHERE
                g.\"HierarchyLevel\" < 10
                AND NOT e.\"groupId\" = ANY (g.path)
        )

        SELECT DISTINCT
            A.\"groupId\",
            A.\"accountGroupName\",
            A.\"groupUnder\"
        FROM \"tbl_AccountGroup\" A
        JOIN GroupInMainGroupP G
            ON A.\"groupId\" = G.\"groupId\"
        ORDER BY A.\"accountGroupName\" ASC
        ";

            $result4 = DB::select($sql4, $groupIds4);
            $excludedGroupIds4 = collect($result4)->pluck('groupId')->toArray();

            $othercharge = AccountLedger::whereIn('groupId', $excludedGroupIds4)
                ->whereRaw(
                    'EXISTS (
            SELECT 1
            FROM jsonb_array_elements("branchId") AS b
            WHERE (b->>\'branchId\')::int = ?
        )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();


            $ledgerId = $request->input('ledgerId');
            $currencyId = $request->input('currencyId');

            $LedgerBalance = DB::select("
        SELECT * FROM get_current_ledger_balance(?, ?, ?)
    ", [$ledgerId, $branchId, $currencyId]);

            $financesettings = collect(DB::select('SELECT * FROM public.get_all_finance_settings()'));

            // $defaultsaleAccountId = $financesettings
            //     ->where('branchId', $branchId)
            //     ->first()
            //     ->defaultSalesAccount ?? null;

            $customeraddress = AccountLedger::with([
                'ShippingAddress',
            ])->find($ledgerId);

            $transactionbatch = DB::select('SELECT * FROM fn_get_transaction_batch_by_vouchertype(?,?)', [$voucherType, $branchId]);
            $currencywithConversion = DB::select('SELECT * FROM get_currency_with_conversion()');

            // =====================================================
            // Sales Invoice Against Data
            // =====================================================
            $p_ledgerid = $request->input('p_ledgerid', null);
            $p_branchid = $request->input('p_branchid', $branchId);
            $p_salesmasterid = $request->input('p_salesmasterid', null);
            $p_isaccountsposting = $request->input('p_isaccountsposting', null);

            $salesInvoiceAgainstData = [
                'salesQuotationList' => [],
                'proformaList'       => [],
                'salesOrderList'     => [],
                'deliveryNoteList'   => [],
            ];

            if ($p_ledgerid && $p_branchid) {
                $salesInvoiceAgainstData['salesQuotationList'] = DB::select(
                    'SELECT * FROM public.get_sales_invoice_sales_quotation_list(?, ?, ?)',
                    [$p_ledgerid, $p_branchid, $p_salesmasterid]
                );

                $salesInvoiceAgainstData['proformaList'] = DB::select(
                    'SELECT * FROM public.get_sales_invoice_proforma_list(?, ?, ?)',
                    [$p_ledgerid, $p_branchid, $p_salesmasterid]
                );

                $salesInvoiceAgainstData['salesOrderList'] = DB::select(
                    'SELECT * FROM public.get_sales_invoice_sales_order_list(?, ?, ?)',
                    [$p_ledgerid, $p_branchid, $p_salesmasterid]
                );

                $salesInvoiceAgainstData['deliveryNoteList'] = DB::select(
                    'SELECT * FROM public.get_sales_invoice_delivery_note_list(?, ?, ?, ?)',
                    [$p_ledgerid, $p_branchid, $p_salesmasterid, $p_isaccountsposting]
                );
            }

            // =====================================================
            // Proforma Against Data
            // =====================================================
            $fromDate = $request->input('fromDate', null);
            $toDate = $request->input('toDate', null);

            $proformaAgainstData = [
                'quotationNoByDate' => [],
            ];

            if ($fromDate && $toDate) {
                $proformaAgainstData['quotationNoByDate'] = DB::select(
                    'SELECT * FROM public.get_quotation_no_by_date(?, ?)',
                    [$fromDate, $toDate]
                );
            }

            // =====================================================
            // Sales Order Against Data
            // =====================================================
            $orderLedgerId = $request->input('orderLedgerId', null);
            $orderBranchId = $request->input('orderBranchId', $branchId);
            $orderMasterId = $request->input('orderMasterId', null);
            $in_proformaMasterId = $request->input('in_proformaMasterId', null);

            $salesOrderAgainstData = [
                'salesOrderQuotationList' => [],
                'salesOrderProformaList'  => [],
            ];

            if ($orderLedgerId && $orderBranchId) {
                // Sales Order Quotation List
                $salesOrderAgainstData['salesOrderQuotationList'] = DB::select(
                    'SELECT * FROM public.get_sales_order_quotation_list(?, ?, ?)',
                    [$orderLedgerId, $orderBranchId, $orderMasterId]
                );

                // Sales Order Proforma List
                $salesOrderAgainstData['salesOrderProformaList'] = DB::select(
                    'SELECT * FROM public.get_sales_order_proforma_list(?, ?, ?)',
                    [$orderLedgerId, $orderBranchId, $in_proformaMasterId]
                );
            }

            // =====================================================
            // Delivery Note Against Data
            // =====================================================
            $dnLedgerId = $request->input('in_ledgerId', null);
            $dnBranchId = $request->input('in_branchId', $branchId);
            $in_deliverynotemasterId = $request->input('in_deliverynotemasterId', null);
            $p_deliverynotemasterid = $request->input('p_deliverynotemasterid', null);

            $deliveryNoteAgainstData = [
                'deliveryNoteSalesQuotationList' => [],
                'deliveryNoteProformaList'       => [],
                'deliveryNoteSalesOrderList'     => [],
            ];

            if ($dnLedgerId && $dnBranchId) {
                // Delivery Note Sales Quotation List
                $deliveryNoteAgainstData['deliveryNoteSalesQuotationList'] = DB::select(
                    'SELECT * FROM public.get_delivery_note_sales_quotation_list(?, ?, ?)',
                    [$dnLedgerId, $dnBranchId, $in_deliverynotemasterId]
                );

                // Delivery Note Proforma List
                $deliveryNoteAgainstData['deliveryNoteProformaList'] = DB::select(
                    'SELECT * FROM public.get_delivery_note_proforma_list(?, ?, ?)',
                    [$dnLedgerId, $dnBranchId, $p_deliverynotemasterid]
                );

                // Delivery Note Sales Order List
                $deliveryNoteAgainstData['deliveryNoteSalesOrderList'] = DB::select(
                    'SELECT * FROM public.get_delivery_note_sales_order_list(?, ?, ?)',
                    [$dnLedgerId, $dnBranchId, $p_deliverynotemasterid]
                );
            }

            $taxMaster = DB::select('SELECT * FROM public.get_all_tax_master() ORDER BY "taxName" ASC');
            $stock = DB::select('
                    SELECT
                        sp."productCode",
                        sp."godownId"::bigint AS "godownId",
                        g."GodownName",
                        COALESCE(SUM(sp."inwardQty") - SUM(sp."outwardQty"), 0) AS "closingStock"
                    FROM public."tbl_StockPosting" sp
                    LEFT JOIN public."tbl_Godown" g ON g."GodownId" = sp."godownId"
                    GROUP BY sp."productCode", sp."godownId", g."GodownName"
                ');

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Sales Data',
                'data' => [
                    'voucherdata'   => [
                        'table_pk'        => $codeData['table_pk'],
                        'voucherCode'     => $codeData['voucherCode'],
                        'voucherNo'       => $codeData['voucherNo'],
                        'suffixPrefixId'  => $codeData['suffixPrefixId'],
                    ],
                    'employees'               => $employees,
                    'costcentre'              => $costcentre,
                    'transactionbatch'        => $transactionbatch,
                    'pricinglevel'            => $pricinglevel,
                    'taxMaster'               => $taxMaster,
                    'customersupplierLedgers' => $customersupplierLedgers,
                    'godowns'                 => $godowns,
                    'bank'                    => $bank,
                    'cash'                    => $cash,
                    'salesAccount'            => $salesAccount,
                    'othercharge'             => $othercharge,
                    'LedgerBalance'           => $LedgerBalance[0] ?? null,
                    'customeraddress'         => $customeraddress,
                    'currencywithConversion'  => $currencywithConversion,
                    // Against Data sections
                    'salesInvoiceAgainstData' => $salesInvoiceAgainstData,
                    'proformaAgainstData'     => $proformaAgainstData,
                    'salesOrderAgainstData'   => $salesOrderAgainstData,
                    'deliveryNoteAgainstData' => $deliveryNoteAgainstData,
                    'stock'                   => $stock
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    public function reorderStock(Request $request)
    {
        $branchId = $request->branchId ?? 1;

        try {

            $products = DB::select(
                'SELECT * FROM product_stock_report(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    'All',         // p_groupid
                    'All',         // p_productcode
                    'All',         // p_brandid
                    'All',         // p_taxid
                    'All',         // p_taxtype
                    'All',         // p_godownid
                    'All',         // p_rackid
                    'Reorder',     // 🔥 p_criteria
                    '',            // p_starttext
                    $branchId,     // p_branchid
                    true,          // p_isactive
                    false,         // p_isdatewise
                    null,          // p_fromdate
                    null,          // p_todate
                    false          // p_dummy
                ]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'count'  => count($products),
                'message' => count($products) > 0
                    ? 'Reorder stock available'
                    : 'No reorder stock',
                'data'   => $products
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // public function UpdateZatcaFieldsWithOutAuth(Request $request)
    // {
    //     $encryptedDb = Crypt::encryptString((string) $request->db);
    //     DatabaseService::switchToDatabase($encryptedDb);
    //     DB::beginTransaction();
    //     try {
    //         if ($request->VoucherType === 'Sales Invoice') {

    //             DB::statement(
    //                 'SELECT update_salesmaster_zatca_details(?, ?, ?, ?, ?, ?, ?, ?, ?)',
    //                 [
    //                     $request->salesMasterId,
    //                     $request->PreInvoiceHash,
    //                     $request->CurrentInvoiceHash,
    //                     $request->UUID,
    //                     $request->ZatcaStatus,
    //                     $request->LogData,
    //                     $request->Qrlink,
    //                     $request->UploadedInvoice,
    //                     $request->ZatcaSignedXml
    //                 ]
    //             );
    //         } elseif ($request->VoucherType === 'Sales Return') {
    //             DB::statement(
    //                 'SELECT update_salesreturn_zatca_details(?, ?, ?, ?, ?, ?, ?, ?, ?)',
    //                 [
    //                     $request->ReturnMasterId,
    //                     $request->PreInvoiceHash,
    //                     $request->CurrentInvoiceHash,
    //                     $request->UUID,
    //                     $request->ZatcaStatus,
    //                     $request->LogData,
    //                     $request->Qrlink,
    //                     $request->UploadedInvoice,
    //                     $request->ZatcaSignedXml
    //                 ]
    //             );
    //         } else {
    //             return response()->json([
    //                 'status' => 400,
    //                 'error' => true,
    //                 'message' => 'Invalid VoucherType'
    //             ]);
    //         }

    //         ZatcaRegistration::where('branchId', $request->branchId)
    //             ->update([
    //                 'PrevInvoiceHash' => $request->CurrentInvoiceHash
    //             ]);

    //         DB::commit();

    //         return response()->json([
    //             'status'  => 200,
    //             'error'   => false,
    //             'message' => 'ZATCA details updated successfully'
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status'  => 500,
    //             'error'   => true,
    //             'message' => 'Error: ' . $e->getMessage()
    //         ]);
    //     }
    // }
    public function UpdateZatcaFieldsWithOutAuth(Request $request)
    {
        $request->validate([
            'db'          => 'required|string',
            'VoucherType' => 'required|string|in:Sales Invoice,Sales Return',
            'ReturnMasterId' => 'required|integer',   // <-- single id key for both
            'branchId'    => 'required|integer',

            'PreInvoiceHash'     => 'nullable|string',
            'CurrentInvoiceHash' => 'nullable|string',
            'UUID'               => 'nullable|string',
            'ZatcaStatus'        => 'nullable',
            'LogData'            => 'nullable|string',
            'Qrlink'             => 'nullable|string',
            'UploadedInvoice'    => 'nullable|string',
            'ZatcaSignedXml'     => 'nullable|string',
        ]);

        $encryptedDb = Crypt::encryptString((string) $request->input('db'));
        DatabaseService::switchToDatabase($encryptedDb);

        $id = (int) $request->input('ReturnMasterId'); // <-- treat as SalesMasterId or SalesReturnMasterId

        try {
            // Optional but recommended: avoid "200 but not updated"
            if ($request->input('VoucherType') === 'Sales Invoice') {
                $exists = DB::selectOne(
                    'SELECT 1 FROM "tbl_SalesMaster" WHERE "salesMasterId" = ?',
                    [$id]
                );
                if (!$exists) {
                    return response()->json([
                        'status' => 404,
                        'error' => true,
                        'message' => "Sales Invoice not found for salesMasterId={$id}"
                    ], 404);
                }
            } else {
                $exists = DB::selectOne(
                    'SELECT 1 FROM "tbl_SalesReturnMaster" WHERE "ReturnMasterId" = ?',
                    [$id]
                );
                if (!$exists) {
                    return response()->json([
                        'status' => 404,
                        'error' => true,
                        'message' => "Sales Return not found for ReturnMasterId={$id}"
                    ], 404);
                }
            }

            DB::transaction(function () use ($request, $id) {

                $args = [
                    $id,
                    $request->input('PreInvoiceHash'),
                    $request->input('CurrentInvoiceHash'),
                    $request->input('UUID'),
                    $request->input('ZatcaStatus'),
                    $request->input('LogData'),
                    $request->input('Qrlink'),
                    $request->input('UploadedInvoice'),
                    $request->input('ZatcaSignedXml'),
                ];

                if ($request->input('VoucherType') === 'Sales Invoice') {
                    DB::statement('SELECT public.update_salesmaster_zatca_details(?, ?, ?, ?, ?, ?, ?, ?, ?)', $args);
                } else {
                    DB::statement('SELECT public.update_salesreturn_zatca_details(?, ?, ?, ?, ?, ?, ?, ?, ?)', $args);
                }

                // ONLY UPDATE (no insert). If branch row doesn't exist, it will update 0 rows.
                \App\Models\ZatcaRegistration::where('branchId', $request->input('branchId'))
                    ->update(['PrevInvoiceHash' => $request->input('CurrentInvoiceHash')]);
            });

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'ZATCA details updated successfully'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function SalesListByCustomer(Request $request)
    {
        try {



            $ledgerId = $request->ledgerId;
            $branchId = $request->branchId;

            $data = DB::select(
                'SELECT * FROM public.get_sales_list_by_customer(?, ?)',
                [
                    $ledgerId,
                    $branchId
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Sales report fetched successfully.',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function customerInfo(Request $request)
    {

        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;

        try {
            $rows = DB::select(
                'select * from public.get_salesmaster_customer_info(?, ?)',
                [$ledgerId, $branchId]
            );
            $data = array_map(function ($r) {
                return [
                    'customerName'    => $r->{'Customer name'}  ?? $r->customerName    ?? null,
                    'customerAddress' => $r->{'Customer address'} ?? $r->CustomerAddress ?? null,
                    'customerVat'     => $r->{'Customer vat'}    ?? $r->customerVATNo   ?? null,
                    'customerMobile'  => $r->{'Customer mobile'} ?? $r->CustomerPhone   ?? null,
                ];
            }, $rows);

            return response()->json([
                'status' => true,
                'data'   => $data,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getAllProductStock(Request $request)
    {
        try {
            $productCode = $request->input('productCode');
            $godownId     = $request->input('godownId');
            $branchId     = $request->input('branchId');

            $sql = '
            SELECT
                sp."productCode",
                sp."godownId"::bigint AS "godownId",
                COALESCE(SUM(sp."inwardQty"), 0)
                    - COALESCE(SUM(sp."outwardQty"), 0) AS "closingStock"
            FROM public."tbl_StockPosting" sp
            WHERE 1 = 1
        ';

            $params = [];

            if ($productCode !== null && $productCode !== '') {
                $sql .= ' AND sp."productCode" = :productCode';
                $params['productCode'] = $productCode;
            }

            if ($godownId !== null && $godownId !== '') {
                $sql .= ' AND sp."godownId" = :godownId';
                $params['godownId'] = $godownId;
            }

            if ($branchId !== null && $branchId !== '') {
                $sql .= ' AND sp."branchId" = :branchId';
                $params['branchId'] = $branchId;
            }

            $sql .= '
            GROUP BY
                sp."productCode",
                sp."godownId"
            ORDER BY
                sp."productCode",
                sp."godownId"
        ';

            $stock = DB::select($sql, $params);

            return response()->json([
                'success' => true,
                'data' => $stock
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stock data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}