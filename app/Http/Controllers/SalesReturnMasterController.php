<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\SalesReturnMaster;
use App\Models\SalesMaster;
use App\Models\ZatcaRegistration;
use App\Models\LedgerPosting;
use App\Models\StockPosting;
use App\Models\Partybalance;
use App\Models\SalesReturnDetails1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesReturnMasterController extends Controller
{
    /**
     * List SalesReturnMaster
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $sales = DB::select(
            'SELECT * FROM public.get_sales_return_master_list(?,?,?,?,?,?,?)',
            [
                $request->fromDate ?? null,
                $request->toDate ?? null,
                $limit,
                $page,
                $request->branchId,
                $request->vouchercode ?? null,
                $request->customername ?? null
            ]
        );

        $collection = collect($sales);
        $total = $collection->count();

        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Return Retrieved Successfully',
            'data' => $paginated,
            'meta' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
            ]
        ]);
    }

    /**
     * Get SalesReturnMaster by id
     */
    public function show($id)
    {
        $sales = DB::select('SELECT * FROM public.get_sales_return_master_data_byid(?)', [$id]);

        // Check if found
        if (empty($sales)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Return Not Found',
            ], 404);
        }

        $sales = (array) $sales[0];

        $salesDetails = DB::select(
            'SELECT * FROM public.get_sales_return_details1_by_return_master_id(?)',
            [$sales['ReturnMasterId']]
        );

        $salesDetails = collect($salesDetails)->map(function ($detail) use ($sales) {
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

            // Get mrp from tbl_ProductSalesPrice
            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', $detail['unitId'])
                ->where('branchId', $sales['branchId'])
                ->value('amount');

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => $product->brand ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $sales['returnDetails'] = $salesDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Return Retrieved Successfully',
            'data' => $sales,
        ], 200);
    }


    /**
     * Create Sales
     */
    public function store(Request $request)
    {
        // Log::debug('sales return Incoming Request:', $request->all());
        DB::beginTransaction();
        try {
            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $vouchertype_generation = $request->taxType === 'NA' ? 'Sales Return-No Tax' : 'Sales Return';
            $codeData = voucherNoGeneration($vouchertype_generation, $branchId, $yearId, $request->taxType);
            $createdDate = getBranchNow($branchId);
            // Insert sales return
            $masterParams = [
                $codeData['table_pk'],
                $codeData['voucherNo'],
                $codeData['suffixPrefixId'],
                $codeData['voucherCode'],
                $request->date,
                $request->billTime,
                $request->salesMasterId,
                $request->ledgerId,
                $request->salesAccountId,
                $request->pricingLevelId,
                $request->employeeId,
                $request->costCentreId,
                $request->BatchId,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->partyName,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->lrNo,
                $request->transportCompany,
                $request->narration,
                $request->taxType,
                $request->taxableAmt,
                $request->subTotal,
                $request->totalTax,
                $request->additionalCost,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0),   // ✅ NEW

                $request->roundOff,
                $request->totalAmount,
                $request->paymentMode,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->billDiscOnProductStatus,
                $request->AddCostonProductStatus,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->AgainstNo,
                $request->isVan,
                $request->vanExecutiveId,
                $request->preinvoiceHash,
                $request->currentinvoiceHash,
                $request->UUID,
                $request->Zatcastatus,
                $request->logdata,
                $request->qr_link,
                $request->branchId,
                $request->CreatedUser,
                $request->reason,
                $request->GodownId,
                $request->creditPeriod,
                $request->formType,
                $createdDate
            ];

            $sql = 'SELECT public.insert_sales_return_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ') AS id';

            $ReturnMasterId = DB::selectOne($sql, $masterParams);

            $sales = SalesReturnMaster::find($ReturnMasterId->id);

            // Insert into tbl_SalesReturnDetails1
            if ($request->has('salesDetails')) {
                foreach ($request->salesDetails as $sd) {
                    $branchNow = getBranchNow($sd['branchId']);
                    $detailParams = [
                        $sales->ReturnMasterId,
                        $sd['salesDetails1Id'],
                        $sd['productCode'],
                        $sd['productDescription'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],

                        $sd['inclusiveRate'] ?? 0,          // ✅ NEW
                        $sd['lineDiscountWithTax'] ?? 0,    // ✅ NEW

                        $sd['unitId'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['amount'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['PurchaseRate'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['GodownId'],
                        $sd['RackId'],
                        $sd['branchId'],
                        $request->CreatedUser,
                        $branchNow
                    ];

                    $sql = 'SELECT * FROM public.insert_sales_return_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    $result = DB::selectOne($sql, $detailParams);

                    // add StockPosting
                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $sales->date,           // date
                        'Sales Return',        // voucherType
                        $sales->ReturnMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        0,                      // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $sd['qty'] + $sd['freeQty'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        ($sd['qty'] + $sd['freeQty']) * $sd['ConversionFactor'],         // inwardQty
                        0,            // outwardQty
                        $sd['PurchaseRate'],    // rate
                        $sd['baseUnitid'],   // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $sd['branchId'],        // branchId
                        $request->CreatedUser,  // CreatedUser
                        $branchNow
                    ]);
                }
            }


            // Insert Ledger Postings
            if ($sales->totalAmount > 0) {
                $createdDate = getBranchNow($branchId);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->ledgerId,          // ledgerId
                    0,                        // debit
                    $sales->totalAmount,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',           // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate

                ]);
            }

            // Compute Sales Credit Amount with roundOff logic
            if ($sales->totalAmount > 0) {
                $roundOff = $sales->roundOff ?? 0;

                if ($roundOff < 0) {
                    $salesCredit = $sales->totalAmount - $sales->totalTax - abs($roundOff);
                } else {
                    $salesCredit = $sales->totalAmount - $sales->totalTax + $roundOff;
                }
                $createdDate = getBranchNow($branchId);

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->salesAccountId,          // ledgerId
                    $salesCredit,                        // debit
                    0,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',           // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',                   // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $request->vatLedgerId,          // ledgerId
                    $sales->totalTax,                        // debit
                    0,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',           // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->CashLedgerId,          // ledgerId
                    0,                        // debit
                    $sales->CashAmount,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return Payment',           // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->BankLedgerId,          // ledgerId
                    0,                        // debit
                    $sales->BankAmount,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return Payment',           // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,
                    $createdDate
                ]);
            }

            // Sales Receipt Posting
            if ($sales->BankAmount > 0 || $sales->CashAmount > 0) {
                $createdDate = getBranchNow($branchId);

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->ledgerId,          // ledgerId
                    ($sales->BankAmount + $sales->CashAmount),    // debit
                    0,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return Payment', // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->CreatedUser,
                    $createdDate
                ]);
            }

            // Partybalance Posting
            $createdDate = getBranchNow($branchId);
            $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $sales->date,                     // date
                $sales->ledgerId,                 // ledgerId
                'Sales Return',                  // voucherType
                $sales->ReturnMasterId,             // voucherNo
                'NA',                           // againstVoucherType
                'NA',                           // againstvoucherNo
                'New',                           // referenceType
                0,         // debit
                $sales->BillBalanceAmount,      // credit
                $sales->creditPeriod,             // creditPeriod
                $sales->currencyConversionId,   // currencyConversionId
                $sales->returnNo,                 // invoiceNo
                $sales->partyRefNo,                  // referenceNo
                $sales->totalAmount,             // BillAmount
                $sales->date,                    // invoiceDate
                $sales->costCentreId,              // costCentreId
                $sales->exchangeRate,              // exchangeRate
                $sales->exchangeDate,              // exchangeDate
                $sales->branchId,                  // branchId
                $request->CreatedUser,              // CreatedUser
                $createdDate
            ]);
            $details = SalesReturnDetails1::where(
                'ReturnMasterId',
                $sales->ReturnMasterId
            )->get();

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Sales Returned Successfully',
                'data' => [
                    'ReturnMasterId'  => $sales->ReturnMasterId,
                    'returnNo'        => $sales->returnNo,
                    'qr_link'         => $sales->qr_link,
                    'prevInvoiceHash' => optional(
                        ZatcaRegistration::where('branchId', $sales->branchId)->first()
                    )->PrevInvoiceHash,

                    'payload' => [
                        'salesReturnMaster' => [
                            ...$sales->toArray(),
                            'salesDetails' => $details->toArray()
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
        $sales = SalesReturnMaster::find($id);

        if (!$sales) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Not Found',
            ], 404);
        }
        $oldAmount   = $sales->totalAmount;
        $oldDate     = $sales->date;
        $oldVoucher  = $sales->returnNo;
        $oldLedgerId = $sales->ledgerId;
        DB::beginTransaction();
        try {

            // Update sales return fields
            $masterParams = [
                $id,
                $request->date,
                $request->billTime,
                $request->salesMasterId,
                $request->ledgerId,
                $request->salesAccountId,
                $request->pricingLevelId,
                $request->employeeId,
                $request->costCentreId,
                $request->BatchId,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->partyName,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->lrNo,
                $request->transportCompany,
                $request->narration,
                $request->taxType,
                $request->taxableAmt,
                $request->subTotal,
                $request->totalTax,
                $request->additionalCost,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0),   // ✅ NEW (must be here)

                $request->roundOff,
                $request->totalAmount,
                $request->paymentMode,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->billDiscOnProductStatus,
                $request->AddCostonProductStatus,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->AgainstNo,
                $request->isVan,
                $request->vanExecutiveId,
                $request->preinvoiceHash,
                $request->currentinvoiceHash,
                $request->UUID,
                $request->Zatcastatus,
                $request->logdata,
                $request->qr_link,
                $request->branchId,
                $request->ModifiedUser,
                $request->reason,
                $request->GodownId,
                $request->creditPeriod,
                $request->formType
            ];

            $sql = 'SELECT public.update_sales_return_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ')';

            DB::select($sql, $masterParams);

            // IMPORTANT: refresh model (otherwise you keep using old values below)
            $sales->refresh();


            /**
             * Update salesDetails
             */
            DB::select('SELECT delete_sales_return_details(?)', [$sales->ReturnMasterId]);
            if ($request->has('salesDetails')) {
                foreach ($request->salesDetails as $sd) {
                    $detailParams = [
                        $sales->ReturnMasterId,
                        $sd['salesDetails1Id'],
                        $sd['productCode'],
                        $sd['productDescription'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],

                        $sd['inclusiveRate'] ?? 0,          // ✅ NEW
                        $sd['lineDiscountWithTax'] ?? 0,    // ✅ NEW

                        $sd['unitId'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['amount'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['PurchaseRate'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['GodownId'],
                        $sd['RackId'],
                        $sd['branchId'],
                        $request->CreatedUser
                    ];

                    $sql = 'SELECT * FROM public.insert_sales_return_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    $result = DB::selectOne($sql, $detailParams);


                    // add StockPosting
                    DB::select('SELECT delete_stock_posting(?, ?)', [
                        'Sales Return', // voucherType
                        $sales->ReturnMasterId  // voucherNo
                    ]);

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $sales->date,           // date
                        'Sales Return',        // voucherType
                        $sales->ReturnMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        0,                      // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $sd['qty'] + $sd['freeQty'],    // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        0,                      // inwardQty
                        ($sd['qty'] + $sd['freeQty']) * $sd['ConversionFactor'],     // outwardQty
                        $sd['PurchaseRate'],    // rate
                        $request->baseUnitid,   // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $sd['branchId'],        // branchId
                        $request->ModifiedUser,  // CreatedUser
                    ]);
                }
            }

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Sales Return', // voucherType
                $sales->ReturnMasterId, // voucherNo
                $request->branchId   // branchId
            ]);

            // Insert Ledger Postings
            if ($sales->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->ledgerId,          // ledgerId
                    0,                        // debit
                    $sales->totalAmount,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',           // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Compute Sales Credit Amount with roundOff logic
            if ($sales->totalAmount > 0) {
                $roundOff = $sales->roundOff ?? 0;

                if ($roundOff < 0) {
                    $salesCredit = $sales->totalAmount - $sales->totalTax - abs($roundOff);
                } else {
                    $salesCredit = $sales->totalAmount - $sales->totalTax + $roundOff;
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $sales->date,           // date
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->salesAccountId,          // ledgerId
                    $salesCredit,                        // debit
                    0,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',           // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',                   // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $request->vatLedgerId,          // ledgerId
                    $sales->totalTax,                        // debit
                    0,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return',           // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->CashLedgerId,          // ledgerId
                    0,                        // debit
                    $sales->CashAmount,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return Payment',           // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->BankLedgerId,          // ledgerId
                    0,                        // debit
                    $sales->BankAmount,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return Payment',           // PostingType
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
                    'Sales Return',         // voucherType
                    $sales->ReturnMasterId,  // voucherNo
                    $sales->ledgerId,          // ledgerId
                    ($sales->BankAmount + $sales->CashAmount),    // debit
                    0,    // credit
                    $sales->returnNo,         // InvoiceNo
                    $sales->narration,         // Narration
                    $sales->costCentreId,      // costCentreId
                    'Sales Return Payment', // PostingType
                    $sales->exchangeRate,      // exchangeRate
                    $sales->exchangeDate,      // exchangeDate
                    $sales->branchId,          // branchId
                    $request->ModifiedUser   // CreatedUser
                ]);
            }

            // Partybalance Posting
            DB::select('SELECT delete_partybalance(?, ?)', [
                'Sales Return', // voucherType
                $sales->ReturnMasterId // voucherNo
            ]);

            // Partybalance Posting
            $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $sales->date,                     // date
                $sales->ledgerId,                 // ledgerId
                'Sales Return',                  // voucherType
                $sales->ReturnMasterId,             // voucherNo
                'NA',                           // againstVoucherType
                'NA',                           // againstvoucherNo
                'New',                           // referenceType
                0,         // debit
                $sales->BillBalanceAmount,      // credit
                $sales->creditPeriod,             // creditPeriod
                $sales->currencyConversionId,   // currencyConversionId
                $sales->returnNo,                 // invoiceNo
                $sales->partyRefNo,              // referenceNo
                $sales->totalAmount,             // BillAmount
                $sales->date,                    // invoiceDate
                $sales->costCentreId,              // costCentreId
                $sales->exchangeRate,              // exchangeRate
                $sales->exchangeDate,              // exchangeDate
                $sales->branchId,                  // branchId
                $request->ModifiedUser              // CreatedUser
            ]);
            ActivityHelper::logActivity(
                $request->employeeId ?? null,
                'Sales Return Update',
                'Sales Return',
                'UPDATED',
                $request->branchId,
                $request->ModifiedUser ?? 'System',
                $sales->ReturnMasterId,
                $sales->returnNo ?? $oldVoucher,
                $request->date ?? $oldDate,
                $oldAmount,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Return Updated Successfully'
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
    public function destroy($id, $branchId)
    {
        $sales = SalesReturnMaster::find($id);

        if (!$sales) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Not Found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $oldAmount = $sales->totalAmount;

            DB::select('SELECT delete_sales_return_details(?)', [$id]);
            DB::statement('SELECT public.delete_sales_return_master_byid(?)', [$id]);

            DB::select('SELECT delete_stock_posting(?, ?)', ['Sales Return', $id]);

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Sales Return', // voucherType
                $id, // voucherNo
                $branchId   // branchId
            ]);

            DB::select('SELECT delete_partybalance(?, ?)', [
                'Sales Return', // voucherType
                $id // voucherNo
            ]);
            ActivityHelper::logActivity(
                $sales->employeeId ?? null,
                'Sales Return Delete',
                'Sales Return',
                'DELETED',
                $sales->branchId,
                $sales->CreatedUser ?? 'System',
                $sales->ReturnMasterId,
                $sales->returnNo ?? null,
                $sales->date ?? null,
                $oldAmount,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Return Deleted Successfully',
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
     * Get salesDataToReturn by id
     */
    public function salesDataToReturn($id)
    {
        $salesMaster = DB::select('SELECT * FROM public.get_sales_master_data_byid(?)', [$id]);

        if (empty($salesMaster)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Not Found',
            ], 404);
        }

        $sales = (array) $salesMaster[0];

        // Get Sales Details Data
        $salesDetails = DB::select('SELECT * FROM public.get_sales_details1_by_sales_master_id(?)', [$id]);
        $salesDetails = collect($salesDetails);

        // Get All Sales Return Masters linked to this Sales
        $salesReturnMasters = DB::select('SELECT * FROM public.get_sales_return_master_list_for_return() WHERE "salesMasterId" = ?', [$id]);
        $salesReturnMasters = collect($salesReturnMasters);

        // Build Return Qty Map (salesDetails1Id → total returned qty)
        $returnQtyMap = collect();

        foreach ($salesReturnMasters as $returnMaster) {
            $returnMasterId = $returnMaster->ReturnMasterId;

            $salesReturnDetails = DB::select('SELECT * FROM public.get_sales_return_details1_by_return_master_id(?)', [$returnMasterId]);

            foreach ($salesReturnDetails as $returnDetail) {
                $sid = $returnDetail->salesDetails1Id;
                $returnQtyMap[$sid] = ($returnQtyMap[$sid] ?? 0) + (float) $returnDetail->qty;
            }

            $returnMaster->salesReturnDetails = $salesReturnDetails;
        }

        // Calculate remaining (balance) qty in Sales Details with product info
        $salesDetailsWithBalance = $salesDetails->map(function ($detail) use ($returnQtyMap, $sales) {
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

            // Get mrp from tbl_ProductSalesPrice
            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', $detail['unitId'])
                ->where('branchId', $sales['branchId'])
                ->value('amount');

            // Calculate balance qty
            $returnedQty = $returnQtyMap[$detail['salesDetails1Id']] ?? 0;
            $balanceQty  = (float) $detail['qty'] - $returnedQty;

            $detail['returnedQty'] = $returnedQty;
            $detail['balanceQty']  = $balanceQty;
            $detail['qty']         = $balanceQty;

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => $product->brand ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        })->filter(function ($detail) {
            return $detail['qty'] > 0;
        })->values();

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message' => 'Sales Retrieved Successfully',
            'data' => [
                ...$sales,
                'salesDetailData'       => $salesDetailsWithBalance,
                'salesReturnMasterData' => $salesReturnMasters,
            ],
        ]);
    }



    public function getInvoicesForReturn(Request $request)
    {
        $ledgerId       = $request->ledgerId ?? 'All';
        $branchId       = (int) $request->branchId;
        $returnMasterId = $request->ReturnMasterId ?? 1;
        $isAccountsPosting = (int) $request->isAccountsPosting ?? 0;

        $result = DB::select('SELECT * FROM public.get_invoices_for_return(?, ?, ?, ?)', [
            $ledgerId,
            $branchId,
            $returnMasterId,
            $isAccountsPosting
        ]);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Sales Invoices Retrieved Successfully',
            'data'    => $result,
        ]);
    }

    public function salesReturnDetailed(Request $request)
    {
        try {


            $data = DB::select(
                'SELECT * FROM public."sales_return_report_fill_detailed"(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->isAccountsPosting ?? false,
                    $request->ledgerId ?? 0,
                    $request->salesMasterId ?? 0,
                    $request->branchId,
                    $request->salesManId ?? 0,
                    $request->costCentreId ?? 0,
                    $request->isPosted ?? false,
                    $request->currencyId ?? 0,
                    $request->userId ?? 0,
                    $request->isCancelled ?? false,
                    $request->taxType ?? 'GST'
                ]
            );


            if (empty($data)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No data found',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Sales return detailed report',
                'count'   => count($data),
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function salesSummaryReport(Request $request)
    {
        try {
            $reportType = $request->reportType;
            $branchId = $request->branchId ?? null;
            $fromDate = $request->fromDate;
            $toDate = $request->toDate;
            $ledgerId = $request->ledgerId;
            $finacUserId = $request->finacUserId;
            $posUserId = $request->posUserId;
            $posCounterId = $request->posCounterId;
            $salesmanId = $request->salesmanId;
            $isAccountsPosting = $request->isAccountsPosting;
            $currencyId = $request->currencyId;
            $data = DB::select('SELECT * FROM public."sales_summary_report"( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $reportType,
                $branchId,
                $fromDate,
                $toDate,
                $ledgerId ?: null,
                $finacUserId ?: null,
                $posUserId ?: null,
                $posCounterId ?: null,
                $salesmanId ?: null,
                $isAccountsPosting,
                $currencyId
            ]);
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Sales summary report fetched successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function SalesManWiseReport(Request $request)
    {
        try {
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $employee_id = $request->employee_id;
            $mode = $request->mode;
            $branch_id = $request->branch_id;
            $currency_id = $request->currency_id;
            $brand_id = $request->brand_id;
            $is_accounts_posting = $request->is_accounts_posting;

            $data = DB::select('SELECT * FROM public."sales_man_wise_sales_report_fill"(?, ?, ?, ?, ?, ?, ?, ?)', [
                $from_date,
                $to_date,
                $employee_id,
                $mode,
                $branch_id,
                $currency_id,
                $brand_id,
                $is_accounts_posting,

            ]);
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Sales man wise sales report fetched successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function SalesManWiseSalesOrderReport(Request $request)
    {
        try {
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            $employee_id = $request->employee_id;
            $mode = $request->mode;
            $branch_id = $request->branch_id;
            $currency_id = $request->currency_id;
            $brand_id = $request->brand_id;
            $is_accounts_posting = $request->is_accounts_posting;

            $data = DB::select('SELECT * FROM public."sales_man_wise_sales_order_report_fill"(?, ?, ?, ?, ?, ?, ?, ?)', [
                $from_date,
                $to_date,
                $employee_id,
                $mode,
                $branch_id,
                $currency_id,
                $brand_id,
                $is_accounts_posting,

            ]);
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Sales man wise sales order report fetched successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function SalesReturnSummaryReport(Request $request)
    {
        try {
            $branchId = $request->branchId;
            $ledgerId = $request->ledgerId;
            $fromDate = $request->fromDate;
            $toDate = $request->toDate;
            $salesManId = $request->salesManId;
            $finacUserId = $request->finacUserId;
            $reportType = $request->reportType ?? 'All';
            $isAccountsPosting = $request->is_accounts_posting ?? false;

            $data = DB::select('SELECT * FROM public."sales_return_summary_report"(?, ?, ?, ?, ?, ?, ?, ?)', [
                $branchId,
                $ledgerId,
                $fromDate,
                $toDate,
                $salesManId,
                $finacUserId,
                $reportType,
                $isAccountsPosting

            ]);
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Sales return summary  report fetched successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function salesDataToReturnbyinvoiceNo($id)
    {
        $salesMaster = DB::select('SELECT * FROM public.get_sales_master_data_by_invoiceno(?)', [$id]);

        if (empty($salesMaster)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Not Found',
            ], 404);
        }

        $sales = (array) $salesMaster[0];
        // dd($sales);

        // Get Sales Details Data
        $salesDetails = DB::select('SELECT * FROM public.get_sales_details1_by_sales_master_id(?)', [$sales['salesMasterId']]);
        $salesDetails = collect($salesDetails);

        // Get All Sales Return Masters linked to this Sales
        $salesReturnMasters = DB::select('SELECT * FROM public.get_sales_return_master_list_for_return() WHERE "salesMasterId" = ?', [$sales['salesMasterId']]);
        $salesReturnMasters = collect($salesReturnMasters);

        // Build Return Qty Map (salesDetails1Id → total returned qty)
        $returnQtyMap = collect();

        foreach ($salesReturnMasters as $returnMaster) {
            $returnMasterId = $returnMaster->ReturnMasterId;

            $salesReturnDetails = DB::select('SELECT * FROM public.get_sales_return_details1_by_return_master_id(?)', [$returnMasterId]);

            foreach ($salesReturnDetails as $returnDetail) {
                $sid = $returnDetail->salesDetails1Id;
                $returnQtyMap[$sid] = ($returnQtyMap[$sid] ?? 0) + (float) $returnDetail->qty;
            }

            $returnMaster->salesReturnDetails = $salesReturnDetails;
        }

        // Calculate remaining (balance) qty in Sales Details with product info
        $salesDetailsWithBalance = $salesDetails->map(function ($detail) use ($returnQtyMap, $sales) {
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

            // Get mrp from tbl_ProductSalesPrice
            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', $detail['unitId'])
                ->where('branchId', $sales['branchId'])
                ->value('amount');

            // Calculate balance qty
            $returnedQty = $returnQtyMap[$detail['salesDetails1Id']] ?? 0;
            $balanceQty  = (float) $detail['qty'] - $returnedQty;

            $detail['returnedQty'] = $returnedQty;
            $detail['balanceQty']  = $balanceQty;
            $detail['qty']         = $balanceQty;

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => $product->brand ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        })->filter(function ($detail) {
            return $detail['qty'] > 0;
        })->values();

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message' => 'Sales Retrieved Successfully',
            'data' => [
                ...$sales,
                'salesDetailData'       => $salesDetailsWithBalance,
                'salesReturnMasterData' => $salesReturnMasters,
            ],
        ]);
    }
    public function SaleSummaryReportData(Request $request)
    {
        try {

            $result = DB::select("
                SELECT *
                FROM public.report_sales_summary_report(
                    :reportType,
                    :branchId,
                    :fromDate,
                    :toDate,
                    :currencyId,
                    :ledgerId,
                    :salesmanId,
                    :isAccountsPosting
                )
            ", [
                'reportType'       => $request->reportType,
                'branchId'         => $request->branchId,
                'fromDate'         => $request->fromDate,
                'toDate'           => $request->toDate,
                'currencyId'       => $request->currencyId,
                'ledgerId'         => $request->ledgerId ?? 'All',
                'salesmanId'       => $request->salesmanId ?? 'All',
                'isAccountsPosting' => filter_var($request->isAccountsPosting, FILTER_VALIDATE_BOOLEAN)
            ]);

            return response()->json([
                'success' => true,
                'count'   => count($result),
                'data'    => $result
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}