<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalculationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function StockValueOnDateByFIFO(Request $request)
    {
        try {
            $date        = $request->date;
            $branchId    = $request->branch_id;
            $currencyId  = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."stock_value_on_date_by_fifo"(?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId,

                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'StockValue OnDate By FIFO data not found'
                    : 'StockValue OnDate By FIFO data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function openingStockValueAVCO(Request $request)
    {
        try {
            $date        = $request->date;
            $fromDate    = $request->from_date;
            $branchId    = $request->filled('branch_id') ? (int) $request->branch_id : null;
            $currencyId  = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."stock_value_on_date_by_avco_for_opening_stock"(?, ?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'Opening Stock Value AVCO data not found'
                    : 'Opening Stock Value AVCO data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch Opening Stock Value AVCO data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function openingStockAVCOBalanceSheet(Request $request)
    {
        try {
            $date        = $request->date;
            $fromDate    = $request->from_date;
            $branchId    = $request->filled('branch_id') ? (int) $request->branch_id : null;
            $currencyId  = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."stock_value_on_date_by_avco_for_opening_stock_for_balance_sheet"(?, ?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'Opening Stock Value AVCO Balance Sheet data not found'
                    : 'Opening Stock Value AVCO Balance Sheet data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function openingStockAVCO(Request $request)
    {
        try {
            $date        = $request->date;
            $branchId    = $request->branch_id;
            $currencyId  = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."stock_value_on_date_by_avco"(?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'Opening Stock Value (AVCO)  data not found'
                    : 'Opening Stock Value (AVCO)  data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function openingStockHighCost(Request $request)
    {
        try {
            $date        = $request->date;
            $fromDate    = $request->from_date;
            $branchId    = $request->branch_id;
            $currencyId  = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."stock_value_on_date_by_high_cost_for_opening_stock"(?, ?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'Opening Stock High Cost data not found'
                    : 'Opening Stock High Cost value fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function openingStockHighCostBalanceSheet(Request $request)
    {
        try {
            $date        = $request->date;
            $fromDate    = $request->from_date;
            $branchId    = $request->branch_id;
            $currencyId  = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public."stock_value_on_date_by_high_cost_for_opening_stock_blnc_sheet"(?, ?, ?, ?)',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => empty($data)
                    ? 'Opening Stock High Cost (Balance Sheet)  data not found'
                    : 'Opening Stock High Cost (Balance Sheet) data fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function StockValueOnDateByHighCost(Request $request)
    {
        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public.stock_value_on_date_by_high_cost(
                ?::timestamp,
                ?::bigint,
                ?::bigint
            )',
                [
                    $date,
                    $branchId,
                    $currencyId
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Opening Stock High Cost value fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch Opening Stock High Cost value',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function openingStockLowCost(Request $request)
    {
        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;
            $fromDate    = $request->from_date;

            $data = DB::select(
                'SELECT * FROM public.stock_value_on_date_by_low_cost_for_opening_stock(
                ?,
                ?,
                ?,
                ?
            )',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Opening Stock Value (Low Cost) fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  value',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function LowCostForOpeningStockForBlncSheet(Request $request)
    {
        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;
            $fromDate    = $request->from_date;

            $data = DB::select(
                'SELECT * FROM public.stock_value_on_date_by_low_cost_opening_stock_bl_sheet(
                ?,
                ?,
                ?,
                ?
            )',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Opening Stock Value (Low Cost) Balance Sheet fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  value',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function StockValueOnDateByLowCost(Request $request)
    {
        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;

            $data = DB::select(
                'SELECT * FROM public.stock_value_on_date_by_low_cost(
                ?,
                ?,
                ?
            )',
                [
                    $date,
                    $branchId,
                    $currencyId
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Stock Value On Date By LowCost fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  value',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function openingStockLastPurchaseRate(Request $request)
    {
        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;
            $fromDate    = $request->from_date;

            $data = DB::select(
                'SELECT * FROM public.stock_value_on_date_by_last_purchase_rate_for_opening_stock(
                ?,
                ?,
                ?,
                ?
            )',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Opening Stock Last PurchaseRate fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  value',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function openingStockLastPurchaseRateBalanceSheet(Request $request)
    {
        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;
            $fromDate    = $request->from_date;

            $data = DB::select(
                'SELECT * FROM public.stock_value_on_date_by_last_purchase_rate_for_opening_stock_bln(
                ?,
                ?,
                ?,
                ?
            )',
                [
                    $date,
                    $branchId,
                    $currencyId,
                    $fromDate
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Opening Stock Last Purchase Rate BalanceSheet fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  value',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function stockValueByLastPurchaseRate(Request $request)
    {
        try {
            $date       = $request->date;
            $branchId   = $request->branch_id;
            $currencyId = $request->currency_id;


            $data = DB::select(
                'SELECT * FROM public.stock_value_on_date_by_last_purchase_rate(
                ?,
                ?,
                ?
            )',
                [
                    $date,
                    $branchId,
                    $currencyId
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Stock Value By Last PurchaseRate fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch  value',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
