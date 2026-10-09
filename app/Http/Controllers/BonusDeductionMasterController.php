<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BonusDeductionMaster;
use App\Models\BonusDeductionDetails;

use Illuminate\Support\Facades\DB;

class BonusDeductionMasterController extends Controller
{
    public function index(Request $request)
    {
        try {

            $branchId = $request->branchId ?? null;

            // ✅ Get MASTER list
            $masters = DB::select(
                'SELECT * FROM public.get_all_bonus_deduction_master(?)',
                [$branchId]
            );

            // ✅ Get DETAILS list
            $details = DB::select(
                'SELECT * FROM public.get_all_bonus_deduction_details(?)',
                [$branchId]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => [
                    'masters' => $masters,
                    'details' => $details
                ]
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message'=> $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {

            // ✅ Get MASTER
            $master = DB::select(
                'SELECT * FROM public.get_bonus_deduction_master_by_id(?)',
                [$id]
            );

            if (empty($master)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message'=> 'Record not found'
                ], 404);
            }

            // ✅ Get DETAILS
            $details = DB::select(
                'SELECT * FROM public.get_bonus_deduction_details_by_master_id(?)',
                [$id]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => [
                    'master'  => $master[0],   // single record
                    'details' => $details      // multiple rows
                ]
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

        public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // ✅ STEP 1: Generate Master ID
            $masterId = (BonusDeductionMaster::max('bonusDeductionMasterId') ?? 0) + 1;

            // ✅ STEP 2: Insert Master
            DB::statement(
                'SELECT public.insert_bonus_deduction_master(?, ?, ?, ?, ?, ?, ?)',
                [
                    $masterId,
                    $request->date,
                    $request->month,
                    $request->narration,
                    $request->branchId,
                    now(),
                    $request->CreatedUser
                ]
            );

            // ✅ STEP 3: Insert Details (LOOP)
            foreach ($request->details as $detail) {

                $detailId = (BonusDeductionDetails::max('bonusDeductionDetailsId') ?? 0) + 1;

                DB::statement(
                    'SELECT public.insert_bonus_deduction_details(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $detailId,
                        $masterId, // 🔥 IMPORTANT (use inserted masterId)
                        $detail['employeeId'],
                        $detail['bonus'],
                        $detail['deduction'],
                        $detail['bonusNarration'],
                        $detail['deductionNarration'],
                        $request->branchId,
                        now(),
                        $request->CreatedUser,
                        null, // ModifiedDate
                        null  // ModifiedUser
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'BonusDeductionMaster Inserted Successfully'
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

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            // ✅ STEP 1: Check master exists
            $master = BonusDeductionMaster::find($id);

            if (!$master) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message'=> 'Record not found',
                ], 404);
            }

            // ✅ STEP 2: Update MASTER
            DB::statement(
                'SELECT public.update_bonus_deduction_master(?, ?, ?, ?, ?, ?, ?)',
                [
                    $id,
                    $request->date,
                    $request->month,
                    $request->narration,
                    $request->branchId,
                    now(),
                    $request->ModifiedUser
                ]
            );

            // ✅ STEP 3: Delete OLD DETAILS
            DB::statement(
                'SELECT public.delete_bonus_deduction_details_by_master(?)',
                [$id]
            );

            // ✅ STEP 4: Insert NEW DETAILS
            foreach ($request->details as $detail) {

                $detailId = (BonusDeductionDetails::max('bonusDeductionDetailsId') ?? 0) + 1;

                DB::statement(
                    'SELECT public.insert_bonus_deduction_details(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $detailId,
                        $id,
                        $detail['employeeId'],
                        $detail['bonus'],
                        $detail['deduction'],
                        $detail['bonusNarration'],
                        $detail['deductionNarration'],
                        $request->branchId,
                        now(),                      // CreatedDate
                        $request->ModifiedUser,     // CreatedUser (or use CreatedUser if needed)
                        now(),                      // ModifiedDate
                        $request->ModifiedUser
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'BonusDeduction updated successfully'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            // ✅ Check if master exists
            $master = BonusDeductionMaster::find($id);

            if (!$master) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message'=> 'Record not found'
                ], 404);
            }

            // 🔴 STEP 1: Delete DETAILS first
            DB::statement(
                'SELECT public.delete_bonus_deduction_details_by_master(?)',
                [$id]
            );

            // 🟢 STEP 2: Delete MASTER
            DB::statement(
                'SELECT public.delete_bonus_deduction_master(?)',
                [$id]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Deleted successfully'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
