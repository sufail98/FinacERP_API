<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class UserGroupController extends Controller
{
    /**
     * Display a listing of user groups
     */
    public function index()
    {
        try {

            $data = DB::select('SELECT * FROM public.get_all_user_groups()');

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display active user groups only
     */
    public function active()
    {
        try {

            $userGroups = DB::select(
                'SELECT * FROM public.get_active_user_groups()'
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Active User Groups',
                'data'    => $userGroups
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified user group
     */
    public function show($id)
    {
        try {

            $data = DB::select(
                'SELECT * FROM public.get_user_group_byid(?)',
                [$id]
            );

            if (empty($data)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'User Group Not Found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data[0] // single record
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created user group
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $result = DB::selectOne(
                'SELECT public.insert_user_group(?, ?, ?, ?)',
                [
                    $request->usergroupName,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser,
                    $request->activeStatus
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'User Group created successfully',
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {

            DB::rollBack();

            /* PostgreSQL unique violation error code */
            if ($e->getCode() == '23505') {
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => 'User Group already exists'
                ], 409);
            }

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update the specified user group
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            // Optional: check existence (good practice)
            $exists = DB::table('tbl_UserGroup')
                ->where('usergroupId', $id)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'User Group not found'
                ], 404);
            }

            // Call PostgreSQL update function
            $result = DB::select(
                'SELECT public.update_user_group(?, ?, ?, ?, ?) AS "usergroupId"',
                [
                    $id,
                    $request->usergroupName,
                    $request->activeStatus,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'User Group updated successfully',
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {

            DB::rollBack();

            // PostgreSQL unique violation
            if ($e->getCode() == '23505') {
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => 'User Group already exists'
                ], 409);
            }

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified user group
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $result = DB::selectOne(
                'SELECT public.delete_user_group(?) AS "usergroupId"',
                [$id]
            );

            // Check if row was actually deleted
            if (!$result || $result->usergroupId == 0) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'User Group not found'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'User Group deleted successfully',

            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Toggle activeStatus between active (1) and inactive (0)
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $newStatus = DB::selectone(
                'SELECT public.toggle_usergroup_status(?) AS "activeStatus"',
                [$id]
            );

            if (!$newStatus) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'User Group not found'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'User Group status toggled successfully',
            ]);
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
