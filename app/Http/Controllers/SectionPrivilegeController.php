<?php

namespace App\Http\Controllers;

use App\Models\SectionPrivilege;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionPrivilegeController extends Controller
{
    public function index($id)
    {
        try {
            // 🔹 Call the PostgreSQL function
            $branchId = ($id === 'null' || $id === null) ? null : (int) $id;
            $settings = DB::select('SELECT * FROM public.get_all_section_privileges(?)', [$branchId]);

            // 🔹 Return JSON response
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Section Privileges retrieved successfully',
                'data'    => $settings
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

    public function show($userGroupId,$branchId)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public.get_section_privileges_byid(?, ?)',
                [$userGroupId,$branchId]
            );

            if (!$data) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message'=> 'Section Privilege not found'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Section Privilege details',
                'data'    => $data
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message'=> $e->getMessage()
            ], 500);
        }
    }

   
    public function savePrivileges(Request $request)
    {
        DB::beginTransaction();

        try {
            $usergroupId = $request->userGroupId;
            $userId      = $request->userid;
            $privileges  = $request->privileges;

            if (!is_array($privileges)) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message'=> 'Privileges must be an array'
                ], 400);
            }

            foreach ($privileges as $privilege) {

                DB::statement(
                    'SELECT public.save_section_privileges(?, ?, ?, ?, ?) AS privilege_id',
                    [
                        $usergroupId,
                        $privilege['SectionName'],
                        $privilege['status'] ?? false,
                        $privilege['branchId'],
                        $userId
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message'=> 'Privileges saved successfully'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message'=> $e->getMessage()
            ], 500);
        }
    }
}
