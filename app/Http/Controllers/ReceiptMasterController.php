<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\ReceiptMaster;
use App\Models\AccountLedger;
use App\Models\LedgerPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ReceiptMasterController extends Controller
{
    /**
     * List ReceiptMaster
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $receipt = DB::select('SELECT * FROM public.get_receipt_master_list(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($receipt);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Receipt Retrieved Successfully',
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
     * Get ReceiptMaster by id
     */
    public function show($id)
    {
        $receipt = DB::select(
            'SELECT * FROM public.get_receipt_master_data_byid(?)',
            [$id]
        );

        if (empty($receipt)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Receipt Not Found',
            ], 404);
        }

        $receipt = $receipt[0];

        // Convert Documents to full URLs
        if (!empty($receipt->Documents)) {

            $documents = json_decode($receipt->Documents, true);

            if (is_array($documents)) {
                $receipt->Documents = array_map(function ($file) {
                    return asset($file);
                }, $documents);
            } else {
                $receipt->Documents = [];
            }
        } else {
            $receipt->Documents = [];
        }

        $receipt->receiptDetails = DB::select(
            'SELECT * FROM public.get_receipt_details_by_master_id(?)',
            [$receipt->receiptMasterId]
        );

        $cr = DB::select(
            'SELECT * FROM public.party_balance_by_voucher_type_and_no(?::varchar, ?::integer, ?::integer, ?::varchar)',
            [
                'Receipt Voucher',
                (int) $receipt->receiptMasterId,
                (int) $receipt->branchId,
                'Cr'
            ]
        );

        $dr = DB::select(
            'SELECT * FROM public.party_balance_by_voucher_type_and_no(?::varchar, ?::integer, ?::integer, ?::varchar)',
            [
                'Receipt Voucher',
                (int) $receipt->receiptMasterId,
                (int) $receipt->branchId,
                'Dr'
            ]
        );

        $receipt->partyDetails = collect($cr)
            ->merge($dr)
            ->unique('balanceId')
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Receipt Retrieved Successfully',
            'data' => $receipt,
        ]);
    }



    /**
     * Create ReceiptMaster
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'Documents'   => 'nullable|array',
                'Documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:20480'
            ]);

            $uploadedFiles = [];

            // Upload multiple documents
            if ($request->hasFile('Documents')) {

                $destinationPath = public_path('storage/receiptmasterdocuments');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                foreach ($request->file('Documents') as $file) {

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    $file->move($destinationPath, $fileName);

                    // Store relative path
                    $uploadedFiles[] = 'storage/receiptmasterdocuments/' . $fileName;
                }
            }

            // Store uploaded file paths as JSON
            $Documents = !empty($uploadedFiles)
                ? json_encode($uploadedFiles)
                : null;

            $branchId   = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId     = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);

            $receiptMasterId = DB::selectOne(
                'SELECT public.insert_receipt_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS "receiptMasterId"',
                [
                    $codeData['voucherNo'],
                    $codeData['suffixPrefixId'],
                    $codeData['voucherCode'],
                    $request->date,
                    $request->ledgerId,
                    $request->narration,
                    $request->totalAmount,
                    $request->userId,
                    $request->employeeId,
                    $request->costCentreId,
                    $request->ReferenceNo,
                    $request->ReferenceDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->vanExecutiveId,
                    $request->branchId,
                    $request->CreatedUser,
                    $createdDate,
                    $Documents



                ]
            )->receiptMasterId;

            $receipt = ReceiptMaster::find($receiptMasterId);

            if ($receipt->totalAmount > 0) {
                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receipt->date,           // date
                    'Receipt Voucher',         // voucherType
                    $receipt->receiptMasterId,  // voucherNo
                    $receipt->ledgerId,          // ledgerId
                    $receipt->totalAmount,    // debit
                    0,                        // credit
                    $receipt->receiptNo,         // InvoiceNo
                    $request->narration,         // Narration
                    $request->costCentreId,      // costCentreId
                    'Receipt',                   // PostingType
                    $request->exchangeRate,      // exchangeRate
                    $request->exchangeDate,      // exchangeDate
                    $request->branchId,          // branchId
                    $request->CreatedUser,   // CreatedUser
                    $createdDate
                ]);
            }

            // Insert into tbl_SalesReturnDetails1
            if ($request->has('receiptDetails')) {
                foreach ($request->receiptDetails as $sd) {
                    DB::select('SELECT public.insert_receipt_details(
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?
					) AS "receiptDetailsId"', [
                        $receipt->receiptMasterId,         // receiptMasterId
                        $sd['SlNo'],                          // SlNo
                        $sd['ledgerId'],                      // ledgerId
                        $sd['amount'],                      // amount
                        $sd['Narration'],                  // Narration
                        $sd['chequeNo'],                   // chequeNo
                        $sd['chequeDate'],               // chequeDate
                        $sd['currencyConversionId'],    // currencyConversionId
                        $request->exchangeRate,         // exchangeRate
                        $request->exchangeDate,         // exchangeDate
                        $request->branchId,             // branchId
                        $createdDate,
                        $request->CreatedUser,              // CreatedUser
                        null,
                        null
                    ]);

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $receipt->date,               // date
                        'Receipt Voucher',             // voucherType
                        $receipt->receiptMasterId,          // voucherNo
                        $sd['ledgerId'],              // ledgerId
                        0,                            // debit
                        $sd['amount'],                // credit
                        $receipt->receiptNo,         // InvoiceNo
                        $sd['Narration'],         // Narration
                        $request->costCentreId,      // costCentreId
                        'Receipt',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $request->branchId,          // branchId
                        $request->CreatedUser,       // CreatedUser
                        $createdDate
                    ]);
                }
            }
            // dd($receipt);
            if ($request->has('partyDetails')) {
                foreach ($request->partyDetails as $pd) {

                    if ($pd['referenceType'] === 'Against') {
                        $partyVoucherType        = $pd['voucherType'];       // referenced bill's type
                        $partyVoucherNo          = $pd['masterId'];          // referenced bill's MasterId ← C#: dRow["MasterId"]
                        $partyAgainstVoucherType = 'Receipt Voucher';
                        $partyAgainstvoucherNo   = $receipt->receiptMasterId;
                    } else {
                        // 'New' or 'OnAccount'
                        $partyVoucherType        = 'Receipt Voucher';
                        $partyVoucherNo          = $receipt->receiptMasterId;
                        $partyAgainstVoucherType = 'NA';
                        $partyAgainstvoucherNo   = 'NA';
                    }

                    DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $receipt->date,              // date ← always receipt header date
                        $pd['ledgerId'],          // ledgerId
                        $partyVoucherType,           // voucherType
                        $partyVoucherNo,             // voucherNo
                        $partyAgainstVoucherType,    // againstVoucherType
                        $partyAgainstvoucherNo,      // againstvoucherNo
                        $pd['referenceType'],        // referenceType
                        0,                           // debit
                        $pd['amount'],               // credit
                        $pd['creditPeriod'],         // creditPeriod
                        $pd['currencyConversionId'], // currencyConversionId
                        $pd['invoiceNo'],            // invoiceNo
                        $pd['referenceNo'],          // referenceNo
                        $pd['BillAmount'],           // BillAmount
                        $pd['invoiceDate'],          // invoiceDate
                        $request->costCentreId,      // costCentreId
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $request->branchId,          // branchId
                        $request->CreatedUser,        // CreatedUser
                        $createdDate
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Sales Receipt Inserted Successfully'
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
     * Update ReceiptMaster
     */
    public function update(Request $request, $id)
    {
        $receipt = ReceiptMaster::find($id);

        if (!$receipt) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Receipt Not Found',
            ], 404);
        }

        $oldValue = $receipt->totalAmount;

        DB::beginTransaction();

        try {

            $request->validate([
                'Documents'   => 'nullable|array',
                'Documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:20480'
            ]);

            // Keep existing documents
            $Documents = $receipt->Documents;

            if ($request->hasFile('Documents')) {

                // Delete old files
                if (!empty($receipt->Documents)) {

                    $oldFiles = json_decode($receipt->Documents, true);

                    if (is_array($oldFiles)) {

                        foreach ($oldFiles as $oldFile) {

                            $filePath = public_path($oldFile);

                            if (is_file($filePath)) {
                                @unlink($filePath);
                            }
                        }
                    }
                }

                // Upload new files
                $destinationPath = public_path('storage/receiptmasterdocuments');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $uploadedFiles = [];

                foreach ($request->file('Documents') as $file) {

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    $file->move($destinationPath, $fileName);

                    $uploadedFiles[] = 'storage/receiptmasterdocuments/' . $fileName;
                }

                $Documents = json_encode($uploadedFiles);
            }
            $branchId   = $request->branchId;
            $modifiedDate = getBranchNow($branchId);

            // Update Receipt Master
            DB::select(
                'SELECT * FROM public.update_receipt_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?
            )',
                [
                    $id,
                    $request->date,
                    $request->ledgerId,
                    $request->narration,
                    $request->totalAmount,
                    $request->userId,
                    $request->employeeId,
                    $request->costCentreId,
                    $request->ReferenceNo,
                    $request->ReferenceDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->vanExecutiveId,
                    $request->branchId,
                    $request->ModifiedUser,
                    $Documents,
                    $modifiedDate
                ]
            );



            $receipt->refresh();

            if ($receipt->totalAmount > 0) {
                DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                    'Receipt Voucher', // voucherType
                    $receipt->receiptMasterId, // voucherNo
                    $request->branchId   // branchId
                ]);

                $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $receipt->date,           // date
                    'Receipt Voucher',         // voucherType
                    $receipt->receiptMasterId,  // voucherNo
                    $receipt->ledgerId,          // ledgerId
                    $receipt->totalAmount,    // debit
                    0,                        // credit
                    $receipt->receiptNo,         // InvoiceNo
                    $request->narration,          // Narration
                    $request->costCentreId,      // costCentreId
                    'Receipt',                   // PostingType
                    $request->exchangeRate,      // exchangeRate
                    $request->exchangeDate,      // exchangeDate
                    $request->branchId,          // branchId
                    $request->ModifiedUser,   // CreatedUser
                    $modifiedDate
                ]);
            }
            $originalCreatedDate = $receipt->CreatedDate ?? $receipt->created_at;
            $originalCreatedUser = $receipt->CreatedUser ?? $receipt->created_user;
            if ($request->has('receiptDetails')) {
                DB::select('SELECT delete_receipt_details(?)', [$receipt->receiptMasterId]);

                foreach ($request->receiptDetails as $sd) {
                    DB::select('SELECT public.insert_receipt_details(
						  ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?
					) AS "receiptDetailsId"', [
                        $receipt->receiptMasterId,         // receiptMasterId
                        $sd['SlNo'],                          // SlNo
                        $sd['ledgerId'],                      // ledgerId
                        $sd['amount'],                      // amount
                        $sd['Narration'],                  // Narration
                        $sd['chequeNo'],                   // chequeNo
                        $sd['chequeDate'],               // chequeDate
                        $sd['currencyConversionId'],    // currencyConversionId
                        $request->exchangeRate,         // exchangeRate
                        $request->exchangeDate,         // exchangeDate
                        $request->branchId,             // branchId
                        $originalCreatedDate,
                        $originalCreatedUser,
                        $request->ModifiedUser,              // CreatedUser
                        $modifiedDate
                    ]);

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $receipt->date,           // date
                        'Receipt Voucher',         // voucherType
                        $receipt->receiptMasterId,  // voucherNo
                        $sd['ledgerId'],          // ledgerId
                        0,    // debit
                        $sd['amount'],                        // credit
                        $receipt->receiptNo,         // InvoiceNo
                        $sd['Narration'],           // Narration
                        $request->costCentreId,      // costCentreId
                        'Receipt',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $request->branchId,          // branchId
                        $request->ModifiedUser,   // CreatedUser
                        $modifiedDate
                    ]);
                }
            }

            if ($request->has('partyDetails')) {
                DB::select('SELECT delete_partybalance(?, ?)', [
                    'Receipt Voucher',
                    $receipt->receiptMasterId
                ]);

                foreach ($request->partyDetails as $pd) {

                    if ($pd['referenceType'] === 'Against') {
                        $partyVoucherType        = $pd['voucherType'];       // referenced bill's type
                        $partyVoucherNo          = $pd['masterId'];          // referenced bill's MasterId
                        $partyAgainstVoucherType = 'Receipt Voucher';
                        $partyAgainstvoucherNo   = $receipt->receiptMasterId;
                    } else {
                        // 'New' or 'OnAccount'
                        $partyVoucherType        = 'Receipt Voucher';
                        $partyVoucherNo          = $receipt->receiptMasterId;
                        $partyAgainstVoucherType = 'NA';
                        $partyAgainstvoucherNo   = 'NA';
                    }

                    DB::select('SELECT * FROM insert_party_balance(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $receipt->date,              // ✅ Fix: always receipt header date, not $pd['date']
                        $pd['ledgerId'],
                        $partyVoucherType,           // ✅ Fix: swapped per referenceType
                        $partyVoucherNo,             // ✅ Fix: swapped per referenceType
                        $partyAgainstVoucherType,    // ✅ Fix: swapped per referenceType
                        $partyAgainstvoucherNo,      // ✅ Fix: swapped per referenceType
                        $pd['referenceType'],
                        0,                           // ✅ Fix: debit always 0
                        $pd['amount'],               // ✅ Fix: credit = amount (was debit before)
                        $pd['creditPeriod'],
                        $pd['currencyConversionId'],
                        $pd['invoiceNo'],            // ✅ Fix: bill's own voucherNo, not $receipt->receiptNo
                        $pd['referenceNo'],
                        $pd['BillAmount'],
                        $pd['invoiceDate'],          // ✅ Fix: bill's original date, not $pd['date']
                        $request->costCentreId,
                        $request->exchangeRate,
                        $request->exchangeDate,
                        $request->branchId,
                        $request->ModifiedUser,       // ✅ Fix: ModifiedUser not CreatedUser
                        $modifiedDate
                    ]);
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,                       // userId
                'Sales Receipt Update',                  // action
                'Sales Receipt',                         // module
                'UPDATED',                               // activityType
                $receipt->branchId,                      // branchId
                $request->ModifiedUser ?? 'System',
                $receipt->receiptMasterId,               // referenceId
                $receipt->voucherNo,                     // referenceNo
                $receipt->date,                          // referenceDate
                $oldValue,                               // oldValue
                $request->totalAmount                    // newValue
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Receipt Updated Successfully'
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
    public function destroy($id)
    {
        $receipt = ReceiptMaster::find($id);

        if (!$receipt) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Receipt Not Found',
            ], 404);
        }

        $oldValue = $receipt->totalAmount;

        DB::beginTransaction();

        try {
            DB::table('tbl_Receiptdetails')
                ->where('receiptMasterId', $receipt->receiptMasterId)
                ->delete();

            // dd($receipt);
            DB::table('tbl_Partybalance')
                ->where('againstvoucherNo', $receipt->receiptMasterId)
                ->where('againstVoucherType', 'Receipt Voucher')
                ->delete();

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Receipt Voucher',
                $receipt->receiptMasterId,
                $receipt->branchId
            ]);

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Sales Receipt Delete',
                'Sales Receipt',
                'DELETED',
                $receipt->branchId,
                $receipt->ModifiedUser ?? ($receipt->CreatedUser ?? 'System'),
                $receipt->receiptMasterId,
                $receipt->voucherNo,
                $receipt->date,
                $oldValue,
                0
            );

            $receipt->delete();

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Receipt Deleted Successfully',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function receiptReport(Request $request)
    {
        try {
            $fromdate     = $request->fromdate;
            $todate       = $request->todate;
            $ledgerid     = $request->ledgerid ?? null;
            $branchid     = $request->branchid ?? null;
            $currencyid   = $request->currencyid ?? null;
            $detailed     = $request->detailed ?? null;
            $ledgername   = $request->ledgername ?? null;
            $employeeid   = $request->employeeid ?? null;
            $userid       = $request->userid ?? null;
            $costcentreid = $request->costcentreid ?? null;

            $data = DB::select(
                'SELECT * FROM "receipt_report_fill"(?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $fromdate,
                    $todate,
                    $detailed,
                    $ledgerid,
                    $branchid,
                    $currencyid,
                    $detailed,
                    $ledgername,
                    $employeeid,
                    $userid,
                    $costcentreid
                ]
            );

            return response()->json([
                'status' => 200,
                'errors' => false,
                'data' => $data,
                'message' => 'Receipt Report Data Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function receiptPaymentAgainstView(Request $request)
    {
        try {
            $ledgerId = $request->ledgerId;
            $crOrDr   = $request->crOrDr;
            $branchId = $request->branchId ?? null;
            $results = DB::select(
                'SELECT * FROM "party_balance_view_by_ledger_id_new"(?, ?, ?)',
                [$ledgerId, $crOrDr, $branchId]
            );
            return response()->json([
                'status' => 200,
                'errors' => false,
                'data' => $results,
                'message' => 'Receipt Payment Against Fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function receiptPaymentAgainstGet(Request $request)
    {
        try {
            $results = DB::select(
                'SELECT * FROM "party_balance_get_by_against_new"(?, ?, ?, ?, ?)',
                [
                    $request->againstVoucherType,
                    $request->againstvoucherNo,
                    $request->ledgerId,
                    $request->branchId,
                    $request->crOrDr
                ]
            );
            return response()->json([
                'status'  => 200,
                'errors'  => false,
                'data'    => $results,
                'message' => 'Party Balance fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function allFinanceApis(Request $request)
    {
        try {

            $voucherType = $request->input('voucherType', 'Sales Invoice');
            $branchId = $request->input('branchId', null);
            $yearId = $request->input('yearId', null);

            if (!$branchId) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Branch ID is required',
                    'data'   => []
                ], 400);
            }

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            // $groupIds = $request->input('group_ids', []);
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
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders)

                    UNION ALL

                    -- Recursive case
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

            // Run query with bindings
            $result = DB::select($sql, $groupIds);
            // Extract group IDs from result
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

            $employees = DB::select('SELECT * FROM public.get_all_employee()');
            $costcentre = DB::select('SELECT * FROM public.get_all_costcentres()');
            $allAccountLedger = DB::select('SELECT * FROM public.get_all_account_ledgers(?)', [$branchId]);


            $groupIds11 = [5, 8];
            $placeholders11 = implode(',', array_fill(0, count($groupIds11), '?'));

            $sql11 = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders11)

                    UNION ALL

                    -- Recursive case
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
                        AND NOT e.\"groupId\" = ANY (g.path)   -- 🔥 stops infinite loop
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

            // Run query with bindings
            $result11 = \DB::select($sql11, $groupIds11);
            // Extract group IDs from result
            $excludedGroupIds11 = collect($result11)->pluck('groupId')->toArray();

            $AccountLedger = AccountLedger::whereNotIn('groupId', $excludedGroupIds11)
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






            $ledgerId = $request->input('ledgerId');
            $currencyId = $request->input('currencyId');

            // $LedgerBalance = DB::select("
            //     SELECT * FROM get_current_ledger_balance(?, ?, ?)
            // ", [$ledgerId, $branchId, $currencyId]);

            $currencywithConversion = DB::select('SELECT * FROM get_currency_with_conversion()');

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


            $groupIds3 = [26];
            // Create placeholders for safe parameter binding
            $placeholders3 = implode(',', array_fill(0, count($groupIds3), '?'));
            $sql3 = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders3)

                    UNION ALL

                    -- Recursive case
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

            // Run query with bindings
            $result3 = DB::select($sql3, $groupIds3);
            // Extract group IDs from result
            $excludedGroupIds3 = collect($result3)->pluck('groupId')->toArray();

            $bankaccountledgers26 = AccountLedger::whereIn('groupId', $excludedGroupIds3)
                ->whereRaw(
                    'EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements("branchId") AS b
                    WHERE (b->>\'branchId\')::int = ?
                )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();


            $groupIds4 = [5, 8];
            // Create placeholders for safe parameter binding
            $placeholders4 = implode(',', array_fill(0, count($groupIds4), '?'));
            $sql4 = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders4)

                    UNION ALL

                    -- Recursive case
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

            // Run query with bindings
            $result4 = DB::select($sql4, $groupIds4);
            // Extract group IDs from result
            $excludedGroupIds4 = collect($result4)->pluck('groupId')->toArray();

            $bankaccountledgers5and8 = AccountLedger::whereIn('groupId', $excludedGroupIds4)
                ->whereRaw(
                    'EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements("branchId") AS b
                    WHERE (b->>\'branchId\')::int = ?
                )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();



            $groupIds5 = [8];
            // Create placeholders for safe parameter binding
            $placeholders5 = implode(',', array_fill(0, count($groupIds5), '?'));
            $sql5 = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders5)

                    UNION ALL

                    -- Recursive case
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

            // Run query with bindings
            $result5 = DB::select($sql5, $groupIds5);
            // Extract group IDs from result
            $excludedGroupIds5 = collect($result5)->pluck('groupId')->toArray();

            $cash = AccountLedger::whereIn('groupId', $excludedGroupIds5)
                ->whereRaw(
                    'EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements("branchId") AS b
                    WHERE (b->>\'branchId\')::int = ?
                )',
                    [$branchId]
                )->orderBy('ledgerName', 'asc')->get();



            $groupIds8 = [5, 6, 8, 23, 24, 29, 44, 43];
            $placeholders8 = implode(',', array_fill(0, count($groupIds8), '?'));

            $sql8 = "
                WITH RECURSIVE GroupInMainGroupP AS (
                    -- Base case
                    SELECT
                        \"groupId\",
                        \"groupUnder\",
                        1 AS \"HierarchyLevel\",
                        ARRAY[\"groupId\"] AS path
                    FROM \"tbl_AccountGroup\"
                    WHERE \"groupId\" IN ($placeholders8)

                    UNION ALL

                    -- Recursive case
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
                        AND NOT e.\"groupId\" = ANY (g.path)   -- 🔥 stops infinite loop
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

            // Run query with bindings
            $result8 = \DB::select($sql8, $groupIds8);
            // Extract group IDs from result
            $excludedGroupIds8 = collect($result8)->pluck('groupId')->toArray();

            $payablevoucherledgers = AccountLedger::whereNotIn('groupId', $excludedGroupIds8)
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


            $customers = AccountLedger::whereIn('ledgerType', ['Customer'])
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

            $taxMaster = DB::select('SELECT * FROM public.get_all_tax_master() ORDER BY "taxName" ASC');

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Finance Data',
                'data' => [
                    'voucherdata'   => [
                        'table_pk'        => $codeData['table_pk'],
                        'voucherCode'    => $codeData['voucherCode'],
                        'voucherNo'       => $codeData['voucherNo'],
                        'suffixPrefixId'  => $codeData['suffixPrefixId'],
                    ],
                    'bank' => $bank,
                    'cash' => $cash,
                    'employees'     =>  $employees,
                    'costcentre'    =>  $costcentre,
                    'allAccountLedger' => $allAccountLedger,
                    'accountLedger' => $AccountLedger,
                    // 'LedgerBalance' => $LedgerBalance[0] ?? null,
                    'currencywithConversion' => $currencywithConversion,
                    'customersupplierLedgers' => $customersupplierLedgers,
                    'bankaccountledgers26' => $bankaccountledgers26,
                    'bankaccountledgers5and8' => $bankaccountledgers5and8,
                    'payablevoucherledgers'  => $payablevoucherledgers,
                    'customers'  => $customers,
                    'taxMaster'  => $taxMaster,
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
}
