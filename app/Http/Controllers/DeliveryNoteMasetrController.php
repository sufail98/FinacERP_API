<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteDetails1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteMasetrController extends Controller
{
    /**
     * List Delivery Note Details
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $delivery = DB::select(
            'SELECT * FROM public.get_all_delivery_note_master_list(?,?,?,?,?,?,?)',
            [
                $request->fromDate ?? null,
                $request->toDate ?? null,
                $limit,
                $page,
                $request->branchId,
                $request->vouchercode ?? null,
                $request->customername ?? null
            ]
        );

        $collection = collect($delivery);
        $total = $collection->count();

        // ✅ Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Details Retrieved Successfully',
            'data' => $paginated,
            'meta' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $limit > 0 ? ceil($total / $limit) : 0,
            ]
        ]);
    }

    /**
     * Get Delivery Note Details by id
     */
    public function show($id)
    {
        $salesQuotation = DB::select('SELECT * FROM public.get_delivery_note_master_data_byid(?)', [$id]);

        if (empty($salesQuotation)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Delivery Note Details Not Found',
            ], 404);
        }

        $salesQuotation = (array) $salesQuotation[0];

        $salesQuotationDetails = DB::select(
            'SELECT * FROM public.get_delivery_note_details_by_masterid(?)',
            [$salesQuotation['deliveryNoteMasterId']]
        );

        $salesQuotationDetails = collect($salesQuotationDetails)->map(function ($detail) use ($salesQuotation) {
            $detail = (array) $detail;

            // Fetch product with unit conversions and sales prices
            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {
                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            // Get mrp from tbl_ProductSalesPrice
            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', $detail['unitId'])
                ->where('branchId', $salesQuotation['branchId'])
                ->value('amount');

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => $product->brand ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $salesQuotation['deliveryDetails'] = $salesQuotationDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Details Retrieved Successfully',
            'data' => $salesQuotation,
        ], 200);
    }
    /**
     * Create Delivery Note Details
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {

            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $orderMasterId = is_array($request->orderMasterId)
                ? implode(',', $request->orderMasterId)
                : $request->orderMasterId;

            $quotationMasterId = is_array($request->quotationMasterId)
                ? implode(',', $request->quotationMasterId)
                : $request->quotationMasterId;

            $proformaMasterId = is_array($request->proformaMasterId)
                ? implode(',', $request->proformaMasterId)
                : $request->proformaMasterId;
            // Insert Delivery Note Details
            $createdDate = getBranchNow($branchId);
            $masterParams = [
                $codeData['table_pk'],
                $codeData['suffixPrefixId'],
                $codeData['voucherNo'],
                $codeData['voucherCode'],
                $request->date,
                $request->ledgerId,
                $request->customerName,
                $request->CustomerAddress,
                $request->CustomerPhone,
                $request->CustomerVATNo,
                $request->RefNo,
                $request->RefDate,
                $request->costCentreId,
                $request->pricingLevelId,
                $request->employeeId,
                $request->narration,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->LPONo,
                $request->LPODate,
                $request->AgainstNo,
                $orderMasterId,
                $quotationMasterId,
                $proformaMasterId,
                $request->BatchId,
                $request->taxType,
                $request->totalAmount,
                $request->taxableAmt,
                $request->additionalCost,
                $request->totalTax,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0), // ✅ NEW

                $request->roundOff,
                $request->othercharge,
                $request->otherChargeLedgerId,
                $request->taxTotal,
                $request->grandTotal,
                $request->transportCompany,
                $request->deliveryLocation,
                $request->driverName,
                $request->contactNo,
                $request->deliveryVehicleNo,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $createdDate,
                $request->CreatedUser
            ];

            $sql = 'SELECT public.insert_delivery_note_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ') AS out_deliveryNoteMasterId';

            $result = DB::selectOne($sql, $masterParams);

            $inserted = $result->out_deliverynotemasterid ?? null;


            // $inserted = $result->out_deliverynotemasterid;

            if (!$inserted || $inserted === null) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => 'Failed to insert Delivery Note',
                ], 500);
            }

            // Get Eloquent instance
            $delivery = DeliveryNote::find($inserted);
            // Insert into tbl_DeliveryNoteDetails1
            if ($request->has('deliveryDetails')) {
                foreach ($request->deliveryDetails as $qd) {
                    $branchNow = getBranchNow($qd['branchId']);
                    $detailParams = [
                        (DeliveryNoteDetails1::max('deliveryNoteDetails1Id') ?? 0) + 1,
                        $delivery->deliveryNoteMasterId,
                        $qd['productCode'],
                        $qd['SlNo'],
                        $qd['qty'],
                        $qd['freeQty'],
                        $qd['rate'],

                        $qd['inclusiveRate'] ?? 0,           // ✅ NEW
                        $qd['lineDiscountWithTax'] ?? 0,     // ✅ NEW

                        $qd['unitId'],
                        $qd['amount'],
                        $qd['ConversionFactor'],
                        $qd['barcode'],
                        $qd['discountPercentage'],
                        $qd['taxAmount'],
                        $qd['grossAmount'],
                        $qd['taxType'],
                        $qd['netAmount'],
                        $qd['billDiscOnProduct'],
                        $qd['AddCostonProduct'],
                        $qd['productDescription'],
                        $qd['PurchaseRate'],
                        $qd['otherchargeonproduct'],
                        $qd['Remark'],
                        $qd['taxId'],
                        $qd['GodownId'],
                        $qd['RackId'],
                        $qd['orderDetails1Id'],
                        $qd['quotationDetailsId'],
                        $qd['proformaDetails1Id'],
                        $qd['branchId'],
                        $branchNow,
                        $request->CreatedUser,
                        null,
                        null
                    ];

                    $sql = 'SELECT public.insert_delivery_note_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    DB::select($sql, $detailParams);
                }
            }
            $delivery = DeliveryNote::find($inserted);
            $details = DeliveryNoteDetails1::where(
                'deliveryNoteMasterId',
                $inserted
            )->get();


            ActivityHelper::logActivity(
                $request->employeeId ?? null,
                'Delivery Note Insert',
                'Delivery Note',
                'INSERTED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $delivery->deliveryNoteMasterId,
                $delivery->voucherNo ?? null,
                $delivery->date ?? null,
                null,
                $request->grandTotal
            );
            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Delivery Note Created Successfully',
                'data' => [
                    'deliveryNoteMasterId' => $delivery->deliveryNoteMasterId,
                    'deliveryNoteNo'       => $delivery->deliveryNoteNo,

                    'payload' => [
                        'deliveryNoteMaster' => [
                            ...$delivery->toArray(),
                            'deliveryDetails' => $details->toArray()
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
     * Update Delivery Note Details
     */
    public function update(Request $request, $id)
    {
        $delivery = DeliveryNote::find($id);

        if (!$delivery) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Delivery Note Details Not Found',
            ], 404);
        }
        $oldAmount = $delivery->grandTotal;

        DB::beginTransaction();
        try {
            // Update fields
            $orderMasterId = is_array($request->orderMasterId)
                ? implode(',', $request->orderMasterId)
                : $request->orderMasterId;

            $quotationMasterId = is_array($request->quotationMasterId)
                ? implode(',', $request->quotationMasterId)
                : $request->quotationMasterId;

            $proformaMasterId = is_array($request->proformaMasterId)
                ? implode(',', $request->proformaMasterId)
                : $request->proformaMasterId;
            $branchId = $request->input('branchId', 1);
            $modifiedDate = getBranchNow($branchId);
            $masterParams = [
                $id,
                $request->date,
                $request->ledgerId,
                $request->customerName,
                $request->CustomerAddress,
                $request->CustomerPhone,
                $request->CustomerVATNo,
                $request->RefNo,
                $request->RefDate,
                $request->costCentreId,
                $request->pricingLevelId,
                $request->employeeId,
                $request->narration,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->LPONo,
                $request->LPODate,
                $request->AgainstNo,
                $orderMasterId,
                $quotationMasterId,
                $proformaMasterId,
                $request->BatchId,
                $request->taxType,
                $request->totalAmount,
                $request->taxableAmt,
                $request->additionalCost,
                $request->totalTax,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0), // ✅ ADDED

                $request->roundOff,
                $request->othercharge,
                $request->otherChargeLedgerId,
                $request->taxTotal,
                $request->grandTotal,
                $request->transportCompany,
                $request->deliveryLocation,
                $request->driverName,
                $request->contactNo,
                $request->deliveryVehicleNo,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser
            ];

            $sql = 'SELECT * FROM public.update_delivery_note_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ')';

            $result = DB::select($sql, $masterParams);
            $originalCreatedDate = $delivery->CreatedDate ?? $delivery->created_at;
            $originalCreatedUser = $delivery->CreatedUser ?? $delivery->created_user;
            /**
             * Update deliveryDetails
             */

            // Insert into tbl_DeliveryNoteDetails1
            if ($request->has('deliveryDetails')) {
                DB::select('SELECT delete_delivery_note_details1_byid(?)', [$id]);

                foreach ($request->deliveryDetails as $qd) {
                    $branchNow = getBranchNow($qd['branchId']);
                    $detailParams = [
                        (DeliveryNoteDetails1::max('deliveryNoteDetails1Id') ?? 0) + 1,
                        $delivery->deliveryNoteMasterId,
                        $qd['productCode'],
                        $qd['SlNo'],
                        $qd['qty'],
                        $qd['freeQty'],
                        $qd['rate'],

                        $qd['inclusiveRate'] ?? 0,           // ✅ ADDED
                        $qd['lineDiscountWithTax'] ?? 0,     // ✅ ADDED

                        $qd['unitId'],
                        $qd['amount'],
                        $qd['ConversionFactor'],
                        $qd['barcode'],
                        $qd['discountPercentage'],
                        $qd['taxAmount'],
                        $qd['grossAmount'],
                        $qd['taxType'],
                        $qd['netAmount'],
                        $qd['billDiscOnProduct'],
                        $qd['AddCostonProduct'],
                        $qd['productDescription'],
                        $qd['PurchaseRate'],
                        $qd['otherchargeonproduct'],
                        $qd['Remark'],
                        $qd['taxId'],
                        $qd['GodownId'],
                        $qd['RackId'],
                        $qd['orderDetails1Id'],
                        $qd['quotationDetailsId'],
                        $qd['proformaDetails1Id'],
                        $qd['branchId'],
                        $originalCreatedDate,
                        $originalCreatedUser,
                        $branchNow,
                        $request->ModifiedUser
                    ];

                    $sql = 'SELECT public.insert_delivery_note_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    DB::select($sql, $detailParams);
                }
            }
            ActivityHelper::logActivity(
                $request->employeeId ?? null,
                'Delivery Note Update',
                'Delivery Note',
                'UPDATED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $delivery->deliveryNoteMasterId,
                $delivery->voucherNo ?? null,
                $delivery->date ?? null,
                $oldAmount,
                $request->grandTotal
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Delivery Note Details Updated Successfully'
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
     * Delete Delivery Note Details
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $delivery = DeliveryNote::find($id);

            if (!$delivery) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Delivery Note Not Found',
                ], 404);
            }
            $oldAmount = $delivery->grandTotal;

            DB::statement('SELECT public.delete_delivery_note_details1_byid(?)', [$id]);

            DB::statement('SELECT public.delete_delivery_note_master_byid(?)', [$id]);
            ActivityHelper::logActivity(
                $delivery->employeeId ?? null,
                'Delivery Note Delete',
                'Delivery Note',
                'DELETED',
                $delivery->branchId,
                $delivery->ModifiedUser ?? 'System',
                $delivery->deliveryNoteMasterId,
                $delivery->voucherNo ?? null,
                $delivery->date ?? null,
                $oldAmount,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Delivery Note Deleted Successfully',
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


    public function DeliveryNoteQuotationList(Request $request)
    {
        $ledgerId = $request->in_ledgerId;
        $branchId = $request->in_branchId;
        $in_deliverynotemasterId = $request->in_deliverynotemasterId;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_delivery_note_sales_quotation_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $in_deliverynotemasterId
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Sales Quotation List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function DeliveryNoteQuotationDetails(Request $request)
    {
        $quotationIds = (array) $request->p_quotation_master_id;

        if (empty($quotationIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Quotation ID required',
            ], 400);
        }

        // ✅ Convert to JSON string for TEXT-based SQL function
        $quotationJson = json_encode($quotationIds);

        $masters = DB::select(
            'SELECT * FROM public.get_delivery_note_sales_quotation_master_byid(?, ?)',
            [
                $quotationJson,
                $request->p_branchid
            ]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Quotation Not Found',
            ], 404);
        }

        // ✅ Use first master as base structure
        $master = (array) $masters[0];

        // ✅ Replace ID with array of all IDs
        $master['quotationMasterId'] = $quotationIds;

        $branchId = $master['branchId'] ?? $request->p_branchid;

        $allDetails = [];

        foreach ($quotationIds as $quotationId) {

            $details = DB::select(
                'SELECT * FROM public.get_delivery_note_sales_quotation_details_with_remaining(?, ?, ?)',
                [
                    $quotationId,
                    $request->p_delivery_note_master_id,
                    $branchId
                ]
            );

            $allDetails = array_merge($allDetails, $details);
        }

        $allDetails = collect($allDetails)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {

                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', (int) $detail['unitId'])
                ->where('branchId', (int) $branchId)
                ->value('amount');

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => optional($product->brand)->brandName,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $master['deliveryDetails'] = $allDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Quotation Details Retrieved Successfully',
            'data' => $master,
        ], 200);
    }

    public function DeliveryNoteProformaList(Request $request)
    {
        $ledgerId = $request->p_ledgerid;
        $branchId = $request->p_branchid;
        $p_deliverynotemasterid = $request->p_deliverynotemasterid;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_delivery_note_proforma_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $p_deliverynotemasterid
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Proforma List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function DeliveryNoteProformaDetails(Request $request)
    {
        $proformaIds = (array) $request->p_proformamasterid;

        if (empty($proformaIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Proforma ID required',
            ], 400);
        }

        // ✅ Fetch master records
        $masters = DB::select(
            'SELECT * FROM public.get_delivery_note_proforma_master_byid(?, ?)',
            [
                json_encode($proformaIds),
                $request->p_branchid
            ]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Proforma Not Found',
            ], 404);
        }

        // ✅ Use first record as base structure
        $baseMaster = (array) $masters[0];

        // ✅ Merge multiple masters into comma-separated string
        $voucherNos = [];
        $proformaNos = [];
        $proformaMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'];
            $proformaNos[] = $m['proformaNo'];
            $proformaMasterIds[] = $m['proformaMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', $voucherNos);
        $baseMaster['proformaNo'] = implode(',', $proformaNos);
        $baseMaster['proformaMasterId'] = implode(',', $proformaMasterIds);

        $branchId = $baseMaster['branchId'] ?? $request->p_branchid;

        $allDetails = [];

        foreach ($proformaIds as $proformaId) {

            $details = DB::select(
                'SELECT * FROM public.get_delivery_note_proforma_details_with_remaining(?, ?, ?)',
                [
                    $proformaId,
                    $request->p_deliverynotemasterid,
                    $branchId
                ]
            );

            $allDetails = array_merge($allDetails, $details);
        }

        $allDetails = collect($allDetails)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {

                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', (int) $detail['unitId'])
                ->where('branchId', (int) $branchId)
                ->value('amount');

            $brandName = null;
            if ($product) {
                $brandName = DB::table('tbl_Brand')
                    ->where('brandId', $product->brandId)
                    ->value('brandName');
            }

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => $brandName ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $baseMaster['deliveryDetails'] = $allDetails->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Proforma Details Retrieved Successfully',
            'data' => $baseMaster,
        ], 200);
    }

    public function DeliveryNoteSalesOrderList(Request $request)
    {
        $ledgerId = $request->p_ledgerid;
        $branchId = $request->p_branchid;
        $p_deliverynotemasterid = $request->p_deliverynotemasterid;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_delivery_note_sales_order_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $p_deliverynotemasterid
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Sales Order List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function DeliveryNoteSalesOrderDetails(Request $request)
    {
        $orderIds = (array) $request->p_ordermasterid;

        if (empty($orderIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Sales Order ID required',
            ], 400);
        }

        // ✅ Fetch master records
        $masters = DB::select(
            'SELECT * FROM public.get_delivery_note_sales_order_master_byid(?)',
            [json_encode($orderIds)]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Order Not Found',
            ], 404);
        }

        // ✅ Use first as base
        $baseMaster = (array) $masters[0];

        // ✅ Merge multiple masters into comma-separated string
        $voucherNos = [];
        $orderNos = [];
        $orderMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'];
            $orderNos[] = $m['orderNo'];
            $orderMasterIds[] = $m['orderMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', $voucherNos);
        $baseMaster['orderNo'] = implode(',', $orderNos);
        $baseMaster['orderMasterId'] = implode(',', $orderMasterIds);

        $branchId = $baseMaster['branchId'] ?? null;

        $allDetails = [];

        foreach ($orderIds as $orderId) {

            $details = DB::select(
                'SELECT * FROM public.get_delivery_note_sales_order_details_with_remaining(?, ?)',
                [
                    $orderId,
                    $request->p_deliverynotemasterid
                ]
            );

            $allDetails = array_merge($allDetails, $details);
        }

        $allDetails = collect($allDetails)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel'
            ])->find($detail['productCode']);

            $units = [];

            if ($product && $product->unitConversions) {
                foreach ($product->unitConversions as $conversion) {

                    $salesPrice = $product->salesPrices
                        ->firstWhere('unitId', $conversion->unitId);

                    $units[] = [
                        'barcode'          => $conversion->barcode ?? null,
                        'unitid'           => $conversion->unitId,
                        'unitname'         => $conversion->unit->UnitName ?? null,
                        'unitconversionid' => $conversion->unitConversionId,
                        'conversionrate'   => $conversion->ConversionFactor,
                        'salesprice'       => $salesPrice->salesPrice ?? '0.000',
                    ];
                }
            }

            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', (int) $detail['unitId'])
                ->where('branchId', (int) $branchId)
                ->value('amount');

            $brandName = null;
            if ($product) {
                $brandName = DB::table('tbl_Brand')
                    ->where('brandId', $product->brandId)
                    ->value('brandName');
            }

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => $brandName ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $baseMaster['deliveryDetails'] = $allDetails->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Delivery Note Sales Order Details Retrieved Successfully',
            'data' => $baseMaster,
        ], 200);
    }

    public function DeliveryNoteReport(Request $request)
    {
        try {
            $fromDate = $request->fromDate;
            $toDate = $request->toDate;
            $optional = false;
            $branchId = $request->branchId;
            $currencyId = $request->currencyId;
            $partyName = $request->partyName;
            $userId = $request->userId;
            $ledgerId = $request->ledgerId;
            $orderId = $request->orderId;
            $quotationMasterId = $request->quotationMasterId;
            $salesManId = $request->salesManId;
            $condition = $request->condition;
            $isAccountsPosting = $request->isAccountsPosting;
            $data = DB::select(
                'SELECT * FROM public."delivery_note_report_fill_detailed"(?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $fromDate,
                    $toDate,
                    $optional,
                    $branchId,
                    $currencyId,
                    $partyName,
                    $userId,
                    $ledgerId ?? 0,
                    $orderId ?? 0,
                    $quotationMasterId ?? 0,
                    $salesManId ?? 0,
                    $condition ?? '',
                    $isAccountsPosting,
                    $request->mode ?? 'Detailed'
                ]
            );
            return response()->json([
                'status'  => true,
                'count'   => count($data),
                'data'    => $data,
                'message' => 'Delivery Note Report fetched successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}