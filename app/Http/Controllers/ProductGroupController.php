<?php

namespace App\Http\Controllers;

use App\Models\ProductGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductGroupController extends Controller
{
    /**
	 * List ProductGroup
	 */
	public function index()
    {
      try {
        // ✅ Call PostgreSQL function
        $productGroups = DB::select('SELECT * FROM public.get_all_product_group()');

        return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $productGroups
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
	 * Get ProductGroup by id
	 */
	public function show($id)
    {
       try {
        // ✅ Call PostgreSQL function
        $productGroup = DB::select(
            'SELECT * FROM public.get_product_group_byId(?)',
            [$id]
        );

        // ✅ Not found
        if (empty($productGroup)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'Product Group not found'
            ], 404);
          }

        return response()->json([
            'status' => 200,
            'error'  => false,
            'data'   => $productGroup[0]
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
	 * Create ProductGroup
	 */
	public function store(Request $request)
	{
		DB::beginTransaction();

       try {

      // ✅ Check duplicate groupName under SAME category
       if (
        $request->has('groupName') &&
        $request->has('category') &&
        ProductGroup::whereRaw('LOWER("groupName") = ?', [strtolower($request->groupName)])
            ->where('category', $request->categoryId)
            ->exists()
        ) {
        return response()->json([
            'status'  => 409,
            'error'   => true,
            'message' => 'Group Name already exists in this category!',
        ], 409);
        }


			$result = DB::select(
            'SELECT public.insert_product_group(?, ?, ?, ?, ?, ?, ?) AS out_groupId',
            [
                (ProductGroup::max('groupId') ?? 0) + 1,
                $request->groupName,
                $request->category,
                $request->narration,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->CreatedUser
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 201,
            'error'   => false,
            'message' => 'Product Group inserted successfully',
            
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


	/**
	 * Update ProductGroup
	 */
	public function update(Request $request, $id)
    {
       DB::beginTransaction();

      try {
		$ProductGroup = ProductGroup::find($id);

		if (!$ProductGroup) {
			return response()->json([
				'status' => 404,
				'error' => true,
				'message' => 'productgroup Not Found',
			], 404);
		}

        // ✅ Check duplicate groupName under SAME category (exclude current record)
        if (
            $request->has('groupName') &&
            $request->has('category') &&
            ProductGroup::whereRaw('LOWER("groupName") = ?', [strtolower($request->groupName)])
                ->where('category', $request->category)
                ->where('groupId', '!=', $id)
                ->exists()
        ) {
            return response()->json([
                'status'  => 409,
                'error'   => true,
                'message' => 'Group Name already exists in this category!',
            ], 409);
        }

        // ✅ Call PostgreSQL update function
        $result = DB::select(
            'SELECT public.update_product_group(?, ?, ?, ?, ?, ?, ?) AS out_groupId',
            [
                $id,
                $request->groupName,
                $request->category,
                $request->narration,
                $request->branchId,
                now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                $request->ModifiedUser
            ]
        );

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Product Group updated successfully',
            
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
	 * Delete ProductGroup
	 */
	public function destroy($id)
    {
       DB::beginTransaction();

      try {
		$ProductGroup = ProductGroup::find($id);

		if (!$ProductGroup) {
			return response()->json([
				'status' => 404,
				'error' => true,
				'message' => 'productgroup Not Found',
			], 404);
		 }
        // ✅ Call PostgreSQL delete function
        $result = DB::select(
            'SELECT public.delete_product_group(?) AS out_groupId',
            [$id]
        );

        DB::commit();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Product Group deleted successfully',
            
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
	 * Get subgroup by category
	 */
	public function subgroup($category)
    {
       try {
        // ✅ Call PostgreSQL function
        $productGroups = DB::select(
            'SELECT * FROM public.get_product_group_by_category(?)',
            [$category]
        );

        if (empty($productGroups)) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => 'No product groups found for this category',
            ], 404);
         }

         return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Sub Groups retrieved successfully',
            'data'    => $productGroups,
        ]);

        } catch (\Throwable $e) {

        return response()->json([
            'status'  => 500,
            'error'   => true,
            'message' => $e->getMessage(),
        ], 500);
        }
    }
}
