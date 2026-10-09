<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $salesOrderPayment = DB::select('SELECT * FROM public.get_all_sales_order_payments()');

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $salesOrderPayment
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT insert_sales_order_payment(?,?,?,?,?,?,?,?,?,?) AS id',
                [
                    $request->SalesOrderMasterId,
                    $request->ledgerId,
                    $request->amount,
                    $request->Date,
                    $request->Remarks,
                    $request->extraDate,
                    $request->branchId,
                    $request->userId,
                    $request->createdUser,
                    $request->createdDate
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Sales Order Payment Inserted Successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $salesOrderMasterId)
    {
        try {
            $data = DB::select(
                'SELECT * FROM get_sales_order_payment_by_id(?)',
                [(int) $salesOrderMasterId]
            );

            if (empty($data)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Record not found'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Sales Order Payment By SalesOrderMasterId Fetched Successfully'
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $salesOrderMasterId)
    {
        try {
            DB::statement(
                '
            SELECT update_sales_order_payment(
                ?::INT,
                ?::INT,
                ?::NUMERIC,
                ?::TIMESTAMP,
                ?::TEXT,
                ?::TIMESTAMP,
                ?::INT,
                ?::INT,
                ?::VARCHAR,
                ?::TIMESTAMP
            )
            ',
                [
                    (int) $salesOrderMasterId,
                    $request->ledgerId,
                    $request->amount,
                    $request->Date,
                    $request->Remarks,
                    $request->extraDate ?? now()->toDateTimeString(),
                    $request->branchId,
                    $request->userId,
                    $request->modifiedUser ?? 'admin',
                    $request->modifiedDate ?? now()->toDateTimeString()
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Sales Order Payment Updated Successfully'
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
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $exists = DB::statement(
            'SELECT delete_sales_order_payment(?)',
            [$id]
        );
        if (!$exists) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Order Payment not found'
            ], 404);
        }

        return response()->json(['message' => 'Payment deleted successfully'], 200);
    }

    public function SalesOrderPaymentReport(Request $request)
    {
        try {
            $result = DB::select(
                'SELECT * FROM "sales_order_payment_report_fill"(?,?,?,?,?,?,?,?,?,?)',
                [
                    $request->fromDate,        // TIMESTAMP
                    $request->toDate,          // TIMESTAMP
                    (int) $request->branchId,  // INTEGER
                    (int) $request->currencyId, // INTEGER

                    (int) ($request->ledgerId   ?? 0),
                    (int) ($request->salesManId ?? 0),
                    (int) ($request->cashOrBank ?? 0),

                    $request->status    ?? 'All',
                    $request->payStatus ?? 'All',
                    $request->partyName ?? 'All'
                ]
            );

            return response()->json([
                'success' => true,
                'data'    => $result
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sales order payment report',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}