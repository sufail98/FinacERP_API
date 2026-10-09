<?php

namespace App\Http\Controllers;

use App\Models\SuffixPrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuffixPrefixController extends Controller
{

    /**
     * Display the specified suffix-prefix
     */
    public function show(Request $request)
    {
        try{
            $voucherType = $request->input('voucherType');
            $yearId      = $request->input('yearId');
            $branchId     = $request->input('branchId');

        // Validate required params
        if (empty($voucherType) || empty($yearId) || empty($branchId)) {
            return response()->json([
                'status'  => 400,
                'error'   => true,
                'message' => 'voucherType,branchId and yearId are required',
            ], 400);
        }

        // Query with BOTH conditions (must match together)
        $result = DB::select(
            'SELECT * FROM public.suffix_prefix_by_vouchertype_yearid(?,?,?)',
            [
                $voucherType,
                $yearId,
                $branchId,
            ]
        );

        if (empty($result)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Suffix Prefix Not Found',
            ], 404);
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Suffix Prefix Retrieved Successfully',
            // if you want only one record (same as ->first())
            'data'    => $result[0]
            // if you want all matches, use:
            // 'data' => $result
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
     * Store suffix-prefix
     */
    public function store(Request $request)
    {
       DB::beginTransaction();

       try {
        $result = DB::select(
            'SELECT public.insert_suffix_prefix(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS suffixPrefixId',
            [
                $request->voucherType,
                $request->yearId,
                $request->suffix,
                $request->prefix,
                $request->startIndex,
                $request->addWithZero,
                $request->noOfZero,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->CreatedUser
            ]
        );

        DB::commit();

        return response()->json([
            'status' => 201,
            'error' => false,
            'message' => 'Suffix Prefix Created Successfully',
            
        ], 201);

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
     * Update the specified SuffixPrefix
     */
    public function update(Request $request, $id)
    {
       DB::beginTransaction();

       try {
         $result = DB::select(
            'SELECT public.update_suffix_prefix(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS status',
            [
                $id,
                $request->voucherType,
                $request->yearId,
                $request->suffix,
                $request->prefix,
                $request->startIndex,
                $request->addWithZero,
                $request->noOfZero,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->ModifiedUser
            ]
         );

        // If record not found in DB function
        if ($result[0]->status === 'NOT_FOUND') {
            DB::rollBack();
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message'=> 'Suffix Prefix Not Found',
            ], 404);
        }

        DB::commit();

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message'=> 'Suffix Prefix Updated Successfully'
        ], 200);

       } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage(),
        ], 500);
       }
    }


    /**
     * Remove the specified SuffixPrefix
     */
    public function destroy($id)
   {
       DB::beginTransaction();

       try {
        $result = DB::select(
            'SELECT public.delete_suffix_prefix(?) AS status',
            [$id]
        );

        if ($result[0]->status === 'NOT_FOUND') {
            DB::rollBack();
            return response()->json([
                'status' => 404,
                'error'  => true,
                'message'=> 'Suffix Prefix Not Found',
            ], 404);
        }

        DB::commit();

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message'=> 'Suffix Prefix Deleted Successfully',
        ], 200);

       } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json([
            'status' => 500,
            'error'  => true,
            'message'=> $e->getMessage(),
        ], 500);
      }
    }

}
