<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;

use App\Models\SalesSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesSettingsController extends Controller
{
    /**
     * Display sales settings
     */
    public function index()
    {
        try {
            // 🔹 Call the PostgreSQL function
            $settings = DB::select('SELECT * FROM public.get_all_sales_settings()');

            // 🔹 Return JSON response
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Sales settings retrieved successfully',
                'data'    => $settings
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    /**
     * Update the specified sales setting
     */
    public function update(Request $request, $id)
    {
        try {

            DB::beginTransaction();

            $result = DB::selectOne(
                'SELECT public.update_sales_settings(
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? ,?,?,?,?
    ) AS message',
                [
                    $id,
                    $request->showProductDescription,
                    $request->showFeeQtyColumn,
                    $request->showLineDiscount,
                    $request->showPurchaserate,
                    $request->ShowPartNo,
                    $request->ActivateSalesRetention,
                    $request->showBillProfit,
                    $request->showBillDiscountAmount,
                    $request->showBillDiscountPerc,
                    $request->CloseAfterSave,
                    $request->printAfterSave,
                    $request->branchId,
                    $request->showProductDetails,
                    $request->focusAfterSalesRate,
                    $request->GridFocusingBarcodeToNext,
                    $request->GridFocusingProductNameToNext,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                    $request->ActiveGodown,
                    $request->ledgerPricingAlert,
                    $request->ledgerPricingAlertAction,
                    $request->gridFixedHeight,
                    $request->ProductLookUpModel,
                    $request->askConfirmationWithSameProduct,
                    $request->DefaultPaymentMode,
                    $request->DefaultBankAccountForInvoicePrint,
                    $request->showPaymentGridInSalesOrder,
                    $request->ShowAdditionalFieldsInSalesInvoice,
                    $request->showStockCount,
                    $request->retentionLedgerId,
                    $request->ShowSize,
                    $request->showCustomerBalanceBill,
                    $request->showStockInproductLookUp,
                    (bool) ($request->validatePurchaseRate ?? false),
                    $request->PaymentModeChangeInCustomerChange,
                    $request->CanChangeDate,
                    (bool) ($request->WeightVisible ?? false),
                    (bool) ($request->OriginVisible ?? false),
                    (bool) ($request->PurchaseRateIsWithShortkey ?? false),

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
                'status'  => 200,
                'error'   => false,
                'message' => $result->message ?? 'Sales settings updated successfully',
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
}