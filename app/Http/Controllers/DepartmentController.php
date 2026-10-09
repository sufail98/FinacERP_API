<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index()
    {
        try {

        // ✅ Call PostgreSQL function
        $departments = DB::select(
            'SELECT * FROM public.get_all_department()'
        );

        return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $departments
        ], 200);

        } catch (\Throwable $e) {

        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
         }
    }


    public function show($id)
    {
       try {

        // ✅ Call PostgreSQL function
        $department = DB::select(
            'SELECT * FROM public.get_department_byId(?)',
            [$id]
        );

        // ✅ If not found
        if (empty($department)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Department not found'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $department[0]
        ], 200);

        } catch (\Throwable $e) {

        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
        }
    }

    public function store(Request $request)
    {
       DB::beginTransaction();

       try {

        // ✅ Generate next departmentId (model style)
        $departmentId = (Department::max('departmentId') ?? 0) + 1;

        // ✅ Call PostgreSQL function
        $result = DB::select(
            'SELECT public.insert_department(?, ?, ?, ?,?) AS out_departmentId',
            [
                $departmentId,
                $request->departmentName,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->CreatedUser
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 201,
            'error'   => false,
            'message' => 'Department Inserted Successfully',
            
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

        // ✅ Call PostgreSQL update function
        $result = DB::select(
            'SELECT public.update_department(?, ?, ?, ?, ?) AS out_departmentId',
            [
                $id,
                $request->departmentName,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->ModifiedUser
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Department Updated Successfully',
            
            ]
        , 200);

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

        // ✅ Call PostgreSQL delete function
        $result = DB::select(
            'SELECT public.delete_department(?) AS out_departmentId',
            [$id]
        );

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Department Deleted Successfully',
            
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
