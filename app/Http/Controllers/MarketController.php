<?php

namespace App\Http\Controllers;

use App\Models\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    /**
     * Display a listing of markets
     */
    public function index()
    {
        try {

            $markets = DB::select('SELECT * FROM public.get_all_market() ORDER BY "MarketName" ASC');

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Markets retrieved successfully',
                'data'    => $markets,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified market
     */
    public function show($id)
    {

        try {

            $market = DB::select(
                'SELECT * FROM public.get_market_byId(?)',
                [$id]
            );
            $market = Market::find($id);

            if (!$market) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Market not found',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Market retrieved successfully',
                'data' => $market,
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
     * Store a newly created market
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            // Call PostgreSQL insert function
            $result = DB::select(
                'SELECT public.insert_market(?, ?, ?, ?, ?, ?,?) AS result',
                [
                    $request->MarketId ?? (Market::max('MarketId') ?? 0) + 1,
                    $request->MarketName,
                    $request->Narration,
                    $request->branchId,
                    $request->AreaId ?? null,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser
                ]
            );

            // Function response text
            $message = $result[0]->result ?? 'Failed';

            // Duplicate check handled inside function
            if ($message === 'Market already exists') {
                DB::rollBack();
                return response()->json([
                    'status' => 422,
                    'error'  => true,
                    'message' => $message
                ], 422);
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error'  => false,
                'message' => $message
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified market
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $result = DB::select(
                'SELECT public.update_market(?, ?, ?, ?, ?, ?,?) AS result',
                [
                    $id,
                    $request->MarketName,
                    $request->Narration,
                    $request->branchId,
                    $request->AreaId ?? null,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser
                ]
            );

            $message = $result[0]->result ?? 'Failed';

            if ($message === 'Market not found') {
                DB::rollBack();
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => $message
                ], 404);
            }

            if ($message === 'Market name already exists') {
                DB::rollBack();
                return response()->json([
                    'status' => 422,
                    'error'  => true,
                    'message' => $message
                ], 422);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => $message
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified market
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Call PostgreSQL delete function
            $result = DB::select(
                'SELECT public.delete_market(?) AS result',
                [$id]
            );

            $message = $result[0]->result ?? 'Failed';

            if ($message === 'Market not found') {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => $message
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => $message
            ], 200);
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
