<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    public function index()
    {
       try {
        $items = DB::select('SELECT * FROM public.get_all_units()');

        if (empty($items)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'No units found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Unit list',
            'data'    => $items,
        ], 200);

        } catch (\Throwable $e) {
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
        // ✅ Call PostgreSQL function
        $result = DB::select(
            'SELECT * FROM public.get_unit_byId(?)',
            [$id]
        );

        if (empty($result)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Unit not found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Unit details',
            'data'    => $result[0]
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
        // ✅ Check for duplicate UnitName (case-insensitive)
        if ($request->has('UnitName') &&
            Unit::whereRaw('LOWER("UnitName") = ?', [strtolower($request->UnitName)])->exists()) {

            return response()->json([
                'status' => 409,
                'error'  => true,
                'message'=> 'Unit Name Already Exists !',
            ], 409);
        }

        // ✅ Generate unitId (max + 1)
        $unitId = (Unit::max('unitId') ?? 0) + 1;

        // ✅ Call PostgreSQL insert function
        DB::select(
            'SELECT public.insert_unit(?, ?, ?, ?, ?, ?)',
            [
                $unitId,
                $request->UnitName,
                $request->Narration,
                $request->branchId,
                $request->CreatedUser,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s')
            ]
        );

        DB::commit();

        return response()->json([
            'status' => 201,
            'error'  => false,
            'message'=> 'Unit created successfully',
            
        ], 201);

        } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage(),
        ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
        // ✅ Check if unit exists
        $exists = DB::select(
            'SELECT 1 FROM public.get_unit_byId(?)',
            [$id]
        );

        if (empty($exists)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Unit not found',
            ], 404);
        }

        // ✅ Duplicate UnitName check (case-insensitive)
        if ($request->has('UnitName') &&
            DB::table('tbl_Unit')
                ->whereRaw('LOWER("UnitName") = ?', [strtolower($request->UnitName)])
                ->where('unitId', '!=', $id)
                ->exists()) {

            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Unit Name Already Exists !',
            ], 409);
        }

        // ✅ Call update function
        $result = DB::select(
            'SELECT public.update_unit(?, ?, ?, ?, ?, ?)',
            [
                $id,
                $request->UnitName,
                $request->Narration,
                $request->branchId,
                $request->ModifiedUser,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s')
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Unit updated successfully',
        ], 200);

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
        // ✅ Call delete function
        $result = DB::select(
            'SELECT public.delete_unit(?)',
            [$id]
        );

        // ✅ Handle not found
        if (empty($result) || $result[0]->delete_unit === null) {
            DB::rollBack();
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Unit not found',
            ], 404);
        }

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Unit deleted successfully',
        ], 200);

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
