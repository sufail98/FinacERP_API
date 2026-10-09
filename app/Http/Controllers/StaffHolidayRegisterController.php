<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StaffHolidayRegister;
use Illuminate\Support\Facades\DB;


class StaffHolidayRegisterController extends Controller
{

    public function index(Request $request)
    {
        try {

            $result = DB::select(
                'SELECT * FROM public.get_all_staffholidayregister(?, ?)',
                [
                    $request->branchId,
                    $request->userId
                ]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function show($holidayId)
    {
        try {

            $result = DB::select(
                'SELECT * FROM public.get_staffholidayregister_byid(?)',
                [$holidayId]
            );

            if (empty($result)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'holidayId not found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result[0]
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
            DB::select(
                'SELECT public.insert_staffholidayregister(?, ?, ?, ?, ?, ?, ?)',
                [
                    (StaffHolidayRegister::max('holidayId') ?? 0) + 1,
                    $request->date,
                    $request->holidayName,
                    $request->narration,
                    $request->branchId,
                    now()->format('Y-m-d H:i:s'),
                    $request->CreatedUser
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


    public function update(Request $request, $holidayId)
    {
        DB::beginTransaction();

        try {
            $StaffHolidayRegister = StaffHolidayRegister::find($holidayId);  // get the model

            if (!$StaffHolidayRegister) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'holidayId not found',
                ], 404);
            }

            DB::select(
                'SELECT public.update_staffholidayregister(?, ?, ?, ?, ?, ?, ?)',
                [
                    $holidayId,              // ✅ numeric ID
                    $request->date,
                    $request->holidayName,
                    $request->narration,          // varchar
                    $request->branchId,           // integer
                    now()->format('Y-m-d H:i:s'), // timestamp
                    $request->ModifiedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'staffholiday updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $holidayId = StaffHolidayRegister::find($id);

            if (empty($holidayId)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'holidayId not found',
                ], 404);
            }

            DB::select('SELECT delete_staffholidayregister(?)', [$id]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'holidayId deleted successfully',
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
}
