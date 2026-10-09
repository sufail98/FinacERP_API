<?php

namespace App\Http\Controllers;

use App\Models\CurrencyConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrencyConversionController extends Controller
{
    /**
     * List currencies
     */
    public function index($branchId = null)
    {
        try {

            $currencyConversions = DB::select(
                'SELECT * FROM public.get_all_currency_conversion(?)',
                [$branchId]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $currencyConversions,
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Get currency by id
     */
    public function show($id)
    {
        DB::beginTransaction();
        try {

            $data = DB::select(
                'SELECT * FROM public.get_currency_conversion_byid(?)',
                [$id]
            );

            DB::commit();

            if (empty($data)) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Currency Conversion not found'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Currency Conversion fetched successfully',
                'data'    => $data[0]   // single record
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


    /**
     * Create currency
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $currencyConversionId =
                $request->currencyConversionId
                ?? (CurrencyConversion::max('currencyConversionId') ?? 0) + 1;

            $result = DB::select(
                'SELECT public.insert_currency_conversion(?, ?, ?, ?, ?, ?, ?) AS result',
                [
                    $request->currencyId,
                    $request->date,
                    $request->rate,
                    $request->narration,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Currency Conversion inserted successfully',

            ]);
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
     * Update currency
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            // Call UPDATE function
            $result = DB::select(
                'SELECT public.update_currency_conversion(?, ?, ?, ?, ?, ?, ?, ?) AS result',
                [
                    $id,                                   // currencyConversionId
                    $request->currencyId,
                    $request->date,
                    $request->rate,
                    $request->narration,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser
                ]
            );

            // If no row affected, treat as not found
            if (empty($result)) {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Currency Conversion Not Found'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Currency Conversion Updated Successfully',

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

    /**
     * Delete currency
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {

            $result = DB::select(
                'SELECT public.delete_currency_conversion(?) AS result',
                [$id]
            );

            if ($result[0]->result == 0) {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Currency Conversion Not Found'
                ], 404);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Currency Conversion Deleted Successfully'
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
