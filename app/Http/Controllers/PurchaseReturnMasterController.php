<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\Product;
use App\Models\PurchaseReturnMaster;
use App\Models\PurchaseReturnDetails1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnMasterController extends Controller
{
    /**
     * List Purchase Return
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $purchase = DB::select('SELECT * FROM public.get_all_purchasereturn_master(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($purchase);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Return Retrieved Successfully',
            'data' => $paginated,
            'meta'    => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($total / $limit),
            ]
        ]);
    }

    /**
     * Get Purchase Return by id
     */
    public function show($id)
    {
        $purchase = DB::select('SELECT * FROM public.get_purchasereturn_master_byid(?)', [$id]);

        if (empty($purchase)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Return Not Found',
            ], 404);
        }

        $purchase = (array) $purchase[0];

        $purchaseDetails = DB::select('SELECT * FROM public.get_purchasereturn_details_byid(?)', [$purchase['returnMasterId']]);

        $purchaseDetails = array_map(function ($item) {
            $item = (array) $item;

            if (!empty($item['productCode'])) {
                $product = Product::with('salesPrices')
                    ->where('productCode', $item['productCode'])
                    ->first();

                $item['salesPrices'] = $product
                    ? $product->salesPrices->toArray()
                    : [];
            } else {
                $item['salesPrices'] = [];
            }

            return $item;
        }, $purchaseDetails);
        $purchase['purchaseDetails'] = $purchaseDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Return Retrieved Successfully',
            'data' => $purchase,
        ], 200);
    }


    /**
     * Create Purchase Return
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);
            // Insert Purchase Return
            $result = DB::select('SELECT insert_purchasereturn_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS out_returnMasterId', [
                $codeData['table_pk'],  // returnMasterId
                $codeData['suffixPrefixId'],  // suffixPrefixId
                $codeData['voucherNo'],  // voucherNo
                $codeData['voucherCode'],  // returnNo
                $request->date,
                $request->purchaseMasterId,
                $request->againstNo,
                $request->ledgerId,
                $request->purchaseAccount,
                $request->narration,
                $request->costCentreId,
                $request->BatchId,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->taxType,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,
                $request->othercharge,
                $request->taxableAmt,
                $request->roundOff,
                $request->totalTax,
                $request->totalAmount,
                $request->otherChargeLedgerId,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->transportCompany,
                $request->paymentMode,
                $request->partyName,
                $request->partyAddress,
                $request->partyPhone,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $createdDate,
                $request->CreatedUser,
                NULL, // ModifiedDate
                NULL,  // ModifiedUser
                $request->GodownId    // GodownId
            ]);

            // dd($result);
            $purchase = PurchaseReturnMaster::find($result[0]->out_returnmasterid);

            // Insert into purchaseDetails
            if ($request->has('purchaseDetails')) {
                foreach ($request->purchaseDetails as $sd) {
                    $result = DB::select('SELECT insert_purchasereturn_details1(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS id', [
                        (PurchaseReturnDetails1::max('returnDetails1Id') ?? 0) + 1,
                        $purchase->returnMasterId,
                        $sd['purchaseDetails1Id'],
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],
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
                        $sd['billDiscOnProduct'],
                        $sd['addCostOnProduct'],
                        $sd['salesRate'],
                        $sd['otherChargeOnProduct'],
                        $sd['productDescription'],
                        $sd['godownId'],
                        $sd['rackId'],
                        $request->branchId,
                        $createdDate,
                        $request->CreatedUser,
                        NULL, // ModifiedDate
                        NULL  // ModifiedUser
                    ]);


                    // add StockPosting
                    $outwardQty = ($sd['qty'] + $sd['freeQty']) * $sd['ConversionFactor'];
                    $voucherQty = $sd['qty'] + $sd['freeQty'];

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $purchase->date,           // date
                        'Purchase Return',        // voucherType
                        $purchase->returnMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $purchase->BatchId,     // batchId
                        $sd['godownId'],        // godownId
                        $sd['rackId'],          // rackId
                        $voucherQty,            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        0,                      // inwardQty
                        $outwardQty,            // outwardQty
                        $sd['rate'],            // rate
                        $sd['unitId'],         // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $request->branchId,      // branchId
                        $request->CreatedUser,  // CreatedUser
                        $createdDate
                    ]);
                }
            }

            if ($purchase->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $purchase->ledgerId,  // ledgerId
                    $purchase->totalAmount,    // debit
                    0,                      // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->totalAmount > 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff < 0) {
                    $purchaseCredit = $purchase->totalAmount - $purchase->totalTax - abs($roundOff);
                } else {
                    $purchaseCredit = $purchase->totalAmount - $purchase->totalTax + $roundOff;
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $purchase->purchaseAccount,  // ledgerId
                    0,                      // debit
                    $purchaseCredit,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // roundOff Posting
            if ($purchase->roundOff != 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff > 0) {
                    $credit = 0;
                    $debit = $roundOff;
                } else {
                    $debit = 0;
                    $credit = abs($roundOff);
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->totalTax,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->othercharge > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->otherChargeLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->othercharge,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    $purchase->CashAmount, // DEBIT
                    0,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    $purchase->BankAmount, // DEBIT
                    0,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($purchase->BankAmount > 0 || $purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    0,                      // debit
                    ($purchase->BankAmount + $purchase->CashAmount),            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }


            // Partybalance Posting
            $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $purchase->date,                     // date
                $purchase->ledgerId,                 // ledgerId
                'Purchase Return',                  // voucherType
                $purchase->returnMasterId,             // voucherNo
                'NA',                           // againstVoucherType
                'NA',                           // againstvoucherNo
                'New',                           // referenceType
                $purchase->BillBalanceAmount,         // debit
                0,                              // credit
                $request->creditPeriod,             // creditPeriod
                $purchase->currencyConversionId,   // currencyConversionId
                $purchase->voucherNo,                 // invoiceNo
                $purchase->partyRefNo,                  // referenceNo
                $purchase->totalAmount,             // BillAmount
                $purchase->date,                    // invoiceDate
                $purchase->costCentreId,              // costCentreId
                $purchase->exchangeRate,              // exchangeRate
                $purchase->exchangeDate,              // exchangeDate
                $purchase->branchId,                  // branchId
                $request->CreatedUser,              // CreatedUser
                $createdDate
            ]);
            $master = PurchaseReturnMaster::find($purchase->returnMasterId);

            $details = PurchaseReturnDetails1::where(
                'returnMasterId',
                $purchase->returnMasterId
            )->get();



            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Return insert',
                'Purchase Return',
                'INSERTED',
                $purchase->branchId,
                auth()->user()->name ?? 'System',
                $purchase->returnMasterId,
                $purchase->voucherNo,
                $purchase->date,
                null,
                $purchase->totalAmount
            );
            DB::commit();

            return response()->json([
                'status' => 201,
                'error'  => false,
                'message' => 'Purchase Return Inserted Successfully',
                'data' => [
                    'returnMasterId' => $master->returnMasterId,
                    'returnNo'       => $master->returnNo,

                    'payload' => [
                        'purchaseReturnMaster' => [
                            ...$master->toArray(),
                            'purchaseDetails' => $details->toArray()
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
     * Update Purchase Return
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $purchase = PurchaseReturnMaster::find($id);

        if (!$purchase) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Return Not Found',
            ], 404);
        }
        $oldValue = $purchase->totalAmount;
        DB::beginTransaction();
        try {
            $branchId = $request->input('branchId', 1);
            $modifiedDate = getBranchNow($branchId);
            // Update Purchase Return
            DB::select('SELECT update_purchasereturn_master(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $id,
                $request->date,
                $request->purchaseMasterId,
                $request->AgainstNo,
                $request->ledgerId,
                $request->purchaseAccount,
                $request->narration,
                $request->costCentreId,
                $request->BatchId,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->taxType,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,
                $request->othercharge,
                $request->taxableAmt,
                $request->roundOff,
                $request->totalTax,
                $request->totalAmount,
                $request->otherChargeLedgerId,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->transportCompany,
                $request->paymentMode,
                $request->partyName,
                $request->partyAddress,
                $request->partyPhone,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser,
                $request->GodownId
            ]);

            $originalCreatedDate = $purchase->CreatedDate ?? $purchase->created_at;
            $originalCreatedUser = $purchase->CreatedUser ?? $purchase->created_user;
            if ($request->has('purchaseDetails')) {
                DB::select('SELECT delete_purchasereturn_details1_byid(?)', [$purchase->returnMasterId]);

                DB::select('SELECT delete_stock_posting(?, ?)', [
                    'Purchase Return', // voucher_type
                    $purchase->returnMasterId // voucherNo
                ]);

                foreach ($request->purchaseDetails as $sd) {
                    $result = DB::select('SELECT insert_purchasereturn_details1(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS id', [
                        (PurchaseReturnDetails1::max('returnDetails1Id') ?? 0) + 1,
                        $purchase->returnMasterId,
                        $sd['purchaseDetails1Id'],
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],
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
                        $sd['billDiscOnProduct'],
                        $sd['addCostOnProduct'],
                        $sd['salesRate'],
                        $sd['otherChargeOnProduct'],
                        $sd['productDescription'],
                        $sd['godownId'],
                        $sd['rackId'],
                        $request->branchId,
                        $originalCreatedDate,
                        $originalCreatedUser,
                        $modifiedDate, // ModifiedDate
                        $request->ModifiedUser  // ModifiedUser
                    ]);


                    // add StockPosting
                    $outwardQty = ($sd['qty'] + $sd['freeQty']) * $sd['ConversionFactor'];
                    $voucherQty = $sd['qty'] + $sd['freeQty'];

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $purchase->date,           // date
                        'Purchase Return',        // voucherType
                        $purchase->returnMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $purchase->BatchId,     // batchId
                        $sd['godownId'],        // godownId
                        $sd['rackId'],          // rackId
                        $voucherQty,            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        0,                      // inwardQty
                        $outwardQty,            // outwardQty
                        $sd['rate'],            // rate
                        $sd['unitId'],         // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $request->branchId,      // branchId
                        $request->ModifiedUser,  // CreatedUser
                        $modifiedDate
                    ]);
                }
            }

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Purchase Return', // voucherType
                $purchase->returnMasterId, // voucherNo
                $request->branchId   // branchId
            ]);

            if ($purchase->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $purchase->ledgerId,  // ledgerId
                    $purchase->totalAmount,    // debit
                    0,                      // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->totalAmount > 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff < 0) {
                    $purchaseCredit = $purchase->totalAmount - $purchase->totalTax - abs($roundOff);
                } else {
                    $purchaseCredit = $purchase->totalAmount - $purchase->totalTax + $roundOff;
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $purchase->purchaseAccount,  // ledgerId
                    0,                      // debit
                    $purchaseCredit,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            // roundOff Posting
            if ($purchase->roundOff != 0) {
                $roundOff = $purchase->roundOff ?? 0;

                if ($roundOff > 0) {
                    $credit = 0;
                    $debit = $roundOff;
                } else {
                    $debit = 0;
                    $credit = abs($roundOff);
                }

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    29,   // Round Off ledgerId
                    $debit,                      // debit
                    $credit,                    // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->totalTax,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->otherCharge > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->otherChargeLedgerId,  // ledgerId
                    0,                      // debit
                    $purchase->otherCharge,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return',                   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    $purchase->CashAmount, // DEBIT
                    0,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    $purchase->BankAmount, // DEBIT
                    0,            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($purchase->BankAmount > 0 || $purchase->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $purchase->date,           // date
                    'Purchase Return',         // voucherType
                    $purchase->returnMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    0,                      // debit
                    ($purchase->BankAmount + $purchase->CashAmount),            // credit
                    $purchase->voucherNo,         // InvoiceNo
                    $purchase->narration,         // Narration
                    $purchase->costCentreId,      // costCentreId
                    'Purchase Return Payment',   // PostingType
                    $purchase->exchangeRate,      // exchangeRate
                    $purchase->exchangeDate,      // exchangeDate
                    $purchase->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }


            // Partybalance Posting
            DB::select('SELECT delete_partybalance(?, ?)', [
                'Purchase Return', // voucherType
                $purchase->returnMasterId // voucherNo
            ]);
            $result = DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $purchase->date,                     // date
                $purchase->ledgerId,                 // ledgerId
                'Purchase Return',                  // voucherType
                $purchase->returnMasterId,             // voucherNo
                'NA',                           // againstVoucherType
                'NA',                           // againstvoucherNo
                'New',                           // referenceType
                $purchase->BillBalanceAmount,         // debit
                0,                              // credit
                $request->creditPeriod,             // creditPeriod
                $purchase->currencyConversionId,   // currencyConversionId
                $purchase->voucherNo,                 // invoiceNo
                $purchase->partyRefNo,                  // referenceNo
                $purchase->totalAmount,             // BillAmount
                $purchase->date,                    // invoiceDate
                $purchase->costCentreId,              // costCentreId
                $purchase->exchangeRate,              // exchangeRate
                $purchase->exchangeDate,              // exchangeDate
                $purchase->branchId,                  // branchId
                $request->ModifiedUser,              // CreatedUser
                $modifiedDate

            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Return Update',
                'Purchase Return',
                'UPDATED',
                $purchase->branchId,
                auth()->user()->name ?? 'System',
                $purchase->returnMasterId,
                $purchase->voucherNo,
                $purchase->date,
                $oldValue,
                $purchase->totalAmount
            );


            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Return Updated Successfully'
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
     * Delete Purchase Return
     */
    public function destroy($id)
    {
        $purchase = PurchaseReturnMaster::find($id);

        if (!$purchase) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Return Not Found',
            ], 404);
        }
        $oldValue = $purchase->totalAmount;

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_purchasereturn_details1_byid(?)', [$id]);

            DB::statement('SELECT public.delete_purchasereturn_master_byid(?)', [$id]);

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Purchase Return', // voucherType
                $purchase->returnMasterId, // voucherNo
                null   // branchId
            ]);

            DB::select('SELECT delete_partybalance(?, ?)', [
                'Purchase Return', // voucherType
                $purchase->returnMasterId // voucherNo
            ]);

            DB::select('SELECT delete_stock_posting(?, ?)', [
                'Purchase Return', // voucher_type
                $purchase->returnMasterId // voucherNo
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Return Delete',
                'Purchase Return',
                'DELETED',
                $purchase->branchId,
                auth()->user()->name ?? 'System',
                $purchase->returnMasterId,
                $purchase->voucherNo,
                $purchase->date,
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Return Deleted Successfully',
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

    public function PurchaseReturnReport(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public."purchase_return_report_fill"(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                )',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->ledgerId,
                    $request->purchaseMasterId,
                    $request->branchId,
                    $request->currencyId,
                    $request->userId,
                    $request->costCentreId,
                    $request->isAccountsPosting
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data'   => $data,
                'message' => 'Purchase return report summary fetched Successully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function PurchaseReturnReportDetailed(Request $request)
    {
        try {

            $data = DB::select(
                'SELECT * FROM public."purchase_return_report_fill_detail"(
                ?::timestamp,
                ?::timestamp,
                ?::boolean,
                ?::integer,
                ?::integer,
                ?::integer,
                ?::bigint,
                ?::boolean,
                ?::varchar
            )',
                [
                    $request->from_date,
                    $request->to_date,
                    $request->optional ?? false,
                    $request->branch_id,
                    $request->ledger_id ?? null,
                    $request->purchase_master_id ?? null,
                    $request->cost_centre_id ?? null,
                    $request->is_accounts_posting ?? false,
                    $request->mode ?? 'Detailed'
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Purchase return report fetched successfully'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}