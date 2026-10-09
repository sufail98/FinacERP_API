<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Batch;
use Illuminate\Support\Facades\DB;



class BatchController extends Controller
{
    
    public function index()
    {
        try {

            $result = DB::select(
                'SELECT * FROM public.get_all_batches()'
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $result
            ], 200);

            } catch (\Throwable $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function show($batchId)
    {
        try {

           $result = DB::select(
            'SELECT * FROM public.get_batch_byid(?)',
            [$batchId]
           );

            if (empty($result)) {
             return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Batch not found'
            ], 404);
            }

         return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $result[0]
        ], 200);

        } catch (\Throwable $e) {

        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
       }
    }



    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // Insert batch
            $result = DB::select(
                'SELECT public.insert_batch(?,?,?,?,?,?) AS out_batchId',
                [
                    (Batch::max('batchId') ?? 0) + 1,
                    $request->batchName,
                    $request->mfd,
                    $request->branchId,
                    $request->CreatedUser,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Batch Inserted Successfully'
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



    public function update(Request $request, $batchId)
    {
        try {
            $result = DB::select(
                'SELECT public.update_batch(?,?,?,?,?,?)',
                [
                    $batchId,                                  // batchId
                    $request->batchName,                       // batchName
                    $request->mfd,                             // mfd
                    $request->branchId,                        // branchId
                    $request->ModifiedUser,                    // ModifiedUser
                    now()->setTimezone('Asia/Kolkata')         // ModifiedDate
                ]
            );

            if (empty($result) || $result[0]->update_batch === null) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Batch not found'
                ], 404);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Batch updated successfully',
                'batchId' => $result[0]->update_batch
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function destroy($batchId)
    {
        try {

        $result = DB::selectOne(
            'SELECT public.delete_batch(?) AS deleted_batchId',
            [$batchId]
        );

        if (!$result || !$result->deleted_batchid) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Batch not found'
            ], 404);
            }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Batch deleted successfully'
        ], 200);

        } catch (\Throwable $e) {
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
         }
    }

}    


    
    