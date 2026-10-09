<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManufacturingJournalController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();
        $category = $request->category;
        try {

            $branchId    = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId      = (int) $request->input('yearId', 1);
            $createdUser = $request->input('CreatedUser', 'admin');


            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            $createdDate = getBranchNow($branchId);

            $data = DB::select(
                'SELECT public.insert_manufacturing_journal_master(
                ?::bigint, ?::varchar, ?::integer, ?::varchar, ?::timestamp,
                ?::text, ?::integer, ?::integer, ?::integer, ?::integer,
                ?::integer, ?::timestamp, ?::varchar
            ) AS out_masterid',
                [
                    $codeData['table_pk'],
                    $codeData['voucherNo'],
                    $codeData['suffixPrefixId'],
                    $codeData['voucherCode'],
                    $request->input('date', now()),
                    $request->input('narration'),
                    $request->input('godownId'),
                    $request->input('batchId'),
                    $request->input('rackId'),
                    $request->input('costCentreId'),
                    $branchId,
                    $createdDate,
                    $createdUser
                ]
            );

            $journalMasterId = $data[0]->out_masterid;
            $masterDate      = $request->input('date', now());

            if ($request->has('details')) {

                $details = $request->input('details');

                if (is_string($details)) {
                    $details = json_decode($details, true);
                }

                $maxDetails1Id = DB::table('tbl_manufacturingjournaldetails1')
                    ->max('JournalDetails1Id') ?? 0;

                $maxDetails2Id = DB::table('tbl_manufacturingjournaldetails2')
                    ->max('JournalDetails2Id') ?? 0;

                foreach ($details as $item) {


                    $maxDetails1Id++;
                    $details1Id = $maxDetails1Id;

                    $finishedQty = (float) ($item['quantity'] ?? 0);
                    $finishedUnitId = (int) ($item['unitId'] ?? 0);
                    $finishedConversionRate = (float) ($item['conversionRate'] ?? 1);

                    DB::select(
                        'SELECT public.insert_manufacturing_journal_details1(
                        ?::bigint, ?::bigint, ?::varchar, ?::numeric, ?::integer,
                        ?::numeric, ?::text, ?::integer, ?::timestamp, ?::varchar,?::timestamp, ?::varchar
                    )',
                        [
                            $details1Id,
                            $journalMasterId,
                            $item['productCode'] ?? null,
                            $finishedQty,
                            $finishedUnitId,
                            $finishedConversionRate,
                            $item['narration'] ?? null,
                            $branchId,
                            $createdDate,
                            $createdUser,
                            null,
                            null
                        ]
                    );

                    // ✅ INWARD STOCK POSTING
                    // $inwardStockQty = $finishedQty * $finishedConversionRate;
                    $finishedCategory = strtolower(trim($item['category'] ?? ''));

                    $inwardStockQty = $finishedQty * $finishedConversionRate;

                    if ($finishedCategory !== 'non inventory') {

                        DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                            $masterDate,
                            'Manufacturing Journal',
                            $journalMasterId,
                            $item['productCode'] ?? null,
                            $request->input('batchId'),
                            $request->input('godownId'),
                            $request->input('rackId'),
                            $finishedQty,
                            $finishedUnitId,
                            0,
                            $inwardStockQty,
                            0,
                            0,
                            $item['baseUnitId'] ?? $finishedUnitId,
                            null,
                            null,
                            $branchId,
                            $createdUser,
                            $createdDate
                        ]);
                    }
                    if (!empty($item['materials'])) {

                        foreach ($item['materials'] as $mat) {

                            $maxDetails2Id++;
                            $rawQty = (float) ($mat['quantity'] ?? 0);
                            $rawUnitId = (int) ($mat['unitId'] ?? 0);
                            $rawConversionRate = (float) ($mat['conversionRate'] ?? 1);
                            $rawRate = (float) ($mat['rate'] ?? 0);

                            DB::select(
                                'SELECT public.insert_manufacturing_journal_details2(
                                ?,?,?,?,?,?,?,?,?,?,?,?,?,?
                            )',
                                [
                                    (int)$maxDetails2Id,
                                    (int)$details1Id,
                                    (int)$journalMasterId,
                                    $mat['productCode'] ?? null,
                                    (float)$rawQty,
                                    (int)$rawUnitId,
                                    (float)$rawConversionRate,
                                    (float)$rawRate,
                                    (float)($mat['amount'] ?? 0),
                                    (int)$branchId,
                                    $createdDate,
                                    (string)$createdUser,
                                    null,
                                    null
                                ]
                            );

                            // ✅ OUTWARD STOCK POSTING
                            $rawCategory = strtolower(trim($mat['category'] ?? ''));

                            $outwardStockQty = $rawQty * $rawConversionRate;

                            if ($rawCategory !== 'non inventory') {

                                DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                                    $masterDate,
                                    'Manufacturing Journal',
                                    $journalMasterId,
                                    $mat['productCode'] ?? null,
                                    $request->input('batchId'),
                                    $mat['godownId'] ?? $request->input('godownId'),
                                    $mat['rackId'] ?? $request->input('rackId'),
                                    $rawQty,
                                    $rawUnitId,
                                    $rawRate,
                                    0,
                                    $outwardStockQty,
                                    $rawRate,
                                    $mat['baseUnitId'] ?? $rawUnitId,
                                    null,
                                    null,
                                    $branchId,
                                    $createdUser,
                                    $createdDate
                                ]);
                            }
                        }
                    }
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Manufacturing Journal insert',
                'Manufacturing Journal',
                'INSERTED',
                $branchId,
                $createdUser ?? 'System',
                $codeData['table_pk'],
                $codeData['voucherCode'],
                $request->date,
                null,
                null
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Manufacturing Journal Created Successfully',
                'id'      => $journalMasterId
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

    public function show($id)
    {
        try {

            $response = DB::selectOne(
                'SELECT public.get_manufacturing_journal_byid(?::bigint) AS result',
                [$id]
            );

            if (!$response || !$response->result) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Manufacturing Journal not found'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Manufacturing Journal Retrieved Successfully',
                'data'    => json_decode($response->result)
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {
        try {

            $branchId = $request->branchId;
            $fromDate = $request->fromDate;
            $toDate   = $request->toDate;

            $data = DB::selectOne(
                'SELECT public.get_manufacturing_journal_list_by_branch(
                ?::integer,
                ?::date,
                ?::date
            ) AS result',
                [
                    $branchId,
                    $fromDate,
                    $toDate
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Manufacturing Journals Retrieved Successfully',
                'data'    => json_decode($data->result)
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $id = (int) $id;
            $createdUser = $request->input('CreatedUser', 'admin');

            $now = now()->format('Y-m-d H:i:s');

            $master = DB::table('tbl_manufacturingjournalmaster')
                ->where('JournalMasterId', $id)
                ->first();

            if (!$master) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Manufacturing Journal not found'
                ], 404);
            }

            $branchId   = (int) $master->branchId;
            $masterDate = $request->input('date', $master->date);
            $modifiedDate = getBranchNow($branchId);
            $originalCreatedDate = $master->CreatedDate ?? $master->created_at;
            $originalCreatedUser = $master->CreatedUser ?? $master->created_user;
            // ✅ 1️⃣ UPDATE MASTER
            DB::select(
                'SELECT public.update_manufacturing_journal(
                ?::bigint,
                ?::varchar,
                ?::timestamp,
                ?::text,
                ?::integer,
                ?::integer,
                ?::integer,
                ?::integer,
                ?::varchar,
                ?::varchar,
                ?::integer,
                ?::timestamp,
                ?::varchar
            )',
                [
                    $id,
                    $request->input('journalNo', $master->JournalNo),
                    $masterDate,
                    $request->input('narration', $master->narration),
                    $request->input('godownId', $master->godownId),
                    $request->input('batchId', $master->batchId),
                    $request->input('rackId', $master->rackId),
                    $request->input('costCentreId', $master->costCentreId),
                    $request->input('postedStatus', $master->postedStatus),
                    $request->input('postedBy', $master->postedBy),
                    $branchId,
                    $modifiedDate,
                    $createdUser
                ]
            );

            // ✅ 2️⃣ DELETE OLD STOCK POSTINGS
            DB::select(
                'SELECT delete_stock_posting(?, ?)',
                ['Manufacturing Journal', $id]
            );

            // ✅ 3️⃣ DELETE OLD DETAILS
            DB::table('tbl_manufacturingjournaldetails2')
                ->where('JournalMasterId', $id)
                ->delete();

            DB::table('tbl_manufacturingjournaldetails1')
                ->where('JournalMasterId', $id)
                ->delete();

            // ✅ 4️⃣ RE-INSERT DETAILS
            if ($request->has('details')) {

                $details = $request->input('details');

                if (is_string($details)) {
                    $details = json_decode($details, true);
                }

                $maxDetails1Id = DB::table('tbl_manufacturingjournaldetails1')
                    ->max('JournalDetails1Id') ?? 0;

                $maxDetails2Id = DB::table('tbl_manufacturingjournaldetails2')
                    ->max('JournalDetails2Id') ?? 0;

                foreach ($details as $item) {

                    // =========================
                    // ✅ INSERT DETAILS1 (INWARD)
                    // =========================
                    $maxDetails1Id++;
                    $details1Id = $maxDetails1Id;

                    $finishedQty = (float) ($item['quantity'] ?? 0);
                    $finishedUnitId = (int) ($item['unitId'] ?? 0);
                    $finishedConversionRate = (float) ($item['conversionRate'] ?? 1); // ✅ NEW

                    DB::select(
                        'SELECT public.insert_manufacturing_journal_details1(
                        ?::bigint, ?::bigint, ?::varchar, ?::numeric, ?::integer,
                        ?::numeric, ?::text, ?::integer, ?::timestamp, ?::varchar,?::timestamp, ?::varchar
                    )',
                        [
                            $details1Id,
                            $id,                              // ✅ JournalMasterId
                            $item['productCode'] ?? null,
                            $finishedQty,
                            $finishedUnitId,
                            $finishedConversionRate,          // ✅ NEW PARAM
                            $item['narration'] ?? null,
                            $branchId,
                            $originalCreatedDate,
                            $originalCreatedUser,
                            $modifiedDate,
                            $createdUser
                        ]
                    );

                    // ✅ INWARD STOCK POSTING
                    // ✅ INWARD STOCK POSTING (Skip Non Inventory)
                    $finishedCategory = strtolower(trim($item['category'] ?? ''));

                    $inwardStockQty = $finishedQty * $finishedConversionRate;

                    if ($finishedCategory !== 'non inventory') {

                        DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                            $masterDate,
                            'Manufacturing Journal',
                            $id,
                            $item['productCode'] ?? null,
                            $request->input('batchId', $master->batchId),
                            $request->input('godownId', $master->godownId),
                            $request->input('rackId', $master->rackId),
                            $finishedQty,
                            $finishedUnitId,
                            0,
                            $inwardStockQty,
                            0,
                            0,
                            $item['baseUnitId'] ?? $finishedUnitId,
                            null,
                            null,
                            $branchId,
                            $createdUser,
                            $modifiedDate
                        ]);
                    }
                    // =========================
                    // ✅ INSERT DETAILS2 (OUTWARD)
                    // =========================
                    if (!empty($item['materials'])) {

                        foreach ($item['materials'] as $mat) {

                            $maxDetails2Id++;
                            $rawQty = (float) ($mat['quantity'] ?? 0);
                            $rawUnitId = (int) ($mat['unitId'] ?? 0);
                            $rawConversionRate = (float) ($mat['conversionRate'] ?? 1); // ✅ NEW
                            $rawRate = (float) ($mat['rate'] ?? 0);

                            DB::select(
                                'SELECT public.insert_manufacturing_journal_details2(
                                ?,?,?,?,?,?,?,?,?,?,?,?,?,?
                            )',
                                [
                                    (int)$maxDetails2Id,
                                    (int)$details1Id,
                                    (int)$id,                          // ✅ JournalMasterId
                                    $mat['productCode'] ?? null,
                                    (float)$rawQty,
                                    (int)$rawUnitId,
                                    (float)$rawConversionRate,         // ✅ NEW PARAM
                                    (float)$rawRate,
                                    (float)($mat['amount'] ?? 0),
                                    (int)$branchId,
                                    $originalCreatedDate,
                                    $originalCreatedUser,
                                    $modifiedDate,
                                    (string)$createdUser
                                ]
                            );

                            // ✅ OUTWARD STOCK POSTING
                            // ✅ OUTWARD STOCK POSTING (Skip Non Inventory)
                            $rawCategory = strtolower(trim($mat['category'] ?? ''));

                            $outwardStockQty = $rawQty * $rawConversionRate;

                            if ($rawCategory !== 'non inventory') {

                                DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                                    $masterDate,
                                    'Manufacturing Journal',
                                    $id,
                                    $mat['productCode'] ?? null,
                                    $request->input('batchId', $master->batchId),
                                    $mat['godownId'] ?? $request->input('godownId', $master->godownId),
                                    $mat['rackId'] ?? $request->input('rackId', $master->rackId),
                                    $rawQty,
                                    $rawUnitId,
                                    $rawRate,
                                    0,
                                    $outwardStockQty,
                                    $rawRate,
                                    $mat['baseUnitId'] ?? $rawUnitId,
                                    null,
                                    null,
                                    $branchId,
                                    $createdUser,
                                    $modifiedDate
                                ]);
                            }
                        }
                    }
                }
            }

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Manufacturing Journal insert',
                'Manufacturing Journal',
                'INSERTED',
                $branchId,
                $modifiedDate ?? 'System',
                $id,
                $master->voucherNo,
                $masterDate,
                null,
                null
            );
            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Manufacturing Journal Updated Successfully',
                'id'      => $id
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        DB::beginTransaction();

        try {


            $id = (int) $id;

            // ✅ Check if journal exists
            $journal = DB::table('tbl_manufacturingjournalmaster')
                ->where('JournalMasterId', $id)
                ->first();

            if (!$journal) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Manufacturing Journal not found'
                ], 404);
            }

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Manufacturing Journal delete',
                'Manufacturing Journal',
                'DELETED',
                $journal->branchId,
                $journal->modifiedDate ?? 'System',
                $id,
                $journal->voucherNo,
                $journal->date,
                null,
                null
            );
            // ✅ 1️⃣ DELETE STOCK POSTINGS
            DB::select(
                'SELECT delete_stock_posting(?, ?)',
                [
                    'Manufacturing Journal',
                    $id
                ]
            );

            // ✅ 2️⃣ DELETE DETAILS1 & DETAILS2
            DB::select(
                'SELECT public.delete_manufacturing_journal_details(?::bigint)',
                [$id]
            );

            // ✅ 3️⃣ DELETE MASTER
            DB::select(
                'SELECT public.delete_manufacturing_journal_master(?::bigint)',
                [$id]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Manufacturing Journal Deleted Successfully',
                'id'      => $id
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
