<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\PayableMaster;
use App\Models\PayableDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableMasterController extends Controller
{
    /**
     * List Payable Voucher
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $payable = DB::select('SELECT * FROM public.get_all_payable_master(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($payable);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Payable Voucher Retrieved Successfully',
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
     * Get Payable Voucher by id
     */
    public function show($id)
    {
        $payable = DB::select('SELECT * FROM public.get_payable_master_byid(?)', [$id]);

        // Check if found
        if (empty($payable)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Payable Voucher Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $payable = (array) $payable[0];

        // Step 2: Get Receipt details using receiptMasterId
        $payableDetails = DB::select('SELECT * FROM public.get_payable_details_byid(?)', [$payable['PayableMasterId']]);

        // Step 3: Attach Receipt details to master data
        $payable['payableDetails'] = $payableDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Payable Voucher Retrieved Successfully',
            'data' => $payable,
        ], 200);
    }


    /**
     * Create Payable Voucher
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

            // Insert Payable Voucher
            $result = DB::select(
                'SELECT insert_payable_master(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                ) AS out_PayableMasterId',
                [
                    $codeData['table_pk'],                     // PayableMasterId
                    $codeData['voucherNo'],                    // voucherNo
                    $codeData['suffixPrefixId'],               // suffixPrefixId
                    $codeData['voucherCode'],                  // PayableNo
                    $request->date,                         // date
                    $request->ledgerId,                     // ledgerId
                    $request->partyName,                    // partyName
                    $request->partyAddress,                 // partyAddress
                    $request->partyPhone,                   // partyPhone
                    $request->partyVatNo,                   // partyVatNo
                    $request->narration,                    // narration
                    $request->totalTax,                     // totalTax
                    $request->totalAmount,                  // totalAmount
                    $request->costCentreId,                 // costCentreId
                    $request->ReferenceNo,                  // ReferenceNo
                    $request->ReferenceDate,                // ReferenceDate
                    $request->paymentMode,                  // paymentMode
                    $request->CashLedgerId,                 // CashLedgerId
                    $request->CashRefNo,                    // CashRefNo
                    $request->CashAmount,                   // CashAmount
                    $request->BankLedgerId,                 // BankLedgerId
                    $request->BankRefNo,                    // BankRefNo
                    $request->BankAmount,                   // BankAmount
                    $request->BillBalanceAmount,            // BillBalanceAmount
                    $request->taxType,                      // taxType
                    $request->currencyConversionId,         // currencyConversionId
                    $request->exchangeRate,                 // exchangeRate
                    $request->exchangeDate,                 // exchangeDate
                    $request->employeeId,                   // employeeId
                    $request->postedStatus,                 // postedStatus
                    $request->postedBy,                     // postedBy
                    $request->postedDate,                   // postedDate
                    $request->branchId,                     // branchId
                    $createdDate,    // CreatedDate
                    $request->CreatedUser,                  // CreatedUser
                    null,                                   // ModifiedDate
                    null                                    // ModifiedUser
                ]
            );

            // dd($result);
            $payable = PayableMaster::find($result[0]->out_payablemasterid);

            // Insert into payableDetails
            if ($request->has('payableDetails')) {
                foreach ($request->payableDetails as $sd) {
                    $result = DB::select('SELECT insert_payable_details(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS "payableDetailsId"', [
                        (PayableDetails::max('payableDetailsId') ?? 0) + 1,
                        $payable->PayableMasterId,
                        $sd['ledgerId'],
                        $sd['grossAmount'],
                        $sd['discountPercentage'],
                        $sd['netAmount'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['amount'],
                        $sd['Narration'],
                        $sd['chequeNo'],
                        $sd['chequeDate'],
                        $sd['lineIndex'],
                        $request->branchId,
                        $createdDate,
                        $request->CreatedUser,
                        NULL, // ModifiedDate
                        NULL  // ModifiedUser
                    ]);


                    if ($sd['amount'] > 0) {
                        
                        $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                            $payable->date,           // date
                            'Payable Voucher',         // voucherType
                            $payable->PayableMasterId,  // voucherNo
                            $sd['ledgerId'],        // ledgerId
                            $sd['amount'] - $sd['taxAmount'], // debit
                            0,                          // credit
                            $payable->voucherNo,         // InvoiceNo
                            $payable->narration,         // Narration
                            $payable->costCentreId,      // costCentreId
                            'Payable',                   // PostingType
                            $payable->exchangeRate,      // exchangeRate
                            $payable->exchangeDate,      // exchangeDate
                            $payable->branchId,          // branchId
                            $request->CreatedUser,   // CreatedUser
                            $createdDate
                        ]);
                    }
                }
            }

            if ($payable->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $payable->ledgerId,  // ledgerId
                    0,                          // debit
                    $payable->totalAmount,      // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable',                   // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($payable->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    $payable->totalTax,         // debit
                    0,                          // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable',                   // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($payable->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    0,                      // debit
                    $payable->CashAmount,            // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable Payment',   // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($payable->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    0,                      // debit
                    $payable->BankAmount,            // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable Payment',          // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if (($payable->BankAmount + $payable->CashAmount) > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    ($payable->BankAmount + $payable->CashAmount),                      // debit
                    0,                          // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable Payment',          // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Payable Voucher Inserted Successfully'
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
     * Update Payable Voucher
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $payable = PayableMaster::find($id);

        if (!$payable) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Payable Voucher Not Found',
            ], 404);
        }
        $oldValue = $payable->totalAmount;

        DB::beginTransaction();
        try {
            $branchId   = $request->branchId;
            $modifiedDate = getBranchNow($branchId);
            // Update Payable Voucher
            DB::select('SELECT update_payable_master(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $id,
                $request->date,                         // date
                $request->ledgerId,                     // ledgerId
                $request->partyName,                    // partyName
                $request->partyAddress,                 // partyAddress
                $request->partyPhone,                   // partyPhone
                $request->partyVatNo,                   // partyVatNo
                $request->narration,                    // narration
                $request->totalTax,                     // totalTax
                $request->totalAmount,                  // totalAmount
                $request->costCentreId,                 // costCentreId
                $request->ReferenceNo,                  // ReferenceNo
                $request->ReferenceDate,                // ReferenceDate
                $request->paymentMode,                  // paymentMode
                $request->CashLedgerId,                 // CashLedgerId
                $request->CashRefNo,                    // CashRefNo
                $request->CashAmount,                   // CashAmount
                $request->BankLedgerId,                 // BankLedgerId
                $request->BankRefNo,                    // BankRefNo
                $request->BankAmount,                   // BankAmount
                $request->BillBalanceAmount,            // BillBalanceAmount
                $request->taxType,                      // taxType
                $request->currencyConversionId,         // currencyConversionId
                $request->exchangeRate,                 // exchangeRate
                $request->exchangeDate,                 // exchangeDate
                $request->employeeId,                   // employeeId
                $request->postedStatus,                 // postedStatus
                $request->postedBy,                     // postedBy
                $request->postedDate,                   // postedDate
                $request->branchId,                     // branchId
                $modifiedDate,
                $request->ModifiedUser
            ]);

            $payable->refresh();

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Payable Voucher', // voucherType
                $payable->PayableMasterId, // voucherNo
                $request->branchId   // branchId
            ]);

            if ($request->has('payableDetails')) {
                DB::select('SELECT delete_payable_details_byid(?)', [$payable->PayableMasterId]);

                foreach ($request->payableDetails as $sd) {
                    $result = DB::select('SELECT insert_payable_details(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS "payableDetailsId"', [
                        (PayableDetails::max('payableDetailsId') ?? 0) + 1,
                        $payable->PayableMasterId,
                        $sd['ledgerId'],
                        $sd['grossAmount'],
                        $sd['discountPercentage'],
                        $sd['netAmount'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['amount'],
                        $sd['Narration'],
                        $sd['chequeNo'],
                        $sd['chequeDate'],
                        $sd['lineIndex'],
                        $request->branchId,
                        $payable->CreatedDate,
                        $payable->CreatedUser,
                       $modifiedDate, // ModifiedDate
                        $request->ModifiedUser  // ModifiedUser
                    ]);


                    if ($sd['amount'] > 0) {
                        $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                            $payable->date,           // date
                            'Payable Voucher',         // voucherType
                            $payable->PayableMasterId,  // voucherNo
                            $sd['ledgerId'],        // ledgerId
                           $sd['amount'] - $sd['taxAmount'], // debit
                            0,                          // credit
                            $payable->voucherNo,         // InvoiceNo
                            $payable->narration,         // Narration
                            $payable->costCentreId,      // costCentreId
                            'Payable',                   // PostingType
                            $payable->exchangeRate,      // exchangeRate
                            $payable->exchangeDate,      // exchangeDate
                            $payable->branchId,          // branchId
                            $request->ModifiedUser,   // CreatedUser
                            $modifiedDate
                        ]);
                    }
                }
            }

            if ($payable->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $payable->ledgerId,  // ledgerId
                    0,                          // debit
                    $payable->totalAmount,      // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable',                   // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($payable->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    $payable->totalTax,         // debit
                    0,                          // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable',                   // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($payable->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    0,                      // debit
                    $payable->CashAmount,            // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable Payment',   // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($payable->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    0,                      // debit
                    $payable->BankAmount,            // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable Payment',          // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if (($payable->BankAmount + $payable->CashAmount) > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $payable->date,           // date
                    'Payable Voucher',         // voucherType
                    $payable->PayableMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    ($payable->BankAmount + $payable->CashAmount),                      // debit
                    0,                          // credit
                    $payable->voucherNo,         // InvoiceNo
                    $payable->narration,         // Narration
                    $payable->costCentreId,      // costCentreId
                    'Payable Payment',          // PostingType
                    $payable->exchangeRate,      // exchangeRate
                    $payable->exchangeDate,      // exchangeDate
                    $payable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Payable Voucher Update',
                'Payable Voucher',
                'UPDATED',
                $payable->branchId,
                $request->ModifiedUser ?? 'System',
                $payable->PayableMasterId,
                $payable->voucherNo,
                $payable->date,
                $oldValue,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Payable Voucher Updated Successfully'
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
     * Delete Payable Voucher
     */
    public function destroy($id)
    {
        $payable = PayableMaster::find($id);

        if (!$payable) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Payable Voucher Not Found',
            ], 404);
        }
        $oldValue = $payable->totalAmount;

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_payable_details_byid(?)', [$id]);

            DB::statement('SELECT public.delete_payable_master_byid(?)', [$id]);

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Payable Voucher', // voucherType
                $payable->PayableMasterId, // voucherNo
                null  // branchId
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Payable Voucher Delete',
                'Payable Voucher',
                'DELETED',
                $payable->branchId,
                $payable->ModifiedUser ?? 'System',
                $payable->PayableMasterId,
                $payable->voucherNo,
                $payable->date,
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Payable Voucher Deleted Successfully',
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
    public function payableVoucherReport(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public."payable_voucher_report_fill"(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $request->fromDate,
                    $request->toDate,
                    false,
                    $request->ledgerId,
                    $request->branchId,
                    $request->currencyId,
                    $request->condition,
                    $request->userId,
                    $request->costCentreId,
                    $request->paymentMode,
                    $request->mode
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Payable Voucher Report fetched successfully'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}