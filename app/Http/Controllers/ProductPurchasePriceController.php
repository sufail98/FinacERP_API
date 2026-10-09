<?php

namespace App\Http\Controllers;

use App\Models\ProductPurchasePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductPurchasePriceController extends Controller
{
    public function index()
    {
        $products = ProductPurchasePrice::all();
        return response()->json([
            'status' => 200,
            'error' => false,
            'data' => $products
        ], 200);
    }
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            ProductPurchasePrice::create([
                'ledgerId'         => $request->ledgerId,
                'productCode'      => $request->productCode,
                'unitId'           => $request->unitId,
                'amount'           => $request->amount,
                'discPercentage'   => $request->discPercentage,
                'discAmount'       => $request->discAmount,
                'purchasePrice'    => $request->purchasePrice,
                'userId'           => $request->userId,
                'branchId'         => $request->branchId,
                'extraDate'        => $request->extraDate,
                'extra1'           => $request->extra1,
                'extra2'           => $request->extra2,
                'purchaseDate'     => $request->purchaseDate,
                'purchaseMasterId' => $request->purchaseMasterId,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product Purchase Stored successfully',
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
    public function show(string $id)
    {
        try {

            $products = ProductPurchasePrice::find($id);

            if (!$products) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Product purchase Price not found',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $products
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy(string $id)
    {
        try {
            $products = ProductPurchasePrice::find($id);
            if (!$products) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Product purchase Price not found',

                ]);
            }
            $products->delete();
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product purchase Price deleted successfully',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $purchasePrice = ProductPurchasePrice::findOrFail($id);


            $purchasePrice->update([
                'ledgerId'         => $request->ledgerId,
                'productCode'      => $request->productCode,
                'unitId'           => $request->unitId,
                'amount'           => $request->amount,
                'discPercentage'   => $request->discPercentage,
                'discAmount'       => $request->discAmount,
                'purchasePrice'    => $request->purchasePrice,
                'userId'           => $request->userId,
                'branchId'         => $request->branchId,
                'extraDate'        => $request->extraDate,
                'extra1'           => $request->extra1,
                'extra2'           => $request->extra2,
                'purchaseDate'     => $request->purchaseDate,
                'purchaseMasterId' => $request->purchaseMasterId,
            ]);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product Purchase updated successfully',
                'data'    => $purchasePrice
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
}
