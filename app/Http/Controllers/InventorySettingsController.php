<?php

namespace App\Http\Controllers;
use App\Helpers\ActivityHelper;

use App\Models\InventorySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventorySettingsController extends Controller
{
    /**
     * Display inventory settings
     */
    public function index()
    {
        try {
            // Call the PostgreSQL function
            $inventorySettings = DB::select('SELECT * FROM public.get_all_inventory_settings()');

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Inventory settings retrieved successfully',
                'data' => $inventorySettings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => 'Failed to retrieve inventory settings',
                'details' => $e->getMessage()
            ]);
        }
    }



    /**
     * Update the specified inventory setting
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Prepare all input values, defaulting to null if not present
            $NegativeStock = $request->input('NegativeStock', null);
            $stockValueCalculation = $request->input('stockValueCalculation', null);
            $SalesPriceUpdateByCostPricePercentage = $request->boolean('SalesPriceUpdateByCostPricePercentage', false);
            $maintainGodown = $request->boolean('maintainGodown', false);
            $maintainRack = $request->boolean('maintainRack', false);
            $lowStockReminder = $request->boolean('lowStockReminder', false);
            $CloseAfterSave = $request->boolean('CloseAfterSave', false);
            $branchId = $request->input('branchId', null);
            $ModifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $ModifiedUser = $request->input('modifiedUser', null);
            $AddProductMainGroupCodeWithProductCode = $request->boolean('AddProductMainGroupCodeWithProductCode', false);
            $activateMultyUnit = $request->boolean('activateMultyUnit', false);
            // Call PostgreSQL function
            $result = DB::selectOne(
                'SELECT public.update_inventory_settings(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?
            ) AS result',
                [
                    $id,
                    $NegativeStock,
                    $stockValueCalculation,
                    $SalesPriceUpdateByCostPricePercentage,
                    $maintainGodown,
                    $maintainRack,
                    $lowStockReminder,
                    $CloseAfterSave,
                    $AddProductMainGroupCodeWithProductCode,
                    $branchId,
                    $ModifiedDate,
                    $ModifiedUser,
                    $activateMultyUnit
                ]
            );

            if (!$result->result) {
                DB::rollBack();
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Inventory setting not found or not updated',
                ], 404);
            }
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

            // Fetch updated record using get_all_inventory_settings function
            $updatedSetting = DB::selectOne(
                'SELECT * FROM public.get_all_inventory_settings() WHERE "InventorySettingsId" = ?',
                [$id]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Inventory settings updated successfully',
                'data' => $updatedSetting
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
