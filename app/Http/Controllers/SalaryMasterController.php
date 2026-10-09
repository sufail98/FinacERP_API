<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryMaster;
use App\Models\SalaryDetails1;
use App\Models\SalaryDetails2;

use Illuminate\Support\Facades\DB;


class SalaryMasterController extends Controller
{
    public function index(Request $request)
    {
        try {

            $branchId = $request->branchId;
            $userId   = $request->userId;

            $branchId = ($branchId === 'null' || $branchId === null)
                ? 0
                : (int) $branchId;

            $userId = ($userId === 'null' || $userId === null)
                ? null
                : $userId;

            $result = DB::select(
                'SELECT * FROM public.get_all_salarymaster(?, ?)',
                [$branchId, $userId]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function show($SalaryMasterId)
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | Get Salary Master
            |--------------------------------------------------------------------------
            */

            $salaryMaster = DB::select(
                'SELECT * FROM public.get_salarymaster_byid(?)',
                [$SalaryMasterId]
            );

            if (empty($salaryMaster)) {

                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'SalaryMaster not found'
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Salary Details1 using SalaryMasterId
            |--------------------------------------------------------------------------
            */

            $salaryDetails1 = DB::select(
                'SELECT * FROM public.get_salarydetails1_by_masterid(?)',
                [$SalaryMasterId]
            );

            /*
            |--------------------------------------------------------------------------
            | Get Salary Details2 using SalaryDetails1Id
            |--------------------------------------------------------------------------
            */

            $salaryDetails2 = [];

            if (!empty($salaryDetails1)) {

                foreach ($salaryDetails1 as $detail1) {

                    $details2 = DB::select(
                        'SELECT * FROM public.get_salarydetails2_byid(?)',
                        [$detail1->SalaryDetails1Id]
                    );

                    $salaryDetails2[$detail1->SalaryDetails1Id] = $details2;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Final Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'status' => 200,
                'error'  => false,

                'data' => [
                    'SalaryMaster'   => $salaryMaster[0],
                    'SalaryDetails1' => $salaryDetails1,
                    'SalaryDetails2' => $salaryDetails2
                ]
            ], 200);

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

            /*
            |--------------------------------------------------------------------------
            | Insert Salary Master
            |--------------------------------------------------------------------------
            */

            $salaryMasterId = (SalaryMaster::max('SalaryMasterId') ?? 0) + 1;

            DB::select(
                'SELECT public.insert_salarymaster(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $salaryMasterId,
                    $request->VoucherNo,
                    $request->Date,
                    $request->Month,
                    $request->Narration,
                    $request->FinancialYearId,
                    $request->LedgerId,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Insert Salary Details 1
            |--------------------------------------------------------------------------
            */

            $salaryDetails1Id = (SalaryDetails1::max('SalaryDetails1Id') ?? 0) + 1;

            DB::select(
                'SELECT public.insert_salarydetails1(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $salaryDetails1Id,
                    $salaryMasterId,
                    $request->EmployeeId,
                    $request->SalaryDate,
                    $request->Bonus,
                    $request->Deduction,
                    $request->Lop,
                    $request->Salary,
                    $request->branchId,

                    // Created Fields
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser,

                    // Modified Fields
                    null,
                    null
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Insert Salary Details 2
            |--------------------------------------------------------------------------
            */

            if (!empty($request->SalaryDetails2)) {

                foreach ($request->SalaryDetails2 as $detail2) {

                    DB::select(
                        'SELECT public.insert_salarydetails2(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            (SalaryDetails2::max('SalaryDetails2Id') ?? 0) + 1,
                            $salaryDetails1Id,
                            $detail2['PayheadId'],
                            $detail2['Amount'],
                            $request->branchId,

                            // Created Fields
                            now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                            $request->CreatedUser,

                            // Modified Fields
                            null,
                            null
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Inserted Successfully'
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
  public function update(Request $request, $SalaryMasterId)
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Check Salary Master Exists
            |--------------------------------------------------------------------------
            */

            $salaryMaster = SalaryMaster::find($SalaryMasterId);

            if (!$salaryMaster) {

                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'SalaryMaster not found',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Update Salary Master
            |--------------------------------------------------------------------------
            */

            DB::select(
                'SELECT public.update_salarymaster(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $SalaryMasterId,
                    $request->VoucherNo,
                    $request->Date,
                    $request->Month,
                    $request->Narration,
                    $request->FinancialYearId,
                    $request->LedgerId,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Get Existing SalaryDetails1 IDs
            |--------------------------------------------------------------------------
            */

            $salaryDetails1List = DB::select(
                'SELECT "SalaryDetails1Id"
                FROM public."tbl_SalaryDetails1"
                WHERE "SalaryMasterId" = ?',
                [$SalaryMasterId]
            );

            /*
            |--------------------------------------------------------------------------
            | Delete Existing SalaryDetails2
            |--------------------------------------------------------------------------
            */

            foreach ($salaryDetails1List as $row) {

                DB::select(
                    'SELECT public.delete_salarydetails2_by_salarydetails1id(?)',
                    [$row->SalaryDetails1Id]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Delete Existing SalaryDetails1
            |--------------------------------------------------------------------------
            */

            DB::select(
                'SELECT public.delete_salarydetails1_by_salarymasterid(?)',
                [$SalaryMasterId]
            );

            /*
            |--------------------------------------------------------------------------
            | Insert New SalaryDetails1
            |--------------------------------------------------------------------------
            */

            $salaryDetails1Id =
                (SalaryDetails1::max('SalaryDetails1Id') ?? 0) + 1;

            DB::select(
                'SELECT public.insert_salarydetails1(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $salaryDetails1Id,
                    $SalaryMasterId,
                    $request->EmployeeId,
                    $request->SalaryDate,
                    $request->Bonus,
                    $request->Deduction,
                    $request->Lop,
                    $request->Salary,
                    $request->branchId,

                    // Created Fields
                    null,
                    null,

                    // Modified Fields
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Insert New SalaryDetails2
            |--------------------------------------------------------------------------
            */

            if (!empty($request->SalaryDetails2)) {

                foreach ($request->SalaryDetails2 as $detail2) {

                    $salaryDetails2Id =
                        (SalaryDetails2::max('SalaryDetails2Id') ?? 0) + 1;

                    DB::select(
                        'SELECT public.insert_salarydetails2(?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $salaryDetails2Id,
                            $salaryDetails1Id,
                            $detail2['PayheadId'],
                            $detail2['Amount'],
                            $request->branchId,

                            // Created Fields
                            null,
                            null,

                            // Modified Fields
                            now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                            $request->ModifiedUser
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Salary updated successfully',
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

        /*
        |--------------------------------------------------------------------------
        | Check SalaryMaster Exists
        |--------------------------------------------------------------------------
        */

        $salaryMaster = SalaryMaster::find($id);

        if (empty($salaryMaster)) {

            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'SalaryMaster not found',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Get SalaryDetails1 IDs
        |--------------------------------------------------------------------------
        */

        $salaryDetails1List = DB::table('tbl_SalaryDetails1')
            ->where('SalaryMasterId', $id)
            ->pluck('SalaryDetails1Id');

        /*
        |--------------------------------------------------------------------------
        | Delete SalaryDetails2
        |--------------------------------------------------------------------------
        */

        foreach ($salaryDetails1List as $details1Id) {

            DB::select(
                'SELECT public.delete_salarydetails2_by_salarydetails1id(?)',
                [$details1Id]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete SalaryDetails1
        |--------------------------------------------------------------------------
        */

        DB::select(
            'SELECT public.delete_salarydetails1_by_salarymasterid(?)',
            [$id]
        );

        /*
        |--------------------------------------------------------------------------
        | Delete SalaryMaster
        |--------------------------------------------------------------------------
        */

        DB::select(
            'SELECT public.delete_salarymaster(?)',
            [$id]
        );

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Salary deleted successfully',
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
