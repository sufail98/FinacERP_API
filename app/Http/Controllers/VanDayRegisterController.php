<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VanDayRegister;
use Illuminate\Support\Facades\DB;


class VanDayRegisterController extends Controller
{

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            DB::select(
                'SELECT public.insert_vandayregister(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    (VanDayRegister::max('DayRegisterId') ?? 0) + 1,
                    $request->ExecutiveId,
                    $request->DriverName,
                    $request->IsOnDuty,
                    $request->StartKM,
                    $request->EndKM,
                    $request->OpenStatus,
                    $request->StartDate,
                    $request->EndDate,
                    $request->AreaId,
                    $request->RouteId,
                    $request->VehicleNo,
                    now()->format('Y-m-d H:i:s'),
                    $request->CreatedUser,
                    $request->branchId
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Inserted Successfully',
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

    public function update(Request $request)
    {
        DB::beginTransaction();

        try {

            DB::select(
                'SELECT public.update_vandayregister(?, ?, ?, ?, ?, ?, ?)',
                [
                    $request->DayRegisterId,
                    $request->OpenStatus,
                    $request->EndKM,
                    $request->RouteId,
                    $request->EndDate,
                    now()->format('Y-m-d H:i:s'),
                    $request->ModifiedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Updated Successfully'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function index($branchId = null)
    {
        try {

            $data = DB::select(
                'SELECT * FROM public.get_all_vandayregister(?)',
                [$branchId]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message'=> $e->getMessage()
            ], 500);
        }
    }
}
