<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\UserPrivilege;

class UserPrivilegeController extends Controller
{
    public function index()
    {
        try {
            $data = DB::select('SELECT * FROM public.get_all_user_privileges()');

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'User Privileges list',
                'data'    => $data
            ]);
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
            $data = DB::select(
                'SELECT * FROM public.get_user_privilege_byId(?)',
                [$id]
            );

            if (!$data) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'User Privilege not found'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'User Privilege details',
                'data'    => $data
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function savePrivileges(Request $request)
    {
        DB::beginTransaction();

        try {
            $usergroupId = $request->usergroup_id;
            $userId      = $request->user_id;
            $privileges  = $request->privileges;

            if (!is_array($privileges)) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Privileges must be an array'
                ], 400);
            }

            foreach ($privileges as $privilege) {

                DB::selectOne(
                    'SELECT public.save_user_privileges(?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?) AS privilege_id',
                    [
                        $usergroupId,
                        $privilege['window_name'],
                        $privilege['can_all'] ?? false,
                        $privilege['can_add'] ?? false,
                        $privilege['can_edit'] ?? false,
                        $privilege['can_delete'] ?? false,
                        $privilege['can_post'] ?? false,
                        $privilege['can_view'] ?? false,
                        $privilege['can_home'] ?? false,
                        $privilege['planVisible'] ?? true,
                        $userId
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Privileges saved successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}