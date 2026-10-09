<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\Product;
use App\Models\PurchaseOrderMaster;
use App\Models\PurchaseOrderDetails1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderMasetrController extends Controller
{
    /**
     * List Purchase Order
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $purchase = DB::select('SELECT * FROM public.get_purchase_order_master_list(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($purchase);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Order Retrieved Successfully',
            'data' => $paginated,
            'meta'    => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($total / $limit),
            ]
        ]);
    }

    /**
     * Get Purchase Order by id
     */
    public function show($id)
    {
        $purchase = DB::select('SELECT * FROM public.get_purchase_order_master_data_byid(?)', [$id]);

        // Check if found
        if (empty($purchase)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Order Not Found',
            ], 404);
        }

        $purchase = (array) $purchase[0];

        $purchaseDetails = DB::select('SELECT * FROM public.get_purchase_order_details1_byid(?)', [$purchase['orderMasterId']]);
        $purchaseDetails = array_map(function ($item) {
            $item = (array) $item;

            if (!empty($item['productCode'])) {
                $product = Product::with('salesPrices')
                    ->where('productCode', $item['productCode'])
                    ->first();

                $item['salesPrices'] = $product
                    ? $product->salesPrices->toArray()
                    : [];
            } else {
                $item['salesPrices'] = [];
            }

            return $item;
        }, $purchaseDetails);
        $purchase['purchaseDetails'] = $purchaseDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Order Retrieved Successfully',
            'data' => $purchase,
        ], 200);
    }


    /**
     * Create Purchase Order
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);

            // Insert Purchase Order
            $result = DB::select('SELECT public.insert_purchase_order_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS out_orderMasterId', [
                $codeData['table_pk'],      // orderMasterId
                $codeData['suffixPrefixId'],   // suffixPrefixId
                $codeData['voucherNo'],        // voucherNo
                $codeData['voucherCode'],          // orderNo
                $request->date,
                $request->dueDate,
                $request->ledgerId,
                $request->partyName,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->narration,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->costCentreId,
                $request->BatchId,
                $request->taxType,
                $request->subTotal,
                $request->billDiscount,
                $request->additionalCost,
                $request->OtherCharge,
                $request->otherChargeLedgerId,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundoff,
                $request->totalAmount,
                $request->transportCompany,
                $request->PaymentTerms,
                $request->Certificate,
                $request->DeliveryTerms,
                $request->cancelled,
                $request->branchId,
                $createdDate,
                $request->CreatedUser
            ]);
            // dd($result);
            $purchase = PurchaseOrderMaster::find($result[0]->out_ordermasterid);

            // Insert into purchaseDetails
            if ($request->has('purchaseDetails')) {
                foreach ($request->purchaseDetails as $sd) {

                    $result = DB::select('SELECT public.insert_purchase_order_details1(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS out_orderDetails1Id', [
                        (PurchaseOrderDetails1::max('orderDetails1Id') ?? 0) + 1,
                        $purchase->orderMasterId,
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],
                        $sd['unitId'],
                        $sd['amount'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['OtherChargeOnProduct'],
                        $sd['productDescription'],
                        $request->branchId,
                        $createdDate,
                        $request->CreatedUser,
                        NULL, // ModifiedDate
                        NULL  // ModifiedUser
                    ]);
                }
            }
            $master = PurchaseOrderMaster::find($purchase->orderMasterId);

            $details = PurchaseOrderDetails1::where(
                'orderMasterId',
                $purchase->orderMasterId
            )->get();


            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Order insert',
                'Purchase Order',
                'INSERTED',
                $request->branchId,
                $request->CreatedUser,
                $purchase->orderMasterId,
                $purchase->voucherNo ?? null,
                $request->date,
                null,
                $request->totalAmount
            );
            DB::commit();

            return response()->json([
                'status' => 201,
                'error'  => false,
                'message' => 'Purchase Order Inserted Successfully',
                'data' => [
                    'orderMasterId' => $master->orderMasterId,
                    'orderNo'       => $master->orderNo,

                    'payload' => [
                        'purchaseOrderMaster' => [
                            ...$master->toArray(),
                            'purchaseDetails' => $details->toArray()
                        ]
                    ]
                ]
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
     * Update Purchase Order
     */
    public function update(Request $request, $id)
    {
        $purchase = PurchaseOrderMaster::find($id);

        if (!$purchase) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Order Not Found',
            ], 404);
        }
        $oldValue = $purchase->totalAmount;
         $branchId = $request->input('branchId', 1);
        $modifiedDate = getBranchNow($branchId);

        DB::beginTransaction();
        try {

            // Update Purchase Order
            DB::statement('SELECT public.update_purchase_order_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )', [
                $id,
                $request->date,
                $request->dueDate,
                $request->ledgerId,
                $request->partyName,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->narration,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->costCentreId,
                $request->BatchId,
                $request->taxType,
                $request->subTotal,
                $request->billDiscount,
                $request->additionalCost,
                $request->OtherCharge,
                $request->otherChargeLedgerId,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundoff,
                $request->totalAmount,
                $request->transportCompany,
                $request->PaymentTerms,
                $request->Certificate,
                $request->DeliveryTerms,
                $request->cancelled,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser
            ]);

            if ($request->has('purchaseDetails')) {
                DB::select('SELECT delete_purchase_order_details1_byid(?)', [$purchase->orderMasterId]);

                foreach ($request->purchaseDetails as $sd) {
                    $result = DB::select('SELECT public.insert_purchase_order_details1(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS out_orderDetails1Id', [
                        (PurchaseOrderDetails1::max('orderDetails1Id') ?? 0) + 1,
                        $purchase->orderMasterId,
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['freeQty'],
                        $sd['rate'],
                        $sd['unitId'],
                        $sd['amount'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['discountPercentage'],
                        $sd['taxId'],
                        $sd['taxType'],
                        $sd['taxAmount'],
                        $sd['grossAmount'],
                        $sd['netAmount'],
                        $sd['billDiscOnProduct'],
                        $sd['AddCostonProduct'],
                        $sd['OtherChargeOnProduct'],
                        $sd['productDescription'],
                        $request->branchId,
                        $purchase->CreatedDate,
                        $purchase->CreatedUser,
                        $modifiedDate,
                        $request->ModifiedUser
                    ]);
                }
            }
            // dd($request->all());
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Purchase Order Update',
                'Purchase Order',
                'UPDATED',
                $request->branchId,
                $request->ModifiedUser,
                $purchase->orderMasterId,
                $purchase->voucherNo ?? null,
                $request->date,
                $oldValue,
                $request->totalAmount
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Order Updated Successfully'
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

    /**
     * Delete Purchase Order
     */
    public function destroy($id)
    {
        $purchase = PurchaseOrderMaster::find($id);

        if (!$purchase) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Order Not Found',
            ], 404);
        }
        $oldValue = $purchase->totalAmount;
        DB::beginTransaction();
        try {
            DB::select('SELECT delete_purchase_order_details1_byid(?)', [$id]);
            DB::statement('SELECT public.delete_purchase_order_master_byid(?)', [$id]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,                   // userId
                'Purchase Order Delete',
                'Purchase Order',
                'DELETED',
                $purchase->branchId,
                $purchase->CreatedUser ?? 'System',
                $purchase->orderMasterId,
                $purchase->voucherNo ?? null,
                $purchase->date ?? null,
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Order Deleted Successfully',
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

    public function purchaseOrder(Request $request)
    {
        try {

            $fromDate = $request->fromDate;
            $toDate = $request->toDate;
            $optional = false;
            $condition = $request->condition ?? 'All';
            $dueOn = !empty($request->dueOn) ? $request->dueOn : null;
            $ledgerId = $request->ledgerId ?? 'All';
            $branchId = $request->branchId;
            $currencyId = $request->currencyId;
            $userId = $request->userId;
            $costCentreId = $request->costCentreId ?? 'All';
            $mode = $request->mode ?? 'Detailed';

            $data = DB::select(
                'SELECT * FROM public."purchase_order_report_fill_detail"(
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )',
                [
                    $fromDate,
                    $toDate,
                    $optional,
                    $condition,
                    $dueOn,
                    $ledgerId,
                    $branchId,
                    $currencyId,
                    $userId,
                    $costCentreId,
                    $mode
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $data,
                'message' => 'Purchase Order Report Fetched Successfully'
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function purchaseOrderAgainstQuotationList(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;
        $ordermasterId = $request->ordermasterId;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.purchase_order_against_quotation_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $ordermasterId
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Order Against Quotation List Retrieved Successfully',
            'data' => $results,
        ]);
    }
    public function purchaseOrderAgainstQuotationDetails(Request $request)
    {
        $SalesOrderinvoices = DB::select('SELECT * FROM public.get_purchase_quotation_master_data_byid(?)', [
            $request->quotationmasterId
        ]);

        // Check if found
        if (empty($SalesOrderinvoices)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Quotation Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $SalesOrderinvoices = (array) $SalesOrderinvoices[0];

        // Step 2: Get invoice details using proformamasterid
        $SalesOrderinvoicesDetails = DB::select('SELECT * FROM public.purchase_order_against_quotation_details(?, ?)', [
            $SalesOrderinvoices['quotationMasterId'],
            $request->ordermasterId
        ]);

        // Step 3: Attach invoice details to master data
        $SalesOrderinvoices['purchaseDetails'] = $SalesOrderinvoicesDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Purchase Quotation Details Retrieved Successfully',
            'data' => $SalesOrderinvoices,
        ], 200);
    }
}