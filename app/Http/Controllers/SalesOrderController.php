<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\SalesOrder;
use App\Models\SalesOrderdetails1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    /**
     * List Sales Order
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $SalesOrder = DB::select('SELECT * FROM public.get_all_sales_order_master_list(?,?,?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode,
            $request->customername ?? null,
            $request->status ?? null

        ]);

        $collection = collect($SalesOrder);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Order Retrieved Successfully',
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
     * Get Sales Order by id
     */
    public function show($id)
    {
        $SalesOrderinvoices = DB::select('SELECT * FROM public.get_sales_order_master_byid(?)', [$id]);

        // Check if found
        if (empty($SalesOrderinvoices)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Order Not Found',
            ], 404);
        }

        $SalesOrderinvoices = (array) $SalesOrderinvoices[0];

        $SalesOrderinvoicesDetails = DB::select(
            'SELECT * FROM public.get_sales_order_details1_by_ordermaster_id(?)',
            [$SalesOrderinvoices['orderMasterId']]
        );

        $SalesOrderinvoicesDetails = collect($SalesOrderinvoicesDetails)->map(function ($detail) use ($SalesOrderinvoices) {
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
                ->where('branchId', $SalesOrderinvoices['branchId'])
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

        $SalesOrderinvoices['orderDetails'] = $SalesOrderinvoicesDetails;

        $payments = DB::select('SELECT * FROM get_sales_order_payment_by_id(?)', [(int) $SalesOrderinvoices['orderMasterId']]);

        $SalesOrderinvoices['payments'] = $payments;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Order Retrieved Successfully',
            'data' => $SalesOrderinvoices,
        ], 200);
    }
    /**
     * Create Sales Order
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
            // Insert Sales Order
            // Insert Sales Order (MASTER)
            $masterParams = [
                $codeData['table_pk'],
                $codeData['suffixPrefixId'],
                $codeData['voucherNo'],
                $codeData['voucherCode'],  // orderNo
                $request->date,
                $request->dueDate,
                $request->ledgerId,
                $request->partyName,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->pricingLevelId,
                $request->employeeId,
                $request->narration,
                $request->costCentreId,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->AgainstNo,
                is_array($request->quotationMasterId)
                    ? json_encode($request->quotationMasterId)
                    : ($request->quotationMasterId ?? '[]'),

                is_array($request->proformaMasterId)
                    ? json_encode($request->proformaMasterId)
                    : ($request->proformaMasterId ?? '[]'),
                $request->LPONO,
                $request->LPODate,
                $request->status,
                $request->taxType,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0), // ✅ NEW

                $request->othercharge,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundOff,
                $request->totalAmount,
                $request->otherChargeLedgerId,
                $request->lrNo,
                $request->transportCompany,
                $request->branchId,
                $createdDate,
                $request->CreatedUser
            ];

            $sql = 'SELECT * FROM public.insert_sales_order_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ')';

            $result = DB::select($sql, $masterParams);


            // dd($result);
            $insertedId = $result[0]->out_ordermasterid ?? null;

            if (!$insertedId) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => 'Failed to Insert Sales Order',
                ], 500);
            }

            // Get Eloquent instance
            $SalesOrder = SalesOrder::find($insertedId);

            // Insert into orderDetails
            // Insert into orderDetails (DETAILS)
            if ($request->has('orderDetails')) {
                foreach ($request->orderDetails as $od) {
                    $branchNow = getBranchNow($od['branchId']);
                    $detailParams = [
                        (SalesOrderdetails1::max('orderDetails1Id') ?? 0) + 1,
                        $SalesOrder->orderMasterId,
                        $od['quotationDetailsId'],
                        $od['proformaDetails1Id'],
                        $od['productCode'],
                        $od['productDescription'],
                        $od['barcode'],
                        $od['qty'],
                        $od['freeQty'],
                        $od['rate'],

                        $od['inclusiveRate'] ?? 0,         // ✅ NEW
                        $od['lineDiscountWithTax'] ?? 0,   // ✅ NEW

                        $od['unitId'],
                        $od['amount'],
                        $od['ConversionFactor'],
                        $od['discountPercentage'],
                        $od['taxId'],
                        $od['taxType'],
                        $od['taxAmount'],
                        $od['grossAmount'],
                        $od['netAmount'],
                        $od['PurchaseRate'],
                        $od['billDiscOnProduct'],
                        $od['AddCostonProduct'],
                        $od['otherchargeonproduct'],
                        $od['SlNo'],
                        $od['branchId'],
                        $branchNow,
                        $request->CreatedUser,
                        null,
                        null

                    ];

                    $sql = 'SELECT * FROM public.insert_sales_order_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    DB::select($sql, $detailParams);
                }
            }
            if ($request->has('payments') && is_array($request->payments)) {
                foreach ($request->payments as $pay) {
                    $createdDate = getBranchNow($branchId);
                    if (
                        empty($pay['ledgerId']) ||
                        empty($pay['amount']) ||
                        (int)$pay['ledgerId'] <= 0 ||
                        (float)$pay['amount'] <= 0
                    ) {
                        continue;
                    }

                    DB::select(
                        'SELECT insert_sales_order_payment(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $SalesOrder->orderMasterId,
                            (int) $pay['ledgerId'],
                            (float) $pay['amount'],
                            $pay['date'] ?? null,
                            $pay['remarks'] ?? null,
                            $pay['extraDate'] ?? null,
                            $branchId,
                            (int) $request->userId,
                            $request->CreatedUser,
                            $createdDate
                        ]
                    );
                }
            }
            ActivityHelper::logActivity(
                $request->employeeId,
                'Sales Order Insert',
                'Sales Order',
                'INSERTED',
                $request->branchId,
                $request->ModifiedUser ?? 'System',
                $SalesOrder->orderMasterId,
                $SalesOrder->voucherNo ?? null,
                $SalesOrder->date ?? null,
                null,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Sales Order Created Successfully',
                'data'   => [
                    'salesOrderMasterId' => $SalesOrder->orderMasterId,
                    'orderNo'            => $SalesOrder->orderNo ?? null,

                    'payload' => [
                        'salesOrderMaster' => [
                            ...$SalesOrder->toArray(),
                            'orderDetails' => $request->orderDetails ?? [],
                            'payments'     => $request->payments ?? []
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
     * Update Sales Order
     */
    public function update(Request $request, $id)
    {
        $SalesOrder = SalesOrder::find($id);
        $oldAmount = $SalesOrder->totalAmount;

        if (!$SalesOrder) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Order Not Found',
            ], 404);
        }
        $branchId = $request->input('branchId', 1);
        $modifiedDate = getBranchNow($branchId);
        DB::beginTransaction();
        try {
            $masterParams = [
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
                $request->pricingLevelId,
                $request->employeeId,
                $request->narration,
                $request->costCentreId,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->AgainstNo,
                is_array($request->quotationMasterId)
                    ? json_encode($request->quotationMasterId)
                    : ($request->quotationMasterId ?? '[]'),

                is_array($request->proformaMasterId)
                    ? json_encode($request->proformaMasterId)
                    : ($request->proformaMasterId ?? '[]'),
                $request->LPONO,
                $request->LPODate,
                $request->status,
                $request->taxType,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0), // ✅ ADDED

                $request->othercharge,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundOff,
                $request->totalAmount,
                $request->otherChargeLedgerId,
                $request->lrNo,
                $request->transportCompany,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser
            ];

            $sql = 'SELECT * FROM public.update_sales_order_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ')';

            $result = DB::select($sql, $masterParams);

            $originalCreatedDate = $SalesOrder->CreatedDate ?? $SalesOrder->created_at;
            $originalCreatedUser = $SalesOrder->CreatedUser ?? $SalesOrder->created_user;

            // Insert into tbl_proformaInvoiceDetails1
            if ($request->has('orderDetails')) {
                DB::select('SELECT delete_sales_order_details1_byid(?)', [$SalesOrder->orderMasterId]);
                foreach ($request->orderDetails as $od) {
                    $branchNow = getBranchNow($od['branchId']);
                    $detailParams = [
                        (SalesOrderdetails1::max('orderDetails1Id') ?? 0) + 1,
                        $SalesOrder->orderMasterId,
                        $od['quotationDetailsId'],
                        $od['proformaDetails1Id'],
                        $od['productCode'],
                        $od['productDescription'],
                        $od['barcode'],
                        $od['qty'],
                        $od['freeQty'],
                        $od['rate'],

                        $od['inclusiveRate'] ?? 0,         // ✅ ADDED
                        $od['lineDiscountWithTax'] ?? 0,   // ✅ ADDED

                        $od['unitId'],
                        $od['amount'],
                        $od['ConversionFactor'],
                        $od['discountPercentage'],
                        $od['taxId'],
                        $od['taxType'],
                        $od['taxAmount'],
                        $od['grossAmount'],
                        $od['netAmount'],
                        $od['PurchaseRate'],
                        $od['billDiscOnProduct'],
                        $od['AddCostonProduct'],
                        $od['otherchargeonproduct'],
                        $od['SlNo'],
                        $od['branchId'],
                        $originalCreatedDate,
                        $originalCreatedUser,
                        $branchNow,
                        $request->ModifiedUser
                    ];

                    $sql = 'SELECT * FROM public.insert_sales_order_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    DB::select($sql, $detailParams);
                }
            }
            if ($request->has('payments')) {
                DB::select('DELETE FROM public."tbl_SalesOrderPayment" WHERE "SalesOrderMasterId" = ?::bigint', [$SalesOrder->orderMasterId]);

                foreach ($request->payments as $pay) {
                    $createdDate = getBranchNow($branchId);
                    $userId = isset($pay['userId']) && $pay['userId'] > 0 ? (int) $pay['userId'] : 1;
                    DB::select(
                        'SELECT insert_sales_order_payment(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $SalesOrder->orderMasterId,
                            (int) $pay['ledgerId'],
                            (float) $pay['amount'],
                            $pay['date'],
                            $pay['remarks'] ?? null,
                            $pay['extraDate'] ?? null,
                            $request->branchId,                        // INT
                            $userId,                          // INT
                            $request->ModifiedUser ?? 'admin',         // VARCHAR
                            $createdDate // TIMESTAMP
                        ]
                    );
                }
            }

            ActivityHelper::logActivity(
                $request->employeeId,
                'Sales Order Update',
                'Sales Order',
                'UPDATED',
                $request->branchId,
                $request->ModifiedUser ?? 'System',
                $SalesOrder->orderMasterId,
                $SalesOrder->voucherNo ?? null,
                $SalesOrder->date ?? null,
                $oldAmount,
                $request->totalAmount
            );


            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Order Updated Successfully'
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
     * Delete Sales Order
     */
    public function destroy($id)
    {
        $id = (int) $id; // ensure integer
        $SalesOrder = SalesOrder::find($id);
        if (!$SalesOrder) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Order Not Found',
            ], 404);
        }

        $oldAmount = $SalesOrder->totalAmount;
        DB::beginTransaction();
        try {
            DB::statement('SELECT public.delete_sales_order_details1_byid(?)', [$id]);

            DB::statement('DELETE FROM public."tbl_SalesOrderPayment" WHERE "SalesOrderMasterId" = ?', [$id]);

            DB::statement('SELECT public.delete_sales_order_master_byid(?)', [$id]);
            ActivityHelper::logActivity(
                $SalesOrder->employeeId ?? null,
                'Sales Order Delete',
                'Sales Order',
                'DELETED',
                $SalesOrder->branchId ?? null,
                $SalesOrder->ModifiedUser ?? 'System',
                $SalesOrder->orderMasterId,
                $SalesOrder->voucherNo ?? null,
                $SalesOrder->date ?? null,
                $oldAmount,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Order Deleted Successfully',
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


    public function SalesOrderQuotationList(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;
        $orderMasterId = $request->orderMasterId;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_sales_order_quotation_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $orderMasterId
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Order Quotation List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function SalesOrderProformaList(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;
        $in_proformaMasterId = $request->in_proformaMasterId;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_sales_order_proforma_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $in_proformaMasterId
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Order Proforma List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function SalesOrderquotationDetails(Request $request)
    {
        $quotationIds = (array) $request->In_quotationmasterId;

        if (empty($quotationIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Quotation ID required',
            ], 400);
        }

        $masters = DB::select(
            'SELECT * FROM public.get_sales_order_quotation_master_byid(?, ?)',
            [
                json_encode($quotationIds),
                $request->In_branchId
            ]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Quotation Not Found',
            ], 404);
        }

        // ✅ Use first as base structure
        $baseMaster = (array) $masters[0];

        // ✅ Merge master values
        $voucherNos = [];
        $quotationNos = [];
        $quotationMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'] ?? null;
            $quotationNos[] = $m['quotationNo'] ?? null;
            $quotationMasterIds[] = $m['quotationMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', array_filter($voucherNos));
        $baseMaster['quotationNo'] = implode(',', array_filter($quotationNos));
        $baseMaster['quotationMasterId'] = implode(',', $quotationMasterIds);

        // ✅ Fetch merged details
        $details = DB::select(
            'SELECT * FROM public.get_sales_order_quotation_details_with_remaining(?, ?, ?)',
            [
                json_encode($quotationIds),
                $request->In_orderMasterId,
                $request->In_branchId,
            ]
        );

        $branchId = $baseMaster['branchId'] ?? $request->In_branchId;

        $details = collect($details)->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'brand',
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
                'productname' => $product->productName ?? null,
                'brand'       => optional($product->brand)->brandName,
                'partno'      => $product->partNo ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'units'       => $units,
            ]);
        });

        $baseMaster['orderDetails'] = $details->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Quotation fetched successfully',
            'data' => $baseMaster
        ], 200);
    }

    public function SalesOrderproformaDetails(Request $request)
    {
        $proformaIds = (array) $request->in_proforma_master_id;

        if (empty($proformaIds)) {
            return response()->json([
                'status' => 400,
                'error' => true,
                'message' => 'Proforma ID required',
            ], 400);
        }

        $masters = DB::select(
            'SELECT * FROM public.get_sales_order_proforma_master_byid(?)',
            [json_encode($proformaIds)]
        );

        if (empty($masters)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Proforma Not Found',
            ], 404);
        }

        // ✅ Use first master as base
        $baseMaster = (array) $masters[0];

        // ✅ Merge master fields properly
        $voucherNos = [];
        $proformaNos = [];
        $proformaMasterIds = [];

        foreach ($masters as $m) {
            $m = (array) $m;
            $voucherNos[] = $m['voucherNo'] ?? null;
            $proformaNos[] = $m['proformaNo'] ?? null;
            $proformaMasterIds[] = $m['proformaMasterId'];
        }

        $baseMaster['voucherNo'] = implode(',', array_filter($voucherNos));
        $baseMaster['proformaNo'] = implode(',', array_filter($proformaNos));
        $baseMaster['proformaMasterId'] = implode(',', $proformaMasterIds);

        $allDetails = collect();

        foreach ($proformaIds as $id) {

            $details = DB::select(
                'SELECT * FROM public.get_sales_order_proforma_details_with_remaining(?, ?, ?)',
                [
                    $id,
                    $request->in_order_master_id,
                    $request->in_branch_id,
                ]
            );

            $allDetails = $allDetails->merge($details);
        }

        $branchId = $baseMaster['branchId'] ?? $request->in_branch_id;

        $allDetails = $allDetails->map(function ($detail) use ($branchId) {

            $detail = (array) $detail;

            $product = \App\Models\Product::with([
                'brand',
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
                'productname' => $product->productName ?? null,
                'brand'       => optional($product->brand)->brandName,
                'partno'      => $product->partNo ?? null,
                'mrp'         => $salesPriceAmount ?? null,
                'units'       => $units,
            ]);
        })->values();

        $baseMaster['orderDetails'] = $allDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Proforma fetched successfully',
            'data' => $baseMaster
        ], 200);
    }

    public function salesOrderVsProduct(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public."sales_order_vs_product_report_fill"(?,?,?,?,?,?,?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->groupId ?? null,
                    $request->productCode ?? 'All',
                    $request->branchId,
                    $request->mode ?? 'Detailed',
                    $request->brandId ?? null
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Sales Order Vs Product Report fetched successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function salesOrderStatus(Request $request)
    {
        DB::beginTransaction();

        try {

            $salesOrder = SalesOrder::find($request->orderMasterId);

            if (!$salesOrder) {
                return response()->json([
                    'status' => 404,
                    'error'  => true,
                    'message' => 'Sales Order Not Found',
                ], 404);
            }

            $salesOrder->status = $request->status;


            $salesOrder->save();

            DB::commit();

            return response()->json([
                'status' => 200,
                'error'  => false,
                'message' => 'Sales Order Status Updated Successfully',
                'data'   => $salesOrder,
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}