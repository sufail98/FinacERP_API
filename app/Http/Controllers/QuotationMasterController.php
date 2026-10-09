<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\QuotationDetails;
use App\Models\QuotationMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationMasterController extends Controller
{
    /**
     * List Sales quotation
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $quotation = DB::select(
            'SELECT * FROM public.get_quotation_master_list(?,?,?,?,?,?,?)',
            [
                $request->fromDate,
                $request->toDate,
                $limit,
                $page,
                $request->branchId,
                $request->vouchercode,
                $request->customername
            ]
        );

        // ✅ Get total count from window column
        $total = count($quotation) > 0
            ? (int) $quotation[0]->totalCount
            : 0;

        // ✅ Remove helper column before returning
        $data = array_map(function ($row) {
            unset($row->totalCount);
            return $row;
        }, $quotation);

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Sales Quotation Retrieved Successfully',
            'data'    => $data,
            'meta'    => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ]);
    }

    /**
     * Get Sales quotation by id
     */
    // public function show($id)
    // {
    //     $salesQuotation = DB::select('SELECT * FROM public.get_quotation_master_data_byid(?)', [$id]);

    //     if (empty($salesQuotation)) {
    //         return response()->json([
    //             'status' => 404,
    //             'error' => true,
    //             'message' => 'Sales Quotation Not Found',
    //         ], 404);
    //     }

    //     $salesQuotation = (array) $salesQuotation[0];

    //     $salesQuotationDetails = DB::table('tbl_QuotationDetails as d')
    //         ->where('d.quotationMasterId', $salesQuotation['quotationMasterId'])
    //         ->get()
    //         ->map(function ($detail) {
    //             $product = \App\Models\Product::with('salesPrices')->find($detail->productCode);

    //             $detail->salesPrices = $product ? $product->salesPrices->toArray() : [];

    //             return $detail;
    //         });

    //     $salesQuotation['quotationDetails'] = $salesQuotationDetails;

    //     return response()->json([
    //         'status' => 200,
    //         'error' => false,
    //         'message' => 'Sales Quotation Retrieved Successfully',
    //         'data' => $salesQuotation,
    //     ]);
    // }
    public function show(Request $request, $id)
    {
        $salesQuotation = DB::select(
            'SELECT * FROM public.get_quotation_master_data_byid(?)',
            [$id]
        );

        if (empty($salesQuotation)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Quotation Not Found',
            ], 404);
        }

        $salesQuotation = (array) $salesQuotation[0];

        $salesQuotationDetails = DB::table('tbl_QuotationDetails as d')

            ->leftJoin('tbl_ProductSalesPrice as sp', function ($join) use ($salesQuotation) {
                $join->on('sp.productCode', '=', 'd.productCode')
                    ->on('sp.unitId', '=', 'd.unitId')
                    ->where('sp.branchId', '=', $salesQuotation['branchId']);
            })

            // ✅ NEW JOIN
            ->leftJoin('tbl_Product as p', 'p.productCode', '=', 'd.productCode')

            ->where('d.quotationMasterId', $salesQuotation['quotationMasterId'])
            ->orderBy('d.SlNo', 'asc')
            ->select(
                'd.quotationDetailsId',
                'd.quotationMasterId',
                'd.productCode',
                'd.unitId',
                'd.taxId',
                'd.qty',
                'd.freeQty',
                'd.rate',
                'd.amount',
                'd.discountPercentage',
                'd.discountAmount',
                'd.taxAmount',
                'd.grossAmount',
                'd.taxType',
                'd.netAmount',
                'd.ConversionFactor',
                'd.barcode',
                'd.billDiscOnProduct',
                'd.AddCostonProduct',
                'd.productDescription',
                'd.SlNo',
                'd.PurchaseRate',
                'd.otherchargeonproduct',
                'd.branchId',
                'd.CreatedDate',
                'd.CreatedUser',
                'd.ModifiedDate',
                'd.ModifiedUser',
                'p.productNameArb',

                DB::raw('d.inclusiverate AS "inclusiveRate"'),
                DB::raw('d.linediscountwithtax AS "lineDiscountWithTax"'),

                'sp.amount as salesPriceAmount',

                // ✅ PRODUCT FIELD
                'p.productName'
            )

            ->get();

        $productCodes = $salesQuotationDetails
            ->pluck('productCode')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $products = collect();
        if (!empty($productCodes)) {
            $products = \App\Models\Product::with([
                'unitConversions.unit',
                'salesPrices.unit',
                'salesPrices.pricingLevel'
            ])
                ->whereIn('productCode', $productCodes)
                ->get()
                ->keyBy('productCode');
        }

        $salesQuotationDetails = $salesQuotationDetails->map(function ($detail) use ($products) {
            $detail = (array) $detail;

            $product = $products[$detail['productCode']] ?? null;

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

            return array_merge($detail, [
                'productcode' => $detail['productCode'],
                'productname' => $product->productName ?? null,
                'brand'       => $product->brand ?? null,
                'mrp'         => $detail['salesPriceAmount'] ?? null,
                'partno'      => $product->partNo ?? null,
                'units'       => $units,
            ]);
        });

        $salesQuotation['quotationDetails'] = $salesQuotationDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Sales Quotation Retrieved Successfully',
            'data' => $salesQuotation,
        ]);
    }
    /**
     * Create Sales Quotation
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);

            // ---------- Quotation Master (added billDiscountWithTax) ----------
            $createdDate = getBranchNow($branchId);

            $masterParams = [
                $codeData['table_pk'],              // quotationMasterId
                $codeData['voucherNo'],             // voucherNo
                $codeData['voucherCode'],           // quotationNo
                $codeData['suffixPrefixId'],        // suffixPrefixId
                $request->employeeId,               // employeeId
                $request->input('executiveId', null),
                $request->pricingLevelId,           // pricingLevelId
                $request->date,                     // date
                $request->currencyConversionId,     // currencyConversionId
                $request->exchangeRate,             // exchangeRate
                $request->exchangeDate,             // exchangeDate
                $request->BatchId,                  // BatchId
                $request->ledgerId,                 // ledgerId
                $request->customerName,             // customerName
                $request->CustomerAddress,          // CustomerAddress
                $request->CustomerPhone,            // CustomerPhone
                $request->CustomerVatNo,            // CustomerVatNo
                $request->creditPeriod,             // creditPeriod
                $request->costCentreId,             // costCentreId
                $request->narration,                // narration
                $request->partyRefNo,               // partyRefNo
                $request->partyRefDate,             // partyRefDate
                $request->taxType,                  // taxType
                $request->totalAmount,              // totalAmount
                $request->billDiscount,             // billDiscount

                $request->input('billDiscountWithTax', 0), // ✅ NEW

                $request->additionalCost,           // additionalCost
                $request->otherChargeLedgerId,      // otherChargeLedgerId
                $request->othercharge,              // othercharge
                $request->taxableAmt,               // taxableAmt
                $request->totalTax,                 // totalTax
                $request->roundOff,                 // roundOff
                $request->grandTotal,               // grandTotal
                $request->billDiscOnProductStatus,  // billDiscOnProductStatus
                $request->AddCostonProductStatus,   // AddCostonProductStatus
                $request->approved,                 // approved
                $request->contactno,                // contactno
                $request->contactperson,            // contactperson
                $request->deliveredwithin,          // deliveredwithin
                $request->deliverysite,             // deliverysite
                $request->paymentterms,             // paymentterms
                $request->quatationvalidity,        // quatationvalidity
                $request->DeliveryTerms,            // DeliveryTerms
                $request->Certificate,              // Certificate
                $request->IncoTerms,                // IncoTerms
                $request->Email,                    // Email
                $request->CarMake,                  // CarMake
                $request->CarModel,                 // CarModel
                $request->Kilometer,                // Kilometer
                $request->NextService,              // NextService
                $request->VehicleInTime,            // VehicleInTime
                $request->VehicleOutTime,           // VehicleOutTime
                $request->branchId,                 // branchId
                $request->CreatedUser,              // CreatedUser
                $createdDate
            ];

            $sql = 'SELECT public.insert_quotation_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ') AS out_quotationMasterId';

            $result = DB::select($sql, $masterParams);

            $inserted = $result[0] ?? null;
            if (!$inserted || $inserted->out_quotationmasterid === null) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => 'Failed to insert quotation master',
                ], 500);
            }

            $quotation = QuotationMaster::find($inserted->out_quotationmasterid);

            // ---------- Quotation Details (added inclusiveRate + lineDiscountWithTax) ----------
            if ($request->has('quotationDetails')) {
                foreach ($request->quotationDetails as $qd) {
                    $branchNow = getBranchNow($qd['branchId']);

                    $detailParams = [
                        $quotation->quotationMasterId,
                        $qd['productCode'],
                        $qd['unitId'],
                        $qd['taxId'],
                        $qd['qty'],
                        $qd['freeQty'],
                        $qd['rate'],

                        $qd['inclusiveRate'] ?? 0,          // ✅ NEW
                        $qd['lineDiscountWithTax'] ?? 0,    // ✅ NEW

                        $qd['amount'],
                        $qd['discountPercentage'],
                        $qd['discountAmount'],
                        $qd['taxAmount'],
                        $qd['grossAmount'],
                        $qd['taxType'],
                        $qd['netAmount'],
                        $qd['ConversionFactor'],
                        $qd['barcode'],
                        $qd['billDiscOnProduct'],
                        $qd['AddCostonProduct'],
                        $qd['productDescription'],
                        $qd['SlNo'],
                        $qd['PurchaseRate'],
                        $qd['otherchargeonproduct'],
                        $qd['branchId'],
                        $request->CreatedUser,
                        $branchNow,
                        null
                    ];

                    $sql = 'SELECT public.insert_quotation_details(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ') AS quotationDetailsId';

                    DB::select($sql, $detailParams);
                }
            }

            ActivityHelper::logActivity(
                $request->employeeId,                  // userId
                'QuotationInsert',                     // activityType
                'Sales Quotation',                           // voucherType
                'INSERTED',                             // action
                $request->branchId,                    // branchId
                $request->CreatedUser ?? 'System',     // createdUser
                $quotation->quotationMasterId,         // masterId
                $quotation->voucherNo ?? null,         // voucherNo
                $quotation->date ?? null,              // voucherDate
                $request->totalAmount,                             // oldAmount
                $request->grandTotal                    // newAmount
            );

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Sales Quotation Created Successfully',
                'data' => [
                    'quotationMasterId' => $quotation->quotationMasterId,
                    'quotationNo'       => $quotation->quotationNo,

                    'payload' => [
                        'quotationMaster' => [
                            ...$quotation->toArray(),
                            'quotationDetails' => $request->quotationDetails ?? []
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
     * Update Sales Quotation
     */
    public function update(Request $request, $id)
    {
        $quotation = QuotationMaster::find($id);

        if (!$quotation) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Quotation Not Found',
            ], 404);
        }
        $oldAmount = $quotation->grandTotal;
        DB::beginTransaction();
        try {
            $branchId = $request->input('branchId', 1);
            $modifiedDate = getBranchNow($branchId);
            // Update sales fields
            $masterParams = [
                $id,
                $request->employeeId,
                // $request->input('executiveId', null),
                $request->pricingLevelId,
                $request->date,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->BatchId,
                $request->ledgerId,
                $request->customerName,
                $request->CustomerAddress,
                $request->CustomerPhone,
                $request->CustomerVatNo,
                $request->creditPeriod,
                $request->costCentreId,
                $request->narration,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->taxType,
                $request->totalAmount,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0), // ✅ ADDED

                $request->additionalCost,
                $request->otherChargeLedgerId,
                $request->othercharge,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundOff,
                $request->grandTotal,
                $request->billDiscOnProductStatus,
                $request->AddCostonProductStatus,
                $request->approved,
                $request->contactno,
                $request->contactperson,
                $request->deliveredwithin,
                $request->deliverysite,
                $request->paymentterms,
                $request->quatationvalidity,
                $request->DeliveryTerms,
                $request->Certificate,
                $request->IncoTerms,
                $request->Email,
                $request->CarMake,
                $request->CarModel,
                $request->Kilometer,
                $request->NextService,
                $request->VehicleInTime,
                $request->VehicleOutTime,
                $request->branchId,
                $request->ModifiedUser,
                $modifiedDate
            ];

            $sql = 'SELECT * FROM public.update_quotation_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ')';

            $result = DB::select($sql, $masterParams);
            $originalCreatedDate = $quotation->CreatedDate ?? $quotation->created_at;
            $originalCreatedUser = $quotation->CreatedUser ?? $quotation->created_user;
            /**
             * Update quotationDetails
             */

            // Insert into tbl_QuotationDetails
            if ($request->has('quotationDetails')) {
                DB::select('SELECT delete_quotation_details(?)', [$quotation->quotationMasterId]);
                foreach ($request->quotationDetails as $qd) {
                    $branchNow = getBranchNow($qd['branchId']);

                    $detailParams = [
                        $quotation->quotationMasterId,
                        $qd['productCode'],
                        $qd['unitId'],
                        $qd['taxId'],
                        $qd['qty'],
                        $qd['freeQty'],
                        $qd['rate'],

                        $qd['inclusiveRate'] ?? 0,          // ✅ ADDED
                        $qd['lineDiscountWithTax'] ?? 0,    // ✅ ADDED

                        $qd['amount'],
                        $qd['discountPercentage'],
                        $qd['discountAmount'],
                        $qd['taxAmount'],
                        $qd['grossAmount'],
                        $qd['taxType'],
                        $qd['netAmount'],
                        $qd['ConversionFactor'],
                        $qd['barcode'],
                        $qd['billDiscOnProduct'],
                        $qd['AddCostonProduct'],
                        $qd['productDescription'],
                        $qd['SlNo'],
                        $qd['PurchaseRate'],
                        $qd['otherchargeonproduct'],
                        $qd['branchId'],
                        $originalCreatedUser,
                        $originalCreatedDate,
                        // $request->CreatedUser,
                        $branchNow,
                        $request->ModifiedUser ?? $request->CreatedUser   // ✅ ADDED

                    ];

                    $sql = 'SELECT public.insert_quotation_details(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ') AS quotationDetailsId';

                    DB::select($sql, $detailParams);
                }
            }
            ActivityHelper::logActivity(
                $request->employeeId,                  // userId
                'QuotationUpdate',                     // activityType
                'Sales Quotation',                           // voucherType
                'UPDATED',                             // action
                $request->branchId,                    // branchId
                $request->CreatedUser ?? 'System',     // createdUser
                $quotation->quotationMasterId,         // masterId
                $quotation->voucherNo ?? null,         // voucherNo
                $quotation->date ?? null,              // voucherDate
                $oldAmount,                             // oldAmount
                $request->grandTotal                    // newAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Quotation Updated Successfully'
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
     * Delete Sales Quotation
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $quotation = DB::table('tbl_QuotationMaster')->where('quotationMasterId', $id)->first();

            if (!$quotation) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Sales Quotation Not Found',
                ], 404);
            }

            $userId = (int) ($quotation->employeeId ?? 0);
            $branchId = (int) ($quotation->branchId ?? 0);
            $createdUser = $quotation->CreatedUser ?? 'System';
            $masterId = (int) $quotation->quotationMasterId;
            $voucherNo = $quotation->voucherNo ?? null;

            $voucherDate = $quotation->date
                ? date('Y-m-d H:i:s', strtotime($quotation->date))
                : null;

            $oldAmount = isset($quotation->grandTotal) ? (float) $quotation->grandTotal : 0;

            DB::statement('SELECT public.delete_quotation_details(?)', [$id]);
            DB::statement('SELECT public.delete_quotation_master_byid(?)', [$id]);

            ActivityHelper::logActivity(
                $userId,             // int
                'QuotationDelete',   // activityType
                'Sales Quotation',   // voucherType
                'DELETED',           // action
                $branchId,           // int
                $createdUser,        // string
                $masterId,           // int
                $voucherNo,          // string
                $voucherDate,        // timestamp (10th parameter)
                $oldAmount,          // numeric (11th parameter)
                $oldAmount           // numeric (12th parameter)
            );


            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Quotation Deleted Successfully',
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





    public function quotationReport(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public."quotation_report_fill_detailed"(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->condition ?? null,
                    $request->ledgerId ?? 'All',
                    $request->branchId,
                    $request->salesManId ?? null,
                    $request->currencyId,
                    $request->userId,
                    $request->costCentreId ?? 'All',
                    $request->partyName ?? 'All',
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'data'    => $data,
                'message' => 'Quotation Report Fetched Successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function quotationReportSummary(Request $request)
    {
        try {
            $data = DB::select(
                'SELECT * FROM public."quotation_report_fill"(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->condition ?: null,
                    $request->ledgerId ?: null,
                    $request->branchId,
                    $request->salesManId ?: null,
                    $request->currencyId,
                    $request->userId,
                    $request->costCentreId ?: null,
                    $request->partyName ?: null
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'count'   => count($data),
                'data'    => $data,
                'message' => 'Quotation Report Summary Fetched Successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function approve($id)
    {
        $quotation = QuotationMaster::find($id);

        if (!$quotation) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Sales Quotation Not Found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Update sales fields
            $result = DB::select('SELECT * FROM public.approve_sales_quotation(?)', [$id]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Sales Quotation Approved Successfully'
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