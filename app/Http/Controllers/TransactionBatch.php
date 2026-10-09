<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionBatch extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($branchId)
    {
        try {
            $transactions = DB::select(
                'SELECT * FROM fn_get_transaction_batch_by_branch(?)',
                [$branchId]
            );

            // 👇 Nothing found for this branchId
            if (empty($transactions)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'No Transaction Batch found for this branch'
                ]);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Transaction Batch fetched successfully',
                'data' => $transactions
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function showByVoucherType($voucherType, $branchId)
    {
        try {
            $transactions = DB::select(
                'SELECT * FROM fn_get_transaction_batch_by_vouchertype(?,?)',
                [$voucherType, $branchId]
            );

            if (empty($transactions)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'No Transaction Batch found for this voucher type'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Transaction Batch fetched successfully',
                'data' => $transactions
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $result = DB::select(
                'SELECT fn_insert_transaction_batch(?, ?, ?, ?, ?) AS transactionBatchId',
                [
                    $request->batchId,
                    $request->voucherType,
                    $request->batchName,
                    $request->branchId,
                    $request->createdUser
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Transaction batch inserted successfully',
                'data' => [
                    'transactionBatchId' => $result[0]->transactionbatchid
                ]
            ]);
        } catch (QueryException $e) {

            $message = 'Database error';

            if ($e->getPrevious()) {
                $pgMessage = $e->getPrevious()->getMessage();

                if (preg_match('/ERROR:\s+(.*?)(\n|CONTEXT:|$)/s', $pgMessage, $matches)) {
                    $message = trim($matches[1]);
                } else {
                    $message = $pgMessage;
                }
            }

            return response()->json([
                'status' => 422,
                'error' => true,
                'message' => $message
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $data = DB::select(
                'SELECT * FROM fn_get_transaction_batch_by_id(?)',
                [$id]
            );

            if (empty($data)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Transaction Batch not found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Transaction Batch fetched successfully',
                'data' => $data[0]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
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
    public function update(Request $request, $id)
    {

        try {
            $result = DB::select(
                'SELECT fn_update_transaction_batch(?, ?, ?, ?, ?, ?) AS updated',
                [
                    $id,
                    $request->batchId,
                    $request->voucherType,
                    $request->batchName,
                    $request->branchId,
                    $request->modifiedUser
                ]
            );

            if (!$result[0]->updated) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Transaction Batch not found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Transaction Batch updated successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $result = DB::select(
                'SELECT fn_delete_transaction_batch(?) AS deleted',
                [$id]
            );

            if (!$result[0]->deleted) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Transaction Batch not found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Transaction Batch deleted successfully'
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
