<?php

namespace App\Http\Controllers;

use App\Models\ProductMainGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductMainGroupController extends Controller
{
    /**
     * List ProductMainGroup
     */
    public function index()
    {
        try {

            $data = DB::select('SELECT * FROM public.get_all_product_main_group()');

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get ProductMainGroup by id
     */
    public function show($groupCode)
    {
        try {

            $data = DB::select(
                'SELECT * FROM public.get_product_main_group_byid(?)',
                [$groupCode]
            );

            if (empty($data)) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Product Main Group Not Found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $data[0]
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Create ProductMainGroup
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            /* 🔎 Duplicate groupName check (case-insensitive) */
            if (
                $request->filled('groupName') &&
                ProductMainGroup::whereRaw(
                    'LOWER("groupName") = ?',
                    [strtolower($request->groupName)]
                )->exists()
            ) {
                return response()->json([
                    'status'  => 409,
                    'error'   => true,
                    'message' => 'Group Name Already Exists'
                ], 409);
            }

            DB::select(
                'SELECT public.insert_product_main_group(?,?,?,?,?,?,?)',
                [
                    $request->groupName,
                    $request->scaleGroup,
                    $request->productCodeLength,
                    $request->nextNumber,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->CreatedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Product Main Group Inserted Successfully'
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }






    /**
     * Update ProductMainGroup
     */
    public function update(Request $request, $groupCode)
    {
        DB::beginTransaction();

        try {
            // ✅ Check if record exists
            $exists = ProductMainGroup::where('groupCode', $groupCode)->exists();
            if (!$exists) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Product Main Group not found'
                ], 404);
            }

            // 🔎 Duplicate groupName check (case-insensitive)
            if ($request->filled('groupName')) {
                $duplicate = ProductMainGroup::whereRaw(
                    'LOWER("groupName") = ?',
                    [strtolower($request->groupName)]
                )->where('groupCode', '!=', $groupCode)
                    ->exists();

                if ($duplicate) {
                    return response()->json([
                        'status'  => 409,
                        'error'   => true,
                        'message' => 'Group Name Already Exists'
                    ], 409);
                }
            }

            /* 📌 Call PostgreSQL update function */
            DB::select(
                'SELECT public.update_product_main_group(?,?,?,?,?,?,?,?)',
                [
                    $groupCode,
                    $request->groupName,
                    $request->scaleGroup,
                    $request->productCodeLength,
                    $request->nextNumber,
                    $request->branchId,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ModifiedUser
                ]
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product Main Group Updated Successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Delete ProductMainGroup
     */
    public function destroy($groupCode)
    {
        DB::beginTransaction();
        try {
            // Call the PostgreSQL delete function
            DB::statement('SELECT public.delete_product_main_group(?)', [$groupCode]);

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product Main Group deleted successfully',
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
     *  Product code generation
     */
    public function productcode($id)
    {
        $group = ProductMainGroup::find($id);

        if (!$group) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Group Code not found',
            ], 404);
        }

        $groupCode          = (string) $group->groupCode;          // e.g. "A000"
        $nextNumber         = (int) $group->nextNumber;            // e.g. 10
        $productCodeLength  = (int) $group->productCodeLength;     // e.g. 5

        // If length not defined or invalid, just concat (fallback)
        if ($productCodeLength <= 0) {
            $generated = $groupCode . $nextNumber;
        } else {
            $prefixLen   = strlen($groupCode);
            if ($prefixLen >= $productCodeLength) {
                return response()->json([
                    'status' => 422,
                    'error' => true,
                    'message' => "productCodeLength ($productCodeLength) must be greater than groupCode length ($prefixLen).",
                ], 422);
            }
            $inventorySettings = DB::select('SELECT * FROM public.get_all_inventory_settings()');
            $maingroupcode = '';
            foreach ($inventorySettings as $set) {
                $maingroupcode = $set->AddProductMainGroupCodeWithProductCode;
            }
            if ($maingroupcode == false) {
                $suffixWidth = $productCodeLength - $prefixLen;
                $padLength = max($suffixWidth, 2);
                $numericPart = str_pad((string) $nextNumber, $padLength, '0', STR_PAD_LEFT);
                $generated   = $numericPart;
            } else {
                $suffixWidth = $productCodeLength - $prefixLen;
                $digitsLen   = strlen((string) $nextNumber);

                if ($digitsLen > $suffixWidth) {
                    return response()->json([
                        'status' => 422,
                        'error' => true,
                        'message' => "nextNumber ($nextNumber) requires $digitsLen digits, but only $suffixWidth digit(s) available for suffix. Increase productCodeLength or reset nextNumber.",
                    ], 422);
                }

                // Left-pad the numeric part and build the code
                $numericPart = str_pad((string) $nextNumber, $suffixWidth, '0', STR_PAD_LEFT);
                $generated   = $groupCode . $numericPart; // exact $productCodeLength chars
            }
        }

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Product Code Generated Successfully',
            'data' => [
                'groupCode'           => $groupCode,
                'nextNumber'          => $nextNumber,
                'productCodeLength'   => $productCodeLength,
                'productCode'         => $generated,
            ],
        ]);
    }
}