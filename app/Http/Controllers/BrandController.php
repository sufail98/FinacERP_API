<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    public function index()
    {
      try {
        $brands = DB::select('SELECT * FROM public.get_all_brand()');

        return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $brands
        ], 200);

      } catch (\Throwable $e) {
        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage()
        ], 500);
       }
    }

    public function show($brandId)
    {
        $result = DB::select(
        'SELECT * FROM public.get_brand_byid(?)',
        [$brandId]
         );

       if (empty($result)) {
        return response()->json([
            'status'  => 404,
            'error'   => true,
            'message' => 'Brand not found'
        ], 404);
        }

      return response()->json([
        'status' => 200,
        'error'  => false,
        'data'   => $result[0]
       ], 200);
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $existingBrand = Brand::whereRaw('LOWER("brandName") = ?', [strtolower($request->brandName)])->first();
            if ($existingBrand) {
                return response()->json([
                    'status' => 422,
                    'error' => true,
                    'message' => 'Brand Name Already Exists !',
                ], 422);
            }

            $result = DB::select(
                'SELECT public.insert_brand(?,?,?,?,?,?) AS out_brandId',
                [
                    (Brand::max('brandId') ?? 0) + 1,
                    $request->brandName,
                    $request->Narration,
                    $request->CreatedUser,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->branchId
                ]
            );

            DB::commit();

            return response()->json([
                'status'   => 200,
                'error'    => false,
                'brandId'  => $result[0]->out_brandid,
                'message'  => 'Brand inserted successfully'
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

    public function update(Request $request, $brandId)
    {
        DB::beginTransaction();

        try {

        $brand = Brand::find($brandId);
            
        if (!$brand) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Brand not found',
            ], 404);
        }

        // Check if new brand name already exists (excluding current brand)
        if ($request->has('brandName') && $request->brandName !== $brand->brandName) {
            $existingBrand = Brand::whereRaw('LOWER("brandName") = ?', [strtolower($request->brandName)])
                ->where('brandId', '!=', $brandId)
                ->first();
                
            if ($existingBrand) {
                return response()->json([
                    'status' => 422,
                    'error' => true,
                    'message' => 'Brand Name Already Exists !',
                ], 422);
            }
        }

        $results = DB::select(
            'SELECT public.update_brand(?,?,?,?,?,?) AS out_brandid',
            [
                $brandId,
                $request->brandName,
                $request->Narration,
                $request->ModifiedUser,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->branchId
            ]
        );

        // DB::select returns array
        if (empty($results) || $results[0]->out_brandid === null) {
            DB::rollBack();
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Brand not found or update failed'
            ], 404);
        }

        DB::commit();

        return response()->json([
            'status'   => 200,
            'error'    => false,
            
            'message'  => 'Brand updated successfully'
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


    public function destroy($brandId)
    {
       DB::beginTransaction();

       try {
        $results = DB::select(
            'SELECT public.delete_brand(?) AS out_brandid',
            [(int) $brandId]
        );

        if (empty($results) || $results[0]->out_brandid === null) {
            DB::rollBack();
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Brand not found or cannot be deleted (maybe in use)'
            ], 404);
        }

        DB::commit();

        return response()->json([
            'status'   => 200,
            'error'    => false,
            'brandId'  => $results[0]->out_brandid,
            'message'  => 'Brand deleted successfully'
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
