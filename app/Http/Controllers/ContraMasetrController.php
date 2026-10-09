<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\ContraMaster;
use App\Models\ContraDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContraMasetrController extends Controller
{
    /**
     * List Contra Voucher
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $contra = DB::select('SELECT * FROM public.get_contra_master_list(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($contra);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Contra Voucher Retrieved Successfully',
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
     * Get Contra Voucher by id
     */
    public function show($id)
    {
        $contra = DB::select('SELECT * FROM public.get_contra_master_byid(?)', [$id]);

        // Check if found
        if (empty($contra)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Contra Voucher Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $contra = (array) $contra[0];

        // Step 2: Get Receipt details using receiptMasterId
        $contraDetails = DB::select('SELECT * FROM public.get_contra_details_by_masterid(?)', [$contra['contraMasterId']]);

        // Step 3: Attach Receipt details to master data
        $contra['contraDetails'] = $contraDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Contra Voucher Retrieved Successfully',
            'data' => $contra,
        ], 200);
    }


    /**
     * Create Contra Voucher
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

            // Insert Contra Voucher
            $result = DB::select('SELECT * FROM public.insert_contra_master(
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS "out_contraMasterId"', [
                $codeData['table_pk'], //contraMasterId
                $codeData['voucherNo'],  // voucherNo
                $codeData['suffixPrefixId'], // suffixPrefixId
                $codeData['voucherCode'], // contraNo
                $request->date,
                $request->ledgerId,
                $request->type,
                $request->narration,
                $request->totalAmount,
                $request->costCentreId,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $createdDate,
                $request->CreatedUser
            ]);

            // dd($result);
            $contra = ContraMaster::find($result[0]->out_contraMasterId);

            $debit = $contra->type === 'Deposit' ? $contra->totalAmount : 0;
            $credit = $contra->type === 'Withdrawal' ? $contra->totalAmount : 0;

            if ($contra->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $contra->date,           // date
                    'Contra Voucher',         // voucherType
                    $contra->contraMasterId,  // voucherNo
                    $contra->ledgerId,          // ledgerId
                    $debit,    // debit
                    $credit,                        // credit
                    $contra->contraNo,         // InvoiceNo
                    $contra->narration,         // Narration
                    $contra->costCentreId,      // costCentreId
                    'Contra',                   // PostingType
                    $request->exchangeRate,      // exchangeRate
                    $request->exchangeDate,      // exchangeDate
                    $contra->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Insert into contraDetails
            if ($request->has('contraDetails')) {
                foreach ($request->contraDetails as $sd) {
                    $details_credit = 0;
                    $details_debit = 0;
                    if ($contra->type === 'Deposit') {
                        $details_credit = $sd['amount'];
                    } else {
                        // Withdraw case
                        $details_debit = $sd['amount'];
                    }

                    $result = DB::select(
                        'SELECT public.insert_contra_details(
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        ) AS "out_contraDetailsId"',
                        [
                            (ContraDetails::max('contraDetailsId') ?? 0) + 1,    // contraDetailsId
                            $contra->contraMasterId,                     // contraMasterId
                            $sd['ledgerId'],                           // ledgerId
                            $sd['amount'],                             // amount
                            $sd['chequeNo'],                           // chequeNo
                            $sd['chequeDate'],                         // chequeDate
                            $request->branchId,                           // branchId
                            $createdDate, // CreatedDate
                            $request->CreatedUser,                        // CreatedUser
                            null,                                         // ModifiedDate
                            null                                          // ModifiedUser
                        ]
                    );

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $contra->date,               // date
                        'Contra Voucher',             // voucherType
                        $contra->contraMasterId,          // voucherNo
                        $sd['ledgerId'],              // ledgerId
                        $details_debit,                // debit
                        $details_credit,            // credit
                        $contra->contraNo,         // InvoiceNo
                        $contra->narration,             // Narration
                        $contra->costCentreId,      // costCentreId
                        'Contra',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $contra->branchId,          // branchId
                        $request->CreatedUser,       // CreatedUser
                        $createdDate
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Contra Voucher Inserted Successfully'
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
     * Update Contra Voucher
     */
    public function update(Request $request, $id)
    {
        $contra = ContraMaster::find($id);

        if (!$contra) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Contra Voucher Not Found',
            ], 404);
        }
        $oldValue = $contra->totalAmount;

        DB::beginTransaction();
            $branchId   = $request->branchId;
            $modifiedDate = getBranchNow($branchId);
        try {

            // Update Contra Voucher
            $result = DB::select('SELECT * FROM public.update_contra_master(?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $id,
                $request->date,
                $request->ledgerId,
                $request->type,
                $request->narration,
                $request->totalAmount,
                $request->costCentreId,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser
            ]);

            $contra->refresh();

            $debit = $contra->type === 'Deposit' ? $contra->totalAmount : 0;
            $credit = $contra->type === 'Withdrawal' ? $contra->totalAmount : 0;

            if ($contra->totalAmount > 0) {
                DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                    'Contra Voucher', // voucherType
                    $contra->contraMasterId, // voucherNo
                    $request->branchId   // branchId
                ]);
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $contra->date,           // date
                    'Contra Voucher',         // voucherType
                    $contra->contraMasterId,  // voucherNo
                    $contra->ledgerId,          // ledgerId
                    $debit,    // debit
                    $credit,                        // credit
                    $contra->contraNo,         // InvoiceNo
                    $contra->narration,         // Narration
                    $contra->costCentreId,      // costCentreId
                    'Contra',                   // PostingType
                    $request->exchangeRate,      // exchangeRate
                    $request->exchangeDate,      // exchangeDate
                    $contra->branchId,          // branchId
                    $contra->CreatedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }

            if ($request->has('contraDetails')) {
                DB::select('SELECT delete_contra_details_byid(?)', [$contra->contraMasterId]);
                foreach ($request->contraDetails as $sd) {
                    $details_credit = 0;
                    $details_debit = 0;
                    if ($contra->type === 'Deposit') {
                        $details_credit = $sd['amount'];
                    } else {
                        // Withdraw case
                        $details_debit = $sd['amount'];
                    }

                    $result = DB::select(
                        'SELECT public.insert_contra_details(
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        ) AS "out_contraDetailsId"',
                        [
                            (ContraDetails::max('contraDetailsId') ?? 0) + 1,    // contraDetailsId
                            $contra->contraMasterId,                     // contraMasterId
                            $sd['ledgerId'],                           // ledgerId
                            $sd['amount'],                             // amount
                            $sd['chequeNo'],                           // chequeNo
                            $sd['chequeDate'],                         // chequeDate
                            $request->branchId,                           // branchId
                            null, // CreatedDate
                            null,                        // CreatedUser
                            $modifiedDate,                                         // ModifiedDate
                            $request->ModifiedUser                                          // ModifiedUser
                        ]
                    );

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $contra->date,               // date
                        'Contra Voucher',             // voucherType
                        $contra->contraMasterId,          // voucherNo
                        $sd['ledgerId'],              // ledgerId
                        $details_debit,                // debit
                        $details_credit,            // credit
                        $contra->contraNo,         // InvoiceNo
                        $contra->narration,             // Narration
                        $contra->costCentreId,      // costCentreId
                        'Contra',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $contra->branchId,          // branchId
                        $contra->CreatedUser,       // CreatedUser
                        $modifiedDate
                    ]);
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Contra Voucher Update',
                'Contra Voucher',
                'UPDATED',
                $contra->branchId,
                $request->ModifiedUser ?? 'System',
                $contra->contraMasterId,
                $contra->voucherNo,
                $contra->date,
                $oldValue,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Contra Voucher Updated Successfully'
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
     * Delete Contra Voucher
     */
    public function destroy($id)
    {
        $contra = ContraMaster::find($id);

        if (!$contra) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Contra Voucher Not Found',
            ], 404);
        }
        $oldValue = $contra->totalAmount;

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_contra_details_byid(?)', [$id]);

            DB::statement('SELECT public.delete_contra_master_byid(?)', [$id]);

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Contra Voucher', // voucherType
                $contra->contraMasterId, // voucherNo
                null   // branchId
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Contra Voucher Delete',
                'Contra Voucher',
                'DELETED',
                $contra->branchId,
                $contra->ModifiedUser ?? 'System',
                $contra->contraMasterId,
                $contra->voucherNo,
                $contra->date,
                $oldValue,
                0
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Contra Voucher Deleted Successfully',
            ]);
        }
    }

    // contra report

    public function ContraReport(Request $request)
    {
        $fromDate     = $request->input('fromDate');
        $toDate       = $request->input('toDate');
        $postedStatus = (string) $request->input('postedStatus');
        $ledgerId     = (int) $request->input('ledgerId', 0);
        $branchId     = (int) $request->input('branchId');
        $currencyId   = (int) $request->input('currencyId');
        $condition    = $request->input('condition', 'All');
        $userId       = (int) $request->input('userId', 0);
        $costCentreId = (int) $request->input('costCentreId', 0);
        $detailed     = filter_var($request->input('detailed'), FILTER_VALIDATE_BOOLEAN);

        $data = DB::select(
            'SELECT * FROM contra_report_fill(?,?,?,?,?,?,?,?,?,?)',
            [
                $fromDate,
                $toDate,
                $postedStatus,
                $ledgerId,
                $branchId,
                $currencyId,
                $condition,
                $userId,
                $costCentreId,
                $detailed
            ]
        );

        return response()->json([
            'params' => compact(
                'fromDate',
                'toDate',
                'postedStatus',
                'ledgerId',
                'branchId',
                'currencyId',
                'condition',
                'userId',
                'costCentreId',
                'detailed'
            ),
            'data' => $data
        ]);
    }
}