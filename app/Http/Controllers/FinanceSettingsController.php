<?php

namespace App\Http\Controllers;
use App\Helpers\ActivityHelper;

use App\Models\FinanceSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class FinanceSettingsController extends Controller
{
    /**
     * Display finance settings
     */
    public function index()
    {
        try {
            $settings = DB::select(
                'SELECT * FROM public.get_all_finance_settings()'
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Finance settings retrieved successfully',
                'data'    => $settings
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Update the specified finance setting
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $result = DB::selectOne(
                'SELECT public.update_finance_settings(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS result',
                [
                    $id,
                    $request->boolean('ShowAllTransactions'),
                    $request->boolean('MaintainBillbyBill'),
                    $request->boolean('multiCurrency'),
                    $request->boolean('showLedgerbalance'),
                    $request->boolean('CloseAfterSave'),
                    $request->boolean('printAfterSave'),
                    $request->branchId,
                    $request->defaultSalesAccount,
                    $request->defaultPurchaseAccount,

                    $request->DefaultCashAccount,
                    $request->DefaultBankAccount,

                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->modifiedUser,
                    $request->DashboardDateRangeInDays
                ]
            );

            if (!$result || $result->result === false) {
                DB::rollBack();
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Finance setting not found',
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

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Finance settings updated successfully'
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
