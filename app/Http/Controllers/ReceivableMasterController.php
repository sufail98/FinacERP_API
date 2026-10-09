<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\ReceivableMaster;
use App\Models\ReceivableDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableMasterController extends Controller
{
    /**
     * List Receivable Voucher
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $receivable = DB::select('SELECT * FROM public.get_all_receivable_master(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($receivable);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Receivable Voucher Retrieved Successfully',
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
     * Get Receivable Voucher by id
     */
    public function show($id)
    {
        $receivable = DB::select('SELECT * FROM public.get_receivable_master_byid(?)', [$id]);

        // Check if found
        if (empty($receivable)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Receivable Voucher Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $receivable = (array) $receivable[0];

        // Step 2: Get Receipt details using receiptMasterId
        $receivableDetails = DB::select('SELECT * FROM public.get_receivable_details_byid(?)', [$receivable['ReceivableMasterId']]);

        // Step 3: Attach Receipt details to master data
        $receivable['receivableDetails'] = $receivableDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Receivable Voucher Retrieved Successfully',
            'data' => $receivable,
        ], 200);
    }


    /**
     * Create Receivable Voucher
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

            // Insert Receivable Voucher
            $result = DB::select('SELECT insert_receivable_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS out_ReceivableMasterId', [
                $codeData['table_pk'],                   // ReceivableMasterId
                $codeData['voucherNo'],                  // voucherNo
                $codeData['suffixPrefixId'],             // suffixPrefixId
                $codeData['voucherCode'],                // ReceivableNo
                $request->date,                          // date
                $request->ledgerId,                      // ledgerId
                $request->partyName,                     // partyName
                $request->partyAddress,                  // partyAddress
                $request->partyPhone,                    // partyPhone
                $request->partyVatNo,                    // partyVatNo
                $request->narration,                     // narration
                $request->totalTax,                      // totalTax
                $request->totalAmount,                   // totalAmount
                $request->costCentreId,                  // costCentreId
                $request->ReferenceNo,                   // ReferenceNo
                $request->ReferenceDate,                 // ReferenceDate
                $request->paymentMode,                   // paymentMode
                $request->CashLedgerId,                  // CashLedgerId
                $request->CashRefNo,                     // CashRefNo
                $request->CashAmount,                    // CashAmount
                $request->BankLedgerId,                  // BankLedgerId
                $request->BankRefNo,                     // BankRefNo
                $request->BankAmount,                    // BankAmount
                $request->BillBalanceAmount,             // BillBalanceAmount
                $request->taxType,                       // taxType
                $request->currencyConversionId,          // currencyConversionId
                $request->exchangeRate,                  // exchangeRate
                $request->exchangeDate,                  // exchangeDate
                $request->employeeId,                    // employeeId
                $request->postedStatus,                  // postedStatus
                $request->postedBy,                      // postedBy
                $request->postedDate,                    // postedDate
                $request->branchId,                      // branchId
                $createdDate,     // CreatedDate
                $request->CreatedUser,                   // CreatedUser
                null,                                    // ModifiedDate
                null                                     // ModifiedUser
            ]);

            // dd($result);
            $receivable = ReceivableMaster::find($result[0]->out_receivablemasterid);

            // Insert into receivableDetails
            if ($request->has('receivableDetails')) {
                foreach ($request->receivableDetails as $sd) {
                    $result = DB::select('SELECT insert_receivable_details(
                        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
                    ) AS id', [
                        (ReceivableDetails::max('receivableDetailsId') ?? 0) + 1,
                        $receivable->ReceivableMasterId,
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
                            $receivable->date,           // date
                            'Receivable Voucher',         // voucherType
                            $receivable->ReceivableMasterId,  // voucherNo
                            $sd['ledgerId'],        // ledgerId
                            0,                              // debit
                            $sd['amount'] - $sd['taxAmount'], // debit
                            $receivable->voucherNo,         // InvoiceNo
                            $receivable->narration,         // Narration
                            $receivable->costCentreId,      // costCentreId
                            'Receivable',                   // PostingType
                            $receivable->exchangeRate,      // exchangeRate
                            $receivable->exchangeDate,      // exchangeDate
                            $receivable->branchId,          // branchId
                            $request->CreatedUser,   // CreatedUser
                            $createdDate
                        ]);
                    }
                }
            }

            if ($receivable->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $receivable->ledgerId,  // ledgerId
                    $receivable->totalAmount,       // debit
                    0,                              // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable',                   // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($receivable->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    0,                              // debit
                    $receivable->totalTax,          // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable',                   // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($receivable->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    $receivable->CashAmount,        // debit
                    0,                              // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable Receipt',   // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if ($receivable->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    $receivable->BankAmount,        // debit
                    0,                              // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable Receipt',          // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            if (($receivable->BankAmount + $receivable->CashAmount) > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    0,                      // debit
                    ($receivable->BankAmount + $receivable->CashAmount),    // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable Receipt',          // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Receivable Voucher Inserted Successfully'
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
     * Update Receivable Voucher
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $receivable = ReceivableMaster::find($id);

        if (!$receivable) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Receivable Voucher Not Found',
            ], 404);
        }
        $oldValue = $receivable->totalAmount;
        DB::beginTransaction();
        try {
            $branchId   = $request->branchId;
            $modifiedDate = getBranchNow($branchId);
            // Update Receivable Voucher
            DB::select('SELECT update_receivable_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?
            )', [
                $id,
                $request->date,
                $request->ledgerId,
                $request->partyName,
                $request->partyAddress,
                $request->partyPhone,
                $request->partyVatNo,
                $request->narration,
                $request->totalTax,
                $request->totalAmount,
                $request->costCentreId,
                $request->ReferenceNo,
                $request->ReferenceDate,
                $request->paymentMode,
                $request->CashLedgerId,
                $request->CashRefNo,
                $request->CashAmount,
                $request->BankLedgerId,
                $request->BankRefNo,
                $request->BankAmount,
                $request->BillBalanceAmount,
                $request->taxType,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->employeeId,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser
            ]);

            $receivable->refresh();

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Receivable Voucher', // voucherType
                $receivable->ReceivableMasterId, // voucherNo
                $request->branchId   // branchId
            ]);

            if ($request->has('receivableDetails')) {
                DB::select('SELECT delete_receivable_details_byid(?)', [$receivable->ReceivableMasterId]);

                foreach ($request->receivableDetails as $sd) {
                    $result = DB::select('SELECT insert_receivable_details(
                        ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
                    ) AS id', [
                        (ReceivableDetails::max('receivableDetailsId') ?? 0) + 1,
                        $receivable->ReceivableMasterId,
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
                        $receivable->CreatedDate,
                        $receivable->CreatedUser,
                        $modifiedDate, // ModifiedDate
                        $request->ModifiedUser  // ModifiedUser
                    ]);

                    if ($sd['amount'] > 0) {
                        $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                            $receivable->date,           // date
                            'Receivable Voucher',         // voucherType
                            $receivable->ReceivableMasterId,  // voucherNo
                            $sd['ledgerId'],        // ledgerId
                            0,                              // debit
                            $sd['amount'] - $sd['taxAmount'], // debit
                            $receivable->voucherNo,         // InvoiceNo
                            $receivable->narration,         // Narration
                            $receivable->costCentreId,      // costCentreId
                            'Receivable',                   // PostingType
                            $receivable->exchangeRate,      // exchangeRate
                            $receivable->exchangeDate,      // exchangeDate
                            $receivable->branchId,          // branchId
                            $request->ModifiedUser,   // CreatedUser
                            $modifiedDate
                        ]);
                    }
                }
            }

            if ($receivable->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $receivable->ledgerId,  // ledgerId
                    $receivable->totalAmount,       // debit
                    0,                              // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable',                   // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($receivable->totalTax > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->vatLedgerId,  // ledgerId
                    0,                              // debit
                    $receivable->totalTax,          // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable',                   // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($receivable->CashAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->CashLedgerId,  // ledgerId
                    $receivable->CashAmount,        // debit
                    0,                              // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable Receipt',   // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($receivable->BankAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->BankLedgerId,  // ledgerId
                    $receivable->BankAmount,        // debit
                    0,                              // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable Receipt',          // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if (($receivable->BankAmount + $receivable->CashAmount) > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receivable->date,           // date
                    'Receivable Voucher',         // voucherType
                    $receivable->ReceivableMasterId,  // voucherNo
                    $request->ledgerId,  // ledgerId
                    0,                      // debit
                    ($receivable->BankAmount + $receivable->CashAmount),    // credit
                    $receivable->voucherNo,         // InvoiceNo
                    $receivable->narration,         // Narration
                    $receivable->costCentreId,      // costCentreId
                    'Receivable Receipt',          // PostingType
                    $receivable->exchangeRate,      // exchangeRate
                    $receivable->exchangeDate,      // exchangeDate
                    $receivable->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Receivable Voucher Update',
                'Receivable Voucher',
                'UPDATED',
                $receivable->branchId,
                $request->ModifiedUser ?? 'System',
                $receivable->ReceivableMasterId,
                $receivable->voucherNo,
                $receivable->date,
                $oldValue,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Receivable Voucher Updated Successfully'
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
     * Delete Receivable Voucher
     */
    public function destroy($id)
    {
        $receivable = ReceivableMaster::find($id);

        if (!$receivable) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Receivable Voucher Not Found',
            ], 404);
        }
        $oldValue = $receivable->totalAmount;

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_receivable_details_byid(?)', [$id]);
            DB::select('SELECT delete_receivable_master_byid(?::bigint)', [$id]);
            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Receivable Voucher', // voucherType
                $receivable->ReceivableMasterId, // voucherNo
                null   // branchId
            ]);

            DB::select('SELECT delete_partybalance(?, ?)', [
                'Receivable Voucher', // voucherType
                $receivable->ReceivableMasterId // voucherNo
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Receivable Voucher Delete',
                'Receivable Voucher',
                'DELETED',
                $receivable->branchId,
                $receivable->ModifiedUser ?? 'System',
                $receivable->ReceivableMasterId,
                $receivable->voucherNo,
                $receivable->date,
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Receivable Voucher Deleted Successfully',
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


    public function daybookSummery(Request $request)
    {
        try {
            $branchId   = $request->branchId !== null ? (int) $request->branchId : null;

            $data = DB::select(
                'SELECT * FROM daybook_summery(?,?,?)',
                [
                    $request->fromdate,
                    $request->todate,
                    $branchId
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Day book data fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function receivableVoucherReport(Request $request)
    {
        try {
            $fromdate     = $request->fromDate;
            $todate       = $request->toDate;
            $branchid     = $request->branchId     ?? '1';
            $ledgerid     = $request->ledgerId     ?: 'All';
            $currencyid   = $request->currencyId   ?? '1';
            $costcentreid = $request->costCentreId ?: 'All';
            $paymentmode  = $request->paymentMode  ?: 'All';
            $condition    = $request->condition    ?: 'All';
            $userid       = $request->userId       ?: 'All';
            $mode         = $request->mode         ?? 'Summary';

            $data = DB::select(
                'SELECT * FROM "receivable_voucher_report_fill"(?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $fromdate,       // 1. p_fromdate
                    $todate,         // 2. p_todate
                    false,           // 3. p_optional (boolean)
                    $ledgerid,       // 4. p_ledgerid
                    $branchid,       // 5. p_branchid
                    $currencyid,     // 6. p_currencyid
                    $condition,      // 7. p_condition
                    $userid,         // 8. p_userid
                    $costcentreid,   // 9. p_costcentreid
                    $paymentmode,    // 10. p_paymentmode
                    $mode,           // 11. p_mode
                ]
            );

            return response()->json([
                'status'  => 200,
                'errors'  => false,
                'data'    => $data,
                'message' => 'Receivable Voucher Report Data Fetched successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'errors'  => true,
                'message' => 'Validation failed',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}