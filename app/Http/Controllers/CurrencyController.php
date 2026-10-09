<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrencyController extends Controller
{
	/**
	 * List currencies
	 */
	public function index()
	{
		try{
			$currencies = DB::select(
            'SELECT * FROM public.get_all_currency()'
        );

          return response()->json([
			'status' => 200,
			'error' => false,
			'message' => 'Currencies retrieved successfully',
			'data' => $currencies,
		]);
		} catch (\Throwable $e) {
        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage()
        ], 500);
       }
	}

	/**
	 * Get currency by id
	 */
	public function show($id)
    {
        try {

        $currency = DB::select(
            'SELECT * FROM public.get_currency_byId(?)',
            [$id]
        );

        if (empty($currency)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Currency not found'
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Currency retrieved successfully',
            'data'    => $currency[0]
        ], 200);

       } catch (\Throwable $e) {
        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage()
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

        $currencyId = $request->currencyId ?? (Currency::max('currencyId') ?? 0) + 1;

        $result = DB::select(
            'SELECT public.insert_currency(?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS result',
            [
                $currencyId,
                $request->currencySymbol,
                $request->currencyName,
                $request->subunitName,
                $request->noOfDecimalPlace,
                $request->narration,
                false, // default currency
                $request->branchId,
				now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->CreatedUser
            ]
        );

        

        DB::commit();

			return response()->json([
				'status' => 200,
				'error' => false,
				'message' => 'Currency inserted successfully'
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

	/**
	 * Update currency
	 */
	public function update(Request $request, $id)
    {
       DB::beginTransaction();
       try {

         $result = DB::select(
            'SELECT public.update_currency(?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS result',
            [
                $id,
                $request->currencySymbol,
                $request->currencyName,
                $request->subunitName,
                $request->noOfDecimalPlace,
                $request->narration,
                $request->default ?? false,
                $request->branchId,
				now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->ModifiedUser
            ]
          );

          $message = $result[0]->result ?? 'Failed';

          if ($message === 'Currency not found') {
            DB::rollBack();
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message'=> $message
            ], 404);
           }

          if ($message === 'Currency name already exists') {
            DB::rollBack();
            return response()->json([
                'status' => 409,
                'error'  => true,
                'message'=> $message
            ], 409);
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
            'message'=> $e->getMessage()
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
            'SELECT public.delete_currency(?) AS result',
            [$id]
        );
			DB::commit();

			return response()->json([
				'status' => 200,
				'error' => false,
				'message' => 'Currency deleted successfully',
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

	/**
	 * List get_currency_with_conversion
	 */
    public function currency_with_conversion()
    {
        $currency = DB::select('SELECT * FROM get_currency_with_conversion()');

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'currency with conversion retrieved successfully',
            'data' => $currency,
        ]);
    }
}
