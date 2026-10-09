<?php

namespace App\Http\Controllers;
use App\Helpers\ActivityHelper;

use Illuminate\Http\Request;
use App\Models\BarcodePrintAlignment;
use Illuminate\Support\Facades\DB;


class BarcodePrintAlignmentController extends Controller
{


    public function index($branchId = null)
    {
        try {

            $result = DB::select(
                'SELECT * FROM public.get_all_barcode_print_alignment(?)',
                [$branchId]
            );

            return response()->json([
                'status' => true,
                'data' => $result
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {

         DB::beginTransaction();
		try {
            $barcode = BarcodePrintAlignment::find($id);

            if (!$barcode) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'barcodeid not found',
                ], 404);
            }

            $result = DB::select(
                'SELECT * FROM public.update_barcode_print_alignment(?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?)',
                [
                    $id,
                    $request->PaddingLeft,
                    $request->PaddingRight,
                    $request->PaddingTop,
                    $request->PaddingBottom,
                    $request->branchId,
                    now(),
                    $request->ModifiedUser,
                    $request->height,
                    $request->width,
                    $request->branchName,
                    $request->showBranchName,
                    $request->showPrice,
                    $request->showProductName,
                    $request->showSupplierCode,
                    $request->showBarCode
                ]
            );
            ActivityHelper::logActivity(
                    auth()->id() ?? 0,                    // UserId
                    'Sales Settings',                     // ActivityType
                    'Sales Settings',                     // VoucherType
                    'UPDATED',                            // Action
                    $request->branchId,                   // BranchId
                    $request->ModifiedUser ?? 'System',   // CreatedUser
                    $id,                                  // MasterId
                    null,                                 // VoucherNo
                    now(),                                // VoucherDate
                    null,                                 // OldAmount
                    null,                                 // NewAmount
                    $request->systemId                       // SystemId
                );

            DB::commit();

                return response()->json([
                    'status' => 200,
                    'error' => false,
                    'message' => 'barcode updated successfully'
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
}
