<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\JournalMaster;
use App\Models\JournalDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalMasetrController extends Controller
{
    /**
     * List Journal Voucher
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $journal = DB::select('SELECT * FROM public.get_journal_master_list(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($journal);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Journal Voucher Retrieved Successfully',
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
     * Get Journal Voucher by id
     */
    public function show($id)
    {
        $journal = DB::select(
            'SELECT * FROM public.get_journal_master_byid(?)',
            [$id]
        );

        if (empty($journal)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Journal Voucher Not Found',
            ], 404);
        }

        $journal = $journal[0];

        // Convert Documents to full URLs
        if (!empty($journal->Documents)) {

            $documents = json_decode($journal->Documents, true);

            if (is_array($documents)) {
                $journal->Documents = array_map(function ($file) {
                    return asset($file);
                }, $documents);
            } else {
                $journal->Documents = [];
            }
        } else {
            $journal->Documents = [];
        }

        // Get Journal Details
        $journal->journalDetails = DB::select(
            'SELECT * FROM public.get_journal_details_byid(?)',
            [$journal->JournalMasterId]
        );

        // Get Credit Party Balance
        $cr = DB::select(
            'SELECT * FROM public.party_balance_by_voucher_type_and_no(?::varchar, ?::integer, ?::integer, ?::varchar)',
            [
                'Journal Voucher',
                (int) $journal->JournalMasterId,
                (int) $journal->branchId,
                'Cr'
            ]
        );

        // Get Debit Party Balance
        $dr = DB::select(
            'SELECT * FROM public.party_balance_by_voucher_type_and_no(?::varchar, ?::integer, ?::integer, ?::varchar)',
            [
                'Journal Voucher',
                (int) $journal->JournalMasterId,
                (int) $journal->branchId,
                'Dr'
            ]
        );

        // Merge Party Details
        $journal->partyDetails = collect($cr)
            ->merge($dr)
            ->unique('balanceId')
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Journal Voucher Retrieved Successfully',
            'data' => $journal,
        ], 200);
    }

    /**
     * Create Journal Voucher
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // Validate multiple documents
            $request->validate([
                'Documents'   => 'nullable|array',
                'Documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:20480'
            ]);

            $uploadedFiles = [];

            // Upload multiple files
            if ($request->hasFile('Documents')) {

                $destinationPath = public_path('storage/journalmasterdocuments');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                foreach ($request->file('Documents') as $file) {

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    $file->move($destinationPath, $fileName);

                    $uploadedFiles[] = 'storage/journalmasterdocuments/' . $fileName;
                }
            }

            // Store as JSON
            $Documents = !empty($uploadedFiles)
                ? json_encode($uploadedFiles)
                : null;

            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);
            // Insert Journal Master
            $result = DB::selectOne(
                'SELECT public.insert_journal_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS "JournalMasterId"',
                [
                    $codeData['table_pk'],
                    $codeData['voucherNo'],
                    $codeData['suffixPrefixId'],
                    $codeData['voucherCode'],
                    $request->date,
                    $request->narration,
                    $request->totalAmount,
                    $request->costCentreId,
                    $request->referenceNo,
                    $request->referenceDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->branchId,
                    $createdDate,
                    $request->createdUser,
                    $Documents
                ]
            );

            $journalMasterId = $result->JournalMasterId;

            $journal = JournalMaster::find($journalMasterId);

            // Insert into journalDetails
            if ($request->has('journalDetails')) {
                foreach ($request->journalDetails as $sd) {
                    $result = DB::select('SELECT public.insert_journal_details(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS "out_JournalDetailsId"', [
                        (JournalDetails::max('JournalDetailsId') ?? 0) + 1,
                        $journal->JournalMasterId,
                        $sd['ledgerId'],
                        $sd['credit'],
                        $sd['debit'],
                        $sd['currencyConversionId'],
                        $request->exchangeRate,
                        $request->exchangeDate,
                        $sd['lineIndex'],
                        $sd['Narration'],
                        $sd['RefNo'],
                        $sd['costCentreId'],
                        $sd['branchId'],
                        $createdDate,
                        $request->createdUser,
                        NULL, // ModifiedDate
                        NULL  // ModifiedUser
                    ]);

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $journal->date,               // date
                        'Journal Voucher',             // voucherType
                        $journal->JournalMasterId,          // voucherNo
                        $sd['ledgerId'],              // ledgerId
                        $sd['debit'],                // debit
                        $sd['credit'],                // credit
                        $journal->JournalNo,         // InvoiceNo
                        $sd['Narration'],             // Narration
                        $sd['costCentreId'],          // costCentreId
                        'Journal',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $request->branchId,              // branchId
                        $journal->CreatedUser,       // CreatedUser
                        $createdDate
                    ]);
                }
            }

            if ($request->has('partyDetails')) {

                foreach ($request->partyDetails as $pd) {

                    if ($pd['referenceType'] === 'Against') {

                        // Existing bill reference
                        $partyVoucherType        = $pd['voucherType'];
                        $partyVoucherNo          = $pd['masterId'];

                        $partyAgainstVoucherType = 'Journal Voucher';
                        $partyAgainstvoucherNo   = $journal->JournalMasterId;
                    } else {

                        // New / OnAccount
                        $partyVoucherType        = 'Journal Voucher';
                        $partyVoucherNo          = $journal->JournalMasterId;

                        $partyAgainstVoucherType = 'NA';
                        $partyAgainstvoucherNo   = 'NA';
                    }

                    DB::select(
                        'SELECT * FROM insert_party_balance(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?
            )',
                        [
                            $journal->date,                 // date
                            $pd['ledgerId'],               // ledgerId

                            $partyVoucherType,             // voucherType
                            $partyVoucherNo,               // voucherNo

                            $partyAgainstVoucherType,      // againstVoucherType
                            $partyAgainstvoucherNo,        // againstvoucherNo

                            $pd['referenceType'],          // referenceType

                            $pd['debit'],                 // debit
                            $pd['credit'],                // credit

                            $pd['creditPeriod'],           // creditPeriod
                            $pd['currencyConversionId'],   // currencyConversionId

                            $pd['invoiceNo'],              // invoiceNo
                            $pd['referenceNo'],            // referenceNo
                            $pd['BillAmount'],             // BillAmount
                            $pd['invoiceDate'],            // invoiceDate

                            $journal->costCentreId,        // costCentreId
                            $request->exchangeRate,        // exchangeRate
                            $request->exchangeDate,        // exchangeDate
                            $journal->branchId,            // branchId
                            $request->createdUser,          // CreatedUser
                            $createdDate
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Journal Voucher Inserted Successfully'
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
     * Update Journal Voucher
     */
    public function update(Request $request, $id)
    {
        $journal = JournalMaster::find($id);

        if (!$journal) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Journal Voucher Not Found',
            ], 404);
        }

        $oldValue = $journal->totalAmount;

        DB::beginTransaction();

        try {

            // Keep existing documents if no new files are uploaded
            $Documents = $journal->Documents;

            if ($request->hasFile('Documents')) {

                $request->validate([
                    'Documents'   => 'required|array',
                    'Documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx|max:20480'
                ]);

                // Delete old files
                if (!empty($journal->Documents)) {

                    $oldFiles = json_decode($journal->Documents, true);

                    if (is_array($oldFiles)) {
                        foreach ($oldFiles as $oldFile) {

                            $filePath = public_path($oldFile);

                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                        }
                    }
                }

                // Create folder if not exists
                $destinationPath = public_path('storage/journalmasterdocuments');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $uploadedFiles = [];

                foreach ($request->file('Documents') as $file) {

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();

                    $file->move($destinationPath, $fileName);

                    $uploadedFiles[] = 'storage/journalmasterdocuments/' . $fileName;
                }

                // Store new document paths as JSON
                $Documents = json_encode($uploadedFiles);
            }
            $branchId   = $request->branchId;
            $modifiedDate = getBranchNow($branchId);
            // Update Journal Master
            DB::select(
                'SELECT * FROM public.update_journal_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $id,
                    $request->date,
                    $request->narration,
                    $request->totalAmount,
                    $request->costCentreId,
                    $request->ReferenceNo,
                    $request->ReferenceDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->branchId,
                    $modifiedDate,
                    $request->ModifiedUser,
                    $Documents
                ]
            );
            $journal->refresh();
            $originalCreatedDate = $journal->CreatedDate ?? $journal->created_at;
            $originalCreatedUser = $journal->CreatedUser ?? $journal->created_user;
            if ($request->has('journalDetails')) {
                DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                    'Journal Voucher', // voucherType
                    $journal->JournalMasterId, // voucherNo
                    $request->branchId   // branchId
                ]);
                DB::select('SELECT delete_journal_details_byid(?)', [$journal->JournalMasterId]);

                foreach ($request->journalDetails as $sd) {
                    $detailBranchId = (int) ($sd['branchId'] ?? $branchId);
                    $detailDate = getBranchNow($detailBranchId);
                    $result = DB::select('SELECT public.insert_journal_details(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS "out_JournalDetailsId"', [
                        (JournalDetails::max('JournalDetailsId') ?? 0) + 1,
                        $journal->JournalMasterId,
                        $sd['ledgerId'],
                        $sd['credit'],
                        $sd['debit'],
                        $sd['currencyConversionId'],
                        $request->exchangeRate,
                        $request->exchangeDate,
                        $sd['lineIndex'],
                        $sd['Narration'],
                        $sd['RefNo'],
                        $sd['costCentreId'],
                        $sd['branchId'],
                        $originalCreatedDate,
                        $originalCreatedUser,
                        $detailDate,
                        $request->ModifiedUser,

                    ]);

                    $result = DB::select('SELECT * FROM insert_ledger_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $journal->date,               // date
                        'Journal Voucher',             // voucherType
                        $journal->JournalMasterId,          // voucherNo
                        $sd['ledgerId'],              // ledgerId
                        $sd['debit'],                // debit
                        $sd['credit'],                // credit
                        $journal->JournalNo,         // InvoiceNo
                        $sd['Narration'],             // Narration
                        $sd['costCentreId'],          // costCentreId
                        'Journal',                   // PostingType
                        $request->exchangeRate,      // exchangeRate
                        $request->exchangeDate,      // exchangeDate
                        $request->branchId,             // branchId
                        $journal->CreatedUser,       // CreatedUser
                        $modifiedDate
                    ]);
                }
            }

            if ($request->has('partyDetails')) {

                DB::select('SELECT delete_partybalance(?, ?)', [
                    'Journal Voucher',
                    $journal->JournalMasterId
                ]);

                foreach ($request->partyDetails as $pd) {

                    if ($pd['referenceType'] === 'Against') {

                        // Existing bill reference
                        $partyVoucherType        = $pd['voucherType'];
                        $partyVoucherNo          = $pd['masterId'];

                        $partyAgainstVoucherType = 'Journal Voucher';
                        $partyAgainstvoucherNo   = $journal->JournalMasterId;
                    } else {

                        // New / OnAccount
                        $partyVoucherType        = 'Journal Voucher';
                        $partyVoucherNo          = $journal->JournalMasterId;

                        $partyAgainstVoucherType = 'NA';
                        $partyAgainstvoucherNo   = 'NA';
                    }

                    DB::select(
                        'SELECT * FROM insert_party_balance(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?
            )',
                        [
                            $journal->date,                 // date
                            $pd['ledgerId'],               // ledgerId

                            $partyVoucherType,             // voucherType
                            $partyVoucherNo,               // voucherNo

                            $partyAgainstVoucherType,      // againstVoucherType
                            $partyAgainstvoucherNo,        // againstvoucherNo

                            $pd['referenceType'],          // referenceType

                            $pd['debit'],                  // debit
                            $pd['credit'],                 // credit

                            $pd['creditPeriod'],           // creditPeriod
                            $pd['currencyConversionId'],   // currencyConversionId

                            $pd['invoiceNo'],              // invoiceNo
                            $pd['referenceNo'],            // referenceNo
                            $pd['BillAmount'],             // BillAmount
                            $pd['invoiceDate'],            // invoiceDate

                            $journal->costCentreId,        // costCentreId
                            $request->exchangeRate,        // exchangeRate
                            $request->exchangeDate,        // exchangeDate
                            $journal->branchId,            // branchId
                            $request->ModifiedUser,         // ModifiedUser
                            $modifiedDate
                        ]
                    );
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Journal Voucher Update',
                'Journal Voucher',
                'UPDATED',
                $journal->branchId,
                $request->ModifiedUser ?? 'System',
                $journal->JournalMasterId,
                $journal->voucherNo,
                $journal->date,
                $oldValue,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Journal Voucher Updated Successfully'
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
     * Delete Journal Voucher
     */
    public function destroy($id)
    {
        $journal = JournalMaster::find($id);

        if (!$journal) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Journal Voucher Not Found',
            ], 404);
        }
        $oldValue = $journal->totalAmount;
        DB::beginTransaction();
        try {
            DB::select('SELECT delete_journal_details_byid(?)', [$id]);
            DB::statement('SELECT public.delete_journal_master_byid(?)', [$id]);

            DB::select('SELECT delete_ledger_posting(?, ?, ?)', [
                'Journal Voucher', // voucherType
                $journal->JournalMasterId, // voucherNo
                null   // branchId
            ]);

            DB::select('SELECT delete_partybalance(?, ?)', [
                'Journal Voucher', // voucherType
                $journal->JournalMasterId // voucherNo
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Journal Voucher Delete',
                'Journal Voucher',
                'DELETED',
                $journal->branchId,
                $journal->ModifiedUser ?? 'System',
                $journal->JournalMasterId,
                $journal->voucherNo,
                $journal->date,
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Journal Voucher Deleted Successfully',
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

    public function journalReport(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM "journal_report_fill"(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $request->from_date,
                    $request->to_date,
                    false,
                    $request->branch_id,
                    $request->currency_id,
                    $request->costcentre_id ?? 0,
                    $request->ledger_id ?? 0,
                    $request->all_costcentre,
                    $request->posted_status,
                    $request->mode
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Journal Report Fetched Successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
