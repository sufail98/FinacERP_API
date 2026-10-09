<?php

namespace App\Http\Controllers;
use App\Helpers\ActivityHelper;

use App\Models\PurchaseSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseSettingsController extends Controller
{
    /**
     * Display purchase settings
     */
    public function index()
    {
        try {
            $result = DB::select(
                'SELECT * FROM public.get_all_purchase_settings()'
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Purchase settings retrieved successfully',
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update the specified purchase setting
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $setting = PurchaseSettings::find($id);

            if (!$setting) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Purchase setting not found',
                ], 404);
            }

            foreach (
                [
                    'VendorInvoiceNoChecking',
                    'showProductDescription',
                    'showFeeQtyColumn',
                    'ActivatePurchaseRetention',
                    'ChangeSalesPrice',
                    'showLineDiscount',
                    'CloseAfterSave',
                    'printAfterSave',
                    'ActiveGodown',
                    'gridFixedHeight',
                ] as $field
            ) {
                if ($request->has($field)) {
                    $setting->{$field} = $request->boolean($field);
                }
            }

            if ($request->has('branchId')) {
                $setting->branchId = $request->branchId;
            }

            if ($request->has('retentionLedgerId')) {
                $setting->retentionLedgerId = $request->retentionLedgerId;
            }

            $setting->modifiedDate = now()
                ->setTimezone('Asia/Kolkata')
                ->format('Y-m-d H:i:s');

            if ($request->has('modifiedUser')) {
                $setting->modifiedUser = $request->modifiedUser;
            }

            DB::select(
                'SELECT * FROM public.update_purchase_settings(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $id,
                    $setting->VendorInvoiceNoChecking,
                    $setting->showProductDescription,
                    $setting->showFeeQtyColumn,
                    $setting->ActivatePurchaseRetention,
                    $setting->ChangeSalesPrice,
                    $setting->showLineDiscount,
                    $setting->CloseAfterSave,
                    $setting->printAfterSave,
                    $setting->branchId,
                    $setting->ActiveGodown,
                    $setting->gridFixedHeight,
                    $setting->retentionLedgerId
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
                'error'  => false,
                'message' => 'Purchase settings updated successfully'
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
