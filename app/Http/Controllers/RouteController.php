<?php

namespace App\Http\Controllers;

use App\Models\Route as RouteModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        try {
            $items = DB::select(
                'SELECT * FROM public.get_all_routes() ORDER BY "RouteName" ASC'
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Routes',
                'data'    => $items,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $item = DB::select(
                'SELECT * FROM public.get_route_by_id(?)',
                [$id]
            );

            if (empty($item)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Route not found',
                    'data'    => [],
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Route details',
                'data'    => $item[0], // single record
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {


            // 🔢 Generate next RouteId
            $RouteId = (DB::table('tbl_Route')->max('RouteId') ?? 0) + 1;

            // 📌 Call PostgreSQL function
            $result = DB::selectOne(
                'SELECT public.insert_route(?, ?, ?, ?, ?,?,?) AS message',
                [
                    $RouteId,
                    $request->RouteName,
                    $request->Narration,
                    $request->branchId,
                    $request->MarketId ?? null,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser,
                ]
            );

            // ❌ Duplicate or DB-level validation
            if ($result->message !== 'Route inserted successfully') {
                DB::rollBack();
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => $result->message,
                ], 409);
            }

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Route created successfully',
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
                'SELECT public.update_route(?, ?, ?, ?, ?, ?, ?) AS message',
                [
                    $id,
                    $request->RouteName,
                    $request->Narration,
                    $request->branchId,
                    $request->MarketId ?? null,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser,
                ]
            );

            // ❌ Route not found
            if ($result->message === 'Route not found') {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => $result->message,
                ], 404);
            }

            // ❌ Duplicate or other validation error
            if ($result->message !== 'Route updated successfully') {
                DB::rollBack();
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => $result->message,
                ], 409);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Route updated successfully',
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
            // 📌 Call PostgreSQL delete function
            $result = DB::selectOne(
                'SELECT public.delete_route(?) AS message',
                [$id]
            );

            if ($result->message === 'Route not found') {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => $result->message,
                ], 404);
            }

            if ($result->message !== 'Route deleted successfully') {
                DB::rollBack();
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => $result->message,
                ], 409);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Route deleted successfully',
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
