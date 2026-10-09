<?php

namespace App\Http\Controllers;

use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkLocationController extends Controller
{
    public function index()
{
    try {
        // 📌 Call PostgreSQL function
        $items = DB::select('SELECT * FROM public.get_all_work_location()');

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Work Locations',
            'data'    => $items
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
            'data'    => []
        ], 500);
    }
}

    public function show($id)
{
    try {
        $data = DB::select(
            'SELECT * FROM public.get_work_location_byId(?)',
            [$id]
        );

        if (empty($data)) {
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message'=> 'Work Location Not Found',
                'data'   => []
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message'=> 'Work Location',
            'data'   => $data[0]   // single record
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage(),
            'data'   => []
        ], 500);
    }
}


    public function store(Request $request)
{
    DB::beginTransaction();

    try {
        

        // 🔁 Duplicate check (case-insensitive + branch wise)
        $exists = DB::table('tbl_workLocation')
            ->whereRaw('LOWER("workLocationName") = ?', [strtolower($request->workLocationName)])
            ->where('branchId', $request->branchId)
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Work Location Name Already Exists!',
            ], 409);
        }

        // 🔢 Generate next workLocationId
        $workLocationId = (DB::table('tbl_workLocation')->max('workLocationId') ?? 0) + 1;

        // 🕒 CreatedDate
        $createdDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');

        // 📌 Call PostgreSQL insert function
        $result = DB::selectOne(
            'SELECT public.insert_work_location(?, ?, ?, ?, ?) AS id',
            [
                $workLocationId,
                $request->workLocationName,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->CreatedUser
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 201,
            'error'   => false,
            'message' => 'Work Location Created Successfully',
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
        $result = DB::selectOne(
            'SELECT public.update_work_location(?, ?, ?, ?, ?) AS message',
            [
                $id,
                $request->workLocationName,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->ModifiedUser
            ]
        );

        DB::commit();

        // Handle function responses
        if ($result->message === 'Work Location Name Already Exists') {
            return response()->json([
                'status' => 409,
                'error'  => true,
                'message'=> $result->message,
            ], 409);
        }

        if ($result->message === 'Work Location Not Found') {
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message'=> $result->message,
            ], 404);
        }

        if (str_starts_with($result->message, 'Error')) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message'=> $result->message,
            ], 500);
        }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message'=> $result->message,
        ], 200);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage(),
        ], 500);
    }
}

    public function destroy($id)
{
    DB::beginTransaction();

    try {
        // Call PostgreSQL function
        $result = DB::select(
            'SELECT public.delete_work_location(?)',
            [$id]
        );

        DB::commit();
       
        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Deleted Successfully',
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
