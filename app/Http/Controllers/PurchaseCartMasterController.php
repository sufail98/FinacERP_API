<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseCartDetailed;
use App\Models\PurchaseCartMaster;
use Illuminate\Http\Request;
use App\Helpers\ActivityHelper;

use Illuminate\Support\Facades\DB;

class PurchaseCartMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $fromDate = $request->fromDate ?? null;
            $toDate   = $request->toDate ?? null;

            // Call function with parameters
            $purchaseCart = DB::select(
                'SELECT * FROM public.get_all_purchase_carts(?, ?)',
                [$fromDate, $toDate]
            );

            // ✅ fetch all product names once
            $productMap = DB::table('tbl_Product')
                ->pluck('productName', 'productCode');

            $purchaseCart = array_map(function ($q) use ($productMap) {

                $details = json_decode($q->details);

                if (!empty($details)) {
                    foreach ($details as $d) {
                        $d->productName = $productMap[$d->productCode] ?? null;
                    }
                }

                $q->details = $details;
                return $q;
            }, $purchaseCart);

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $purchaseCart
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $branchId    = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId      = (int) $request->input('yearId', 1);


            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            $createdDate = getBranchNow($branchId);
            $data = DB::select(
                'SELECT public.insert_purchase_cart_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS out_masterId',
                [
                    $codeData['table_pk'],
                    $codeData['suffixPrefixId'],
                    $codeData['voucherNo'],
                    $codeData['voucherCode'],
                    // $request->input('PurchaseCartNo'),
                    $request->input('date', now()),
                    $request->input('Narration'),
                    $request->input('CustomerName'),
                    $request->input('CustomerPhone'),
                    $request->input('Status', 'Pending'),
                    $branchId,
                    $createdDate,
                    $request->input('CreatedUser', 'admin')
                ]
            );

            $purchaseCartMasterId = $data[0]->out_masterid;


            if ($request->has('details')) {
                $details = $request->input('details');
                if (is_string($details)) {
                    $details = json_decode($details, true);
                }

                $maxDetailsId = DB::table('tbl_PurchaseCartDetails')->max('PurchaseCartDetailsId') ?? 0;

                foreach ($details as $d) {
                    $maxDetailsId++;

                    DB::select(
                        'SELECT public.insert_purchase_cart_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )',
                        [
                            $maxDetailsId,
                            $purchaseCartMasterId,
                            $d['productCode'] ?? null,
                            $d['barcode'] ?? null,
                            $d['productName'] ?? $d['manualItemName'] ?? null,
                            $d['manualItemDescription'] ?? null,
                            $d['Qty'] ?? 0,
                            $d['expectedPrice'] ?? 0,
                            $d['Priority'] ?? null,
                            $d['status'] ?? 'Pending',
                            $branchId,
                            $createdDate,
                            $request->input('CreatedUser', 'admin')
                        ]
                    );
                }
            }
            $master = DB::table('tbl_PurchaseCartMaster')
                ->where('PurchaseCartMasterId', $purchaseCartMasterId)
                ->first();

            $details = DB::table('tbl_PurchaseCartDetails')
                ->where('PurchaseCartMasterId', $purchaseCartMasterId)
                ->get();

                ActivityHelper::logActivity(
                $request->employeeId,
                'Purchase Invoice Insert',
                'Purchase Invoice',
                'INSERTED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $purchaseCartMasterId,
                $master->voucherNo,   // real, persisted voucher no.
                $master->CreatedDate,             // check actual column name — could be CreatedDate etc.
                null,
                $request->totalAmount
            );
            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Purchase Cart Inserted Successfully',
                'data'    => [
                    'purchaseCartMasterId' => $purchaseCartMasterId,
                    'purchaseCartNo'       => $master->PurchaseCartNo ?? null,

                    'payload' => [
                        'purchaseCartMaster' => [
                            ...(array) $master,
                            'details' => $details->toArray()
                        ]
                    ]
                ]
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
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $purchaseCart = DB::select(
                'SELECT * FROM public.get_purchase_cart_by_id(?)',
                [$id]
            );

            if (empty($purchaseCart)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Purchase Cart not found'
                ], 404);
            }

            $purchaseCart = array_map(function ($q) {

                // Decode details JSON
                if (isset($q->details)) {
                    $details = json_decode($q->details, true);

                    if (is_array($details)) {
                        foreach ($details as &$item) {
                            if (!empty($item['productCode'])) {
                                // Fetch product and salesPrices
                                $product = Product::with('salesPrices')
                                    ->where('productCode', $item['productCode'])
                                    ->first();

                                $item['salesPrices'] = $product
                                    ? $product->salesPrices->toArray()
                                    : [];
                            }
                        }
                    }

                    $q->details = $details;
                }

                return $q;
            }, $purchaseCart);

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $purchaseCart
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        DB::beginTransaction();

        try {
            // Find master record
            $purchaseCart = PurchaseCartMaster::find($id);

            if (!$purchaseCart) {
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => 'Purchase Cart not found',
                ], 404);
            }

            $branchId     = (int) $request->input('branchId', $purchaseCart->branchId);
            // $modifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $modifiedUser = $request->input('ModifiedUser', 'system');
            $modifiedDate = getBranchNow($branchId);
            // -------------------------
            // Update master record
            // -------------------------
            DB::select(
                'SELECT public.update_purchase_cart_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $id,                                         // PurchaseCartMasterId
                    $request->input('PurchaseCartNo', $purchaseCart->PurchaseCartNo),
                    $request->input('date', $purchaseCart->date),
                    $request->input('Narration', $purchaseCart->Narration),
                    $request->input('CustomerName', $purchaseCart->CustomerName),
                    $request->input('CustomerPhone', $purchaseCart->CustomerPhone),
                    $request->input('Status', $purchaseCart->Status),
                    $branchId,
                    $modifiedDate,
                    $modifiedUser
                ]
            );

            // -------------------------
            // Update detail rows if provided
            // -------------------------
            if ($request->has('details')) {
                $details = $request->input('details');
                if (is_string($details)) {
                    $details = json_decode($details, true);
                }

                foreach ($details as $d) {
                    DB::select(
                        'SELECT public.update_purchase_cart_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )',
                        [
                            $d['PurchaseCartDetailsId'],
                            $d['productCode'] ?? null,
                            $d['barcode'] ?? null,
                            $d['manualItemName'] ?? null,
                            $d['manualItemDescription'] ?? null,
                            $d['Qty'] ?? null,
                            $d['expectedPrice'] ?? null,
                            $d['Priority'] ?? null,
                            $d['status'] ?? null,
                            $branchId,
                            $modifiedDate,
                            $modifiedUser
                        ]
                    );
                }
            }

            ActivityHelper::logActivity(
                $request->employeeId,
                'Purchase Invoice update',
                'Purchase Invoice',
                'UPDATED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $id,
                $purchaseCart->voucherNo,   // real, persisted voucher no.
                $purchaseCart->CreatedDate,             // check actual column name — could be CreatedDate etc.
                null,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Purchase Cart Updated Successfully',
                'id'      => $id
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





    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {

            $purchaseCart = PurchaseCartMaster::find($id);
            ActivityHelper::logActivity(
                $purchaseCart->employeeId,
                'Purchase Invoice delete',
                'Purchase Invoice',
                'DELETED',
                $purchaseCart->branchId,
                $purchaseCart->CreatedUser ?? 'System',
                $id,
                $purchaseCart->voucherNo,   // real, persisted voucher no.
                $purchaseCart->CreatedDate,             // check actual column name — could be CreatedDate etc.
                null,
                null
            );
            DB::select('SELECT public.delete_purchase_cart(?)', [$id]);

            
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Cart deleted successfully',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error' => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function report(Request $request)
    {
        try {

            $result = DB::select(
                "SELECT * FROM purchase_cart_report(?, ?, ?, ?, ?, ?)",
                [
                    $request->from_date,
                    $request->to_date,
                    $request->branch_id,
                    $request->status ?? 'All',
                    $request->customer_name ?? 'All',
                    $request->mode
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Purchase Cart Report Generated Successfully',
                'mode' => $request->mode,
                'count' => count($result),
                'data' => $result
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Error generating report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}