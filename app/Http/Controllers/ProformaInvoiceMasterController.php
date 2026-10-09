<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\ProformaInvoiceMaster;
use App\Models\ProformaInvoiceDetails1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProformaInvoiceMasterController extends Controller
{
    /**
     * List Proforma Invoice
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $proforma = DB::select('SELECT * FROM public.get_proforma_invoice_master_list(?,?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode,
            $request->customername ?? null
        ]);

        $collection = collect($proforma);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Proforma Invoice Retrieved Successfully',
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
     * Get Proforma Invoice by id
     */
    public function show($id)
    {
        $proformainvoices = DB::select('SELECT * FROM public.get_proforma_invoice_master_byid(?)', [$id]);

        if (empty($proformainvoices)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Proforma Invoice Not Found',
            ], 404);
        }

        $proformainvoices = (array) $proformainvoices[0];

        $proformainvoicesDetails = DB::select(
            'SELECT * FROM public.get_proforma_invoice_details_by_masterid(?)',
            [$proformainvoices['proformaMasterId']]
        );

        $proformainvoicesDetails = collect($proformainvoicesDetails)->map(function ($detail) use ($proformainvoices) {
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
                        'salesprice'       => $salesPrice->salesPrice ?? '',
                    ];
                }
            }

            // Get mrp from tbl_ProductSalesPrice
            $salesPriceAmount = DB::table('tbl_ProductSalesPrice')
                ->where('productCode', $detail['productCode'])
                ->where('unitId', $detail['unitId'])
                ->where('branchId', $proformainvoices['branchId'])
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

        $proformainvoices['invoiceDetails'] = $proformainvoicesDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Proforma Invoice Retrieved Successfully',
            'data' => $proformainvoices,
        ], 200);
    }
    /**
     * Create Proforma Invoice
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

            // Insert Proforma Invoice
            $masterParams = [
                $codeData['table_pk'],                 // proformaMasterId
                $codeData['suffixPrefixId'],           // suffixPrefixId
                $codeData['voucherNo'],                // voucherNo
                $codeData['voucherCode'],              // proformaNo
                $request->date,
                $request->dueDate,
                $request->cancelled,
                $request->ledgerId,
                $request->partyName,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->status,
                $request->taxType,
                // $request->quotationMasterId,
                is_array($request->quotationMasterId)
                    ? implode(',', array_unique($request->quotationMasterId))
                    : (string) $request->quotationMasterId,
                $request->AgainstNo,
                $request->pricingLevelId,
                $request->employeeId,
                $request->narration,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->costCentreId,
                $request->BatchId,
                $request->LPONO,
                $request->LPODate,
                $request->deliveryDate,
                $request->DeliveryTerms,
                $request->PaymentTerms,
                $request->lrNo,
                $request->transportCompany,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0),   // ✅ NEW

                $request->othercharge,
                $request->otherChargeLedgerId,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundOff,
                $request->totalAmount,
                $request->AdvancePerc,
                $request->branchId,
                $createdDate, // CreatedDate
                $request->CreatedUser,
                null, // ModifiedDate
                null, // ModifiedUser
            ];

            $sql = 'SELECT public.insert_proforma_invoice_master('
                . implode(',', array_fill(0, count($masterParams), '?'))
                . ') AS out_proformaMasterId';

            $result = DB::select($sql, $masterParams);
            $insertedId = $result[0]->out_proformamasterid ?? null;

            // dd($result);

            if (!$insertedId) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => 'Failed to Insert Proforma Invoice',
                ], 500);
            }

            // Get Eloquent instance
            $proforma = ProformaInvoiceMaster::find($insertedId);

            // Insert into tbl_proformaInvoiceDetails1
            if ($request->has('invoiceDetails')) {
                foreach ($request->invoiceDetails as $qd) {
                    $branchNow = getBranchNow($qd['branchId']);

                    $detailParams = [
                        (ProformaInvoiceDetails1::max('proformaDetails1Id') ?? 0) + 1,
                        $proforma->proformaMasterId,
                        !empty($request->quotationMasterId)
                            ? (int) $request->quotationMasterId
                            : null,
                        $qd['productCode'],
                        $qd['qty'],
                        $qd['freeQty'],
                        $qd['rate'],

                        $qd['inclusiveRate'] ?? 0,
                        $qd['lineDiscountWithTax'] ?? 0,

                        $qd['unitId'],
                        $qd['amount'],
                        $qd['quotationDetailsId'],
                        $qd['ConversionFactor'],
                        $qd['barcode'],
                        $qd['productDescription'],
                        $qd['discountPercentage'],
                        $qd['taxId'],
                        $qd['taxType'],
                        $qd['taxAmount'],
                        $qd['grossAmount'],
                        $qd['netAmount'],
                        $qd['PurchaseRate'],
                        $qd['billDiscOnProduct'],
                        $qd['AddCostonProduct'],
                        $qd['SlNo'],
                        $qd['Size'],
                        $qd['otherchargeonproduct'],
                        $qd['branchId'],
                        $branchNow,
                        $request->CreatedUser,
                        null,
                        null
                    ];

                    $sql = 'SELECT public.insert_proforma_invoice_details1('
                        . implode(',', array_fill(0, count($detailParams), '?'))
                        . ')';

                    DB::select($sql, $detailParams);
                }
            }
            $proforma = ProformaInvoiceMaster::find($insertedId);
            $details = ProformaInvoiceDetails1::where('proformaMasterId', $insertedId)->get();

            ActivityHelper::logActivity(
                $request->employeeId,               // userId
                'Proforma Invoice Insert',          // activityType
                'Proforma Invoice',                 // voucherType
                'INSERTED',                          // action
                $request->branchId,                 // branchId
                $request->CreatedUser ?? 'System', // createdUser
                $proforma->proformaMasterId,        // masterId
                $proforma->voucherNo ?? null,       // voucherNo
                $proforma->date ?? null,            // voucherDate
                null,                         // oldAmount
                $request->totalAmount               // newAmount
            );
            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Proforma Invoice Created Successfully',
                'data' => [
                    'proformaMasterId' => $proforma->proformaMasterId,
                    'proformaNo'       => $proforma->proformaNo,

                    'payload' => [
                        'proformaMaster' => [
                            ...$proforma->toArray(),
                            'invoiceDetails' => $details->toArray()
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
     * Update Proforma Invoice
     */
    public function update(Request $request, $id)
    {
        $proforma = ProformaInvoiceMaster::find($id);
        $oldAmount = $proforma->totalAmount;

        if (!$proforma) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Proforma Invoice Not Found',
            ], 404);
        }

        DB::beginTransaction();
        $branchId = $request->input('branchId', 1);
        $modifiedDate = getBranchNow($branchId);
        try {
            // Update Proforma Invoice
            $masterParams = [
                $id,
                $request->date,
                $request->dueDate,
                $request->cancelled,
                $request->ledgerId,
                $request->partyName,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyVatNo,
                $request->partyRefNo,
                $request->partyRefDate,
                $request->status,
                $request->taxType,
                // $request->quotationMasterId,
                is_array($request->quotationMasterId)
                    ? implode(',', array_unique($request->quotationMasterId))
                    : (string) $request->quotationMasterId,
                $request->AgainstNo,
                $request->pricingLevelId,
                $request->employeeId,
                $request->narration,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->costCentreId,
                $request->BatchId,
                $request->LPONO,
                $request->LPODate,
                $request->deliveryDate,
                $request->DeliveryTerms,
                $request->PaymentTerms,
                $request->lrNo,
                $request->transportCompany,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,

                $request->input('billDiscountWithTax', 0), // ✅ ADDED

                $request->othercharge,
                $request->otherChargeLedgerId,
                $request->taxableAmt,
                $request->totalTax,
                $request->roundOff,
                $request->totalAmount,
                $request->AdvancePerc,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser
            ];

            $sql = 'SELECT * FROM public.update_proforma_invoice_master(' .
                implode(',', array_fill(0, count($masterParams), '?')) .
                ')';

            $updatedId = DB::select($sql, $masterParams);
            $originalCreatedDate = $proforma->CreatedDate ?? $proforma->created_at;
            $originalCreatedUser = $proforma->CreatedUser ?? $proforma->created_user;

            // Insert into tbl_proformaInvoiceDetails1
            if ($request->has('invoiceDetails')) {
                DB::select('SELECT delete_proforma_invoice_details1_byid(?)', [$proforma->proformaMasterId]);

                foreach ($request->invoiceDetails as $qd) {
                    $branchNow = getBranchNow($qd['branchId']);
                    $detailParams = [
                        (ProformaInvoiceDetails1::max('proformaDetails1Id') ?? 0) + 1,
                        $proforma->proformaMasterId,
                        !empty($qd['quotationMasterId'])
                            ? (int) $qd['quotationMasterId']
                            : null,
                        $qd['productCode'],
                        $qd['qty'],
                        $qd['freeQty'],
                        $qd['rate'],

                        $qd['inclusiveRate'] ?? 0,          // ✅ ADDED
                        $qd['lineDiscountWithTax'] ?? 0,    // ✅ ADDED

                        $qd['unitId'],
                        $qd['amount'],
                        $qd['quotationDetailsId'],
                        $qd['ConversionFactor'],
                        $qd['barcode'],
                        $qd['productDescription'],
                        $qd['discountPercentage'],
                        $qd['taxId'],
                        $qd['taxType'],
                        $qd['taxAmount'],
                        $qd['grossAmount'],
                        $qd['netAmount'],
                        $qd['PurchaseRate'],
                        $qd['billDiscOnProduct'],
                        $qd['AddCostonProduct'],
                        $qd['SlNo'],
                        $qd['Size'],
                        $qd['otherchargeonproduct'],
                        $qd['branchId'],
                        $originalCreatedDate, // CreatedDate
                        $originalCreatedUser, // CreatedUser
                        $branchNow,
                        $request->ModifiedUser
                    ];

                    $sql = 'SELECT public.insert_proforma_invoice_details1(' .
                        implode(',', array_fill(0, count($detailParams), '?')) .
                        ')';

                    DB::select($sql, $detailParams);
                }
            }
            ActivityHelper::logActivity(
                $request->employeeId,               // userId
                'Proforma Invoice Update',          // activityType
                'Proforma Invoice',                 // voucherType
                'UPDATED',                          // action
                $request->branchId,                 // branchId
                $request->ModifiedUser ?? 'System', // createdUser
                $proforma->proformaMasterId,        // masterId
                $proforma->voucherNo ?? null,       // voucherNo
                $proforma->date ?? null,            // voucherDate
                $oldAmount,                         // oldAmount
                $request->totalAmount               // newAmount
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Proforma Invoice Updated Successfully'
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
     * Delete Proforma Invoice
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        $proforma = ProformaInvoiceMaster::find($id);
        if (!$proforma) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Proforma Invoice Not Found'
            ], 404);
        }
        $oldAmount = $proforma->totalAmount;

        try {
            DB::statement('SELECT delete_proforma_invoice_details1_byid(?)', [$id]);

            DB::statement('SELECT public.delete_proforma_invoice_master_byid(?)', [$id]);
            ActivityHelper::logActivity(
                $proforma->employeeId ?? null,
                'Proforma Invoice Delete',
                'Proforma Invoice',
                'DELETED',
                $proforma->branchId ?? null,
                $proforma->ModifiedUser ?? 'System',
                $proforma->proformaMasterId,
                $proforma->voucherNo ?? null,
                $proforma->date ?? null,
                $oldAmount,
                0
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Proforma Invoice Deleted Successfully',
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

    public function getQuotationByDate(Request $request)
    {
        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');

        if (!$fromDate || !$toDate) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide fromDate and toDate'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_quotation_no_by_date(?, ?)', [
            $fromDate,
            $toDate
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Quotation Numbers Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function getQuotationDetails($id)
    {
        $quotation = DB::select('SELECT * FROM public.get_quotation_master_data_byid(?)', [$id]);

        // Check if found
        if (empty($quotation)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Quotation Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $quotation = (array) $quotation[0];

        // Step 2: Get invoice details using quotationMasterId
        $quotationDetails = DB::select('SELECT * FROM public.get_quotation_details_by_quotation_master_id(?)', [$quotation['quotationMasterId']]);

        // Step 3: Attach invoice details to master data
        $quotation['invoiceDetails'] = $quotationDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Quotation Retrieved Successfully',
            'data' => $quotation,
        ], 200);
    }

    public function proformaInvoiceReport(Request $request)
    {
        try {

            $data = DB::select(
                '
                SELECT *
                FROM public."proforma_invoice_report_fill_detailed"(
                    ?::timestamp,
                    ?::timestamp,
                    ?::boolean,
                    ?::varchar,
                    ?::timestamp,
                    ?::varchar,
                    ?::integer,
                    ?::bigint,
                    ?::varchar,
                    ?::varchar,
                    ?::varchar
                )
                ',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->optional ?? false,
                    $request->condition ?? 'All',
                    $request->dueOn ?? now(),
                    $request->ledgerId ?? 'All',
                    $request->branchId,
                    $request->currencyId,
                    $request->userId ?? 'All',
                    $request->costCentreId ?? 'All',
                    $request->mode ?? 'Detailed'
                ]
            );

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $data,
                'message' => 'proforma Invoice Report Fetched Successfully'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getQuotationForProforma(Request $request)
    {
        try {

            $quotationIds = $request->quotationMasterId;

            if (!$quotationIds) {
                return response()->json([
                    'status' => 400,
                    'error' => true,
                    'message' => 'quotationMasterId is required',
                    'data' => null
                ], 400);
            }

            // ✅ Ensure array format
            $quotationIds = is_array($quotationIds)
                ? $quotationIds
                : [$quotationIds];

            // ✅ Convert to PostgreSQL array format
            $pgArray = '{' . implode(',', array_map('intval', $quotationIds)) . '}';

            $data = DB::select(
                'SELECT * FROM public.get_quotation_details_data_for_proforma_byid(?::bigint[])',
                [$pgArray]
            );

            if (empty($data)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Quotation not found',
                    'data' => null
                ], 404);
            }

            $master = (array) $data[0];

            $detailKeys = [
                'quotationDetailsId',
                'productCode',
                'qty',
                'freeQty',
                'rate',
                'inclusiveRate',
                'lineDiscountWithTax',
                'unitId',
                'amount',
                'discountPercentage',
                'taxId',
                'taxType',
                'taxAmount',
                'grossAmount',
                'netAmount',
                'PurchaseRate',
                'billDiscOnProduct',
                'AddCostonProduct',
                'SlNo',
                'otherchargeonproduct',
                'branchId',
                'CreatedDate',
                'CreatedUser',
                'ModifiedDate',
                'ModifiedUser',
                'ConversionFactor',
                'barcode',
                'productDescription',
                'proformaQuotationMasterId'
            ];

            $invoiceDetails = [];

            foreach ($data as $row) {

                $row = (array) $row;
                $detail = array_intersect_key($row, array_flip($detailKeys));

                $product = \App\Models\Product::with([
                    'unitConversions.unit',
                    'salesPrices.unit',
                    'salesPrices.pricingLevel'
                ])->find($row['productCode']);

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

                $detail['productname'] = $product->productName ?? null;
                $detail['brand']       = $product->brand ?? null;
                $detail['partno']      = $product->partNo ?? null;
                $detail['units']       = $units;

                $invoiceDetails[] = $detail;
            }

            $masterData = [
                'quotationMasterId'      => $master['quotationMasterId'],
                'voucherNo'               => $master['voucherNo'],
                'quotationNo'              => $master['quotationNo'],
                'suffixPrefixId'           => $master['suffixPrefixId'],
                'employeeId'               => $master['employeeId'],
                'pricingLevelId'           => $master['pricingLevelId'],
                'date'                     => $master['date'],
                'currencyConversionId'     => $master['currencyConversionId'],
                'exchangeRate'             => $master['exchangeRate'],
                'exchangeDate'             => $master['exchangeDate'],
                'BatchId'                  => $master['BatchId'],
                'ledgerId'                 => $master['ledgerId'],
                'customerName'             => $master['customerName'],
                'CustomerAddress'          => $master['CustomerAddress'],
                'CustomerPhone'            => $master['CustomerPhone'],
                'CustomerVatNo'            => $master['CustomerVatNo'],
                'creditPeriod'             => $master['creditPeriod'],
                'costCentreId'             => $master['costCentreId'],
                'narration'                => $master['narration'],
                'partyRefNo'               => $master['partyRefNo'],
                'partyRefDate'             => $master['partyRefDate'],
                'masterTaxType'            => $master['masterTaxType'],
                'totalAmount'              => $master['totalAmount'],
                'billDiscount'             => $master['billDiscount'],
                'additionalCost'           => $master['additionalCost'],
                'otherChargeLedgerId'      => $master['otherChargeLedgerId'],
                'othercharge'              => $master['othercharge'],
                'taxableAmt'               => $master['taxableAmt'],
                'totalTax'                 => $master['totalTax'],
                'roundOff'                 => $master['roundOff'],
                'grandTotal'               => $master['grandTotal'],
                'billDiscOnProductStatus'  => $master['billDiscOnProductStatus'],
                'AddCostonProductStatus'   => $master['AddCostonProductStatus'],
                'approved'                 => $master['approved'],
                'contactno'                => $master['contactno'],
                'contactperson'            => $master['contactperson'],
                'deliveredwithin'          => $master['deliveredwithin'],
                'deliverysite'             => $master['deliverysite'],
                'paymentterms'             => $master['paymentterms'],
                'quatationvalidity'        => $master['quatationvalidity'],
                'DeliveryTerms'            => $master['DeliveryTerms'],
                'Certificate'              => $master['Certificate'],
                'IncoTerms'                => $master['IncoTerms'],
                'Email'                    => $master['Email'],
                'CarMake'                  => $master['CarMake'],
                'CarModel'                 => $master['CarModel'],
                'Kilometer'                => $master['Kilometer'],
                'NextService'              => $master['NextService'],
                'VehicleInTime'            => $master['VehicleInTime'],
                'VehicleOutTime'           => $master['VehicleOutTime'],
                'branchId'                 => $master['QuotationBranchId'],
                'CreatedDate'              => $master['QuotationCreatedDate'],
                'CreatedUser'              => $master['QuotationCreatedUser'],
                'ModifiedDate'             => $master['QuotationModifiedDate'],
                'ModifiedUser'             => $master['QuotationModifiedUser'],
                'billDiscountWithTax'      => $master['billDiscountWithTax'],
                'ExecutiveId'              => $master['ExecutiveId'],
                'LedgerName'               => $master['LedgerName'],
                'PricingLevelName'         => $master['PricingLevelName'],
                'employeeName'             => $master['employeeName'],

                'invoiceDetails'           => $invoiceDetails
            ];
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Quotation fetched successfully',
                'data'    => $masterData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}