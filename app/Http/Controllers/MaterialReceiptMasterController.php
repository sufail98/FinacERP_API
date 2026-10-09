<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\MaterialReceiptMaster;
use App\Models\MaterialReceiptDetails1;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialReceiptMasterController extends Controller
{
    /**
     * List Material Receipt
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $material = DB::select('SELECT * FROM public.get_material_receipt_master_list(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($material);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Material Receipt Retrieved Successfully',
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
     * Get Material Receipt by id
     */
    public function show($id)
    {
        $material = DB::select('SELECT * FROM public.get_material_receipt_master_byid(?)', [$id]);

        if (empty($material)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Material Receipt Not Found',
            ], 404);
        }

        $material = (array) $material[0];

        $materialDetails = DB::select('SELECT * FROM public.get_material_receipt_details_by_masterid(?)', [$material['receiptMasterId']]);
        $materialDetails = array_map(function ($item) {
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
        }, $materialDetails);
        $material['materialDetails'] = $materialDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Material Receipt Retrieved Successfully',
            'data' => $material,
        ], 200);
    }
    /**
     * Create Material Receipt
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

            // Insert Material Receipt
            $result = DB::select(
                'SELECT insert_material_receipt_master(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) AS out_receiptMasterId',
                [
                    $codeData['table_pk'], // receiptMasterId
                    $codeData['suffixPrefixId'], // suffixPrefixId
                    $codeData['voucherNo'], // voucherNo
                    $codeData['voucherCode'], // receieptNo
                    $request->date,
                    $request->ledgerId,
                    $request->orderMasterId,
                    $request->AgainstNo,
                    $request->BatchId,
                    $request->narration,
                    $request->currencyConversionId,
                    $request->exchangeRate,
                    $request->exchangeDate,
                    $request->costCentreId,
                    $request->transportCompany,
                    $request->taxType,
                    $request->subTotal,
                    $request->additionalCost,
                    $request->billDiscount,
                    $request->othercharge,
                    $request->OtherChargeLedgerId,
                    $request->roundoff,
                    $request->taxableAmt,
                    $request->totalTax,
                    $request->totalAmount,
                    $request->partyAddress,
                    $request->partyMobile,
                    $request->partyName,
                    $request->partyVatNo,
                    $request->partyRefDate,
                    $request->partyRefNo,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->branchId,
                    $createdDate,
                    $request->CreatedUser,
                    NULL, // ModifiedDate
                    NULL // ModifiedUser
                ]
            );

            // dd($result);
            $insertedId = $result[0]->out_receiptmasterid ?? null;

            if (!$insertedId) {
                return response()->json([
                    'status' => 500,
                    'error' => true,
                    'message' => 'Failed to Insert Material Receipt',
                ], 500);
            }

            // Get Eloquent instance
            $material = MaterialReceiptMaster::find($insertedId);

            // Insert into materialDetails
            if ($request->has('materialDetails')) {
                foreach ($request->materialDetails as $qd) {

                    $result = DB::select(
                        'SELECT insert_material_receipt_details1(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) AS id',
                        [
                            (MaterialReceiptDetails1::max('receiptDetails1Id') ?? 0) + 1,
                            $material->receiptMasterId,
                            $qd['productCode'],
                            $qd['orderDetails1Id'],
                            $qd['qty'],
                            $qd['freeQty'],
                            $qd['rate'],
                            $qd['unitId'],
                            $qd['amount'],
                            $qd['ConversionFactor'],
                            $qd['barcode'],
                            $qd['discountPercentage'],
                            $qd['taxId'],
                            $qd['taxType'],
                            $qd['taxAmount'],
                            $qd['grossAmount'],
                            $qd['netAmount'],
                            $qd['billDiscOnProduct'],
                            $qd['AddCostonProduct'],
                            $qd['productDescription'],
                            $qd['otherchargeOnProduct'],
                            $qd['GodownId'],
                            $qd['RackId'],
                            $request->branchId,
                            $createdDate,
                            $request->CreatedUser,
                            NULL,                // ModifiedDate
                            NULL                 // ModifiedUser
                        ]
                    );
                }
            }
            $master = MaterialReceiptMaster::find($insertedId);

            $details = MaterialReceiptDetails1::where(
                'receiptMasterId',
                $insertedId
            )->get();

            ActivityHelper::logActivity(
                $request->employeeId,               // userId
                'material Invoice Insert',          // activityType
                'material Invoice',                 // voucherType
                'INSERTED',                          // action
                $request->branchId,                 // branchId
                $request->CreatedUser ?? 'System', // createdUser
                $material->receiptMasterId,        // masterId
                $material->voucherNo ?? null,       // voucherNo
                $request->date,            // voucherDate
                null,                         // oldAmount
                $request->totalAmount               // newAmount
            );
            DB::commit();

            return response()->json([
                'status' => 201,
                'error'  => false,
                'message' => 'Material Receipt Created Successfully',
                'data' => [
                    'receiptMasterId' => $master->receiptMasterId,
                    'receiptNo'       => $master->receiptNo,

                    'payload' => [
                        'materialReceiptMaster' => [
                            ...$master->toArray(),
                            'materialDetails' => $details->toArray()
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
     * Update Material Receipt
     */
    public function update(Request $request, $id)
    {
        $material = MaterialReceiptMaster::find($id);

        if (!$material) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Material Receipt Not Found',
            ], 404);
        }
        $oldValue = $material->totalAmount;
        $branchId = $request->input('branchId', 1);
        $modifiedDate = getBranchNow($branchId);

        DB::beginTransaction();
        try {
            // Update Material Receipt
            $result = DB::select('SELECT update_material_receipt_master(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) AS updated', [
                $id,
                $request->date,
                $request->ledgerId,
                $request->orderMasterId,
                $request->AgainstNo,
                $request->BatchId,
                $request->narration,
                $request->currencyConversionId,
                $request->exchangeRate,
                $request->exchangeDate,
                $request->costCentreId,
                $request->transportCompany,
                $request->taxType,
                $request->subTotal,
                $request->additionalCost,
                $request->billDiscount,
                $request->othercharge,
                $request->OtherChargeLedgerId,
                $request->roundoff,
                $request->taxableAmt,
                $request->totalTax,
                $request->totalAmount,
                $request->partyAddress,
                $request->partyMobile,
                $request->partyName,
                $request->partyVatNo,
                $request->partyRefDate,
                $request->partyRefNo,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $modifiedDate,
                $request->ModifiedUser
            ]);

            // Insert into materialDetails
            if ($request->has('materialDetails')) {
                DB::select('SELECT delete_material_receipt_details1_byid(?)', [$material->receiptMasterId]);

                foreach ($request->materialDetails as $qd) {
                    $result = DB::select(
                        'SELECT insert_material_receipt_details1(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) AS id',
                        [
                            (MaterialReceiptDetails1::max('receiptDetails1Id') ?? 0) + 1,
                            $material->receiptMasterId,
                            $qd['productCode'],
                            $qd['orderDetails1Id'],
                            $qd['qty'],
                            $qd['freeQty'],
                            $qd['rate'],
                            $qd['unitId'],
                            $qd['amount'],
                            $qd['ConversionFactor'],
                            $qd['barcode'],
                            $qd['discountPercentage'],
                            $qd['taxId'],
                            $qd['taxType'],
                            $qd['taxAmount'],
                            $qd['grossAmount'],
                            $qd['netAmount'],
                            $qd['billDiscOnProduct'],
                            $qd['AddCostonProduct'],
                            $qd['productDescription'],
                            $qd['otherchargeOnProduct'],
                            $qd['GodownId'],
                            $qd['RackId'],
                            $request->branchId,
                            NULL, // CreatedDate
                            NULL, // CreatedUser
                            $modifiedDate,                // ModifiedDate
                            $request->ModifiedUser                 // ModifiedUser
                        ]
                    );
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,           // User ID
                'Material Receipt Update',    // Action
                'Material Receipt',           // Module
                'UPDATED',                    // Type
                $request->branchId,           // Branch ID
                $request->ModifiedUser,       // Username or user making change
                $material->receiptMasterId,   // Master ID
                $material->voucherNo ?? null, // Voucher No
                $request->date,               // Date of update
                $oldValue,                    // Old totalAmount
                $request->totalAmount         // New totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Material Receipt Updated Successfully'
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
     * Delete Material Receipt
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $material = MaterialReceiptMaster::find($id);
            if (!$material) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Material Receipt Not Found',
                ], 404);
            }
            $oldValue = $material->totalAmount;

            DB::select('SELECT delete_material_receipt_details1_byid(?)', [$id]);

            DB::statement('SELECT public.delete_material_receipt_master_byid(?)', [$id]);
            // dd(auth()->id());
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Material Receipt Delete',
                'Material Receipt',
                'DELETED',
                $material->branchId,
                $material->ModifiedUser ?? 'System',
                $material->receiptMasterId,
                $material->voucherNo ?? null,
                $material->date ?? now(),
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Material Receipt Deleted Successfully',
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
    public function materialReceiptReport(Request $request)
    {
        try {
            $fromDate          = $request->fromDate;
            $toDate            = $request->toDate;
            $condition         = $request->condition ?? 'All';
            $branchId          = (int) $request->branchId;
            $ledgerId          = (int) ($request->ledgerId ?? 0);
            $orderId           = (int) ($request->orderId ?? 0);
            $costCentreId      = (int) ($request->costCentreId ?? 0);
            $currencyId        = (int) $request->currencyId;
            $userId            = $request->userId ?? '';
            $isAccountsPosting = filter_var(
                $request->isAccountsPosting,
                FILTER_VALIDATE_BOOLEAN
            );

            $data = DB::select(
                'SELECT * FROM public."material_receipt_report_fill_detail"(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?
            )',
                [
                    $fromDate,
                    $toDate,
                    $condition,
                    $branchId,
                    $ledgerId,
                    $orderId,
                    $costCentreId,
                    $currencyId,
                    $userId,
                    $isAccountsPosting,
                    $request->mode ?? 'Detailed'

                ]
            );

            return response()->json([
                'status'  => true,
                'count'   => count($data),
                'data'    => $data,
                'message' => 'Material Receipt Report fetched successfully'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function materialreceiptpurchaseorderlist(Request $request)
    {
        $ledgerId = $request->ledgerId;
        $branchId = $request->branchId;
        $receiptmasterid = $request->receiptmasterId;

        if (!$ledgerId || !$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide ledgerId and branchId'
            ], 400);
        }

        // Call PostgreSQL function
        $results = DB::select('SELECT * FROM public.get_material_receipt_purchase_order_list(?, ?, ?)', [
            $ledgerId,
            $branchId,
            $receiptmasterid
        ]);

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Material Receipt Purchase Order List Retrieved Successfully',
            'data' => $results,
        ]);
    }

    public function materialreceiptpurchaseorderDetails(Request $request)
    {
        $SalesOrderinvoices = DB::select('SELECT * FROM public.get_purchase_order_master_data_byid(?)', [
            $request->ordermasterId
        ]);

        // Check if found
        if (empty($SalesOrderinvoices)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Purchase Order Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $SalesOrderinvoices = (array) $SalesOrderinvoices[0];

        // Step 2: Get invoice details using proformamasterid
        $SalesOrderinvoicesDetails = DB::select('SELECT * FROM public.get_material_receipt_purchase_order_details(?, ?)', [
            $SalesOrderinvoices['orderMasterId'],
            $request->receiptmasterId
        ]);

        // Step 3: Attach invoice details to master data
        $SalesOrderinvoices['materialDetails'] = $SalesOrderinvoicesDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Material Receipt Purchase Order Details Retrieved Successfully',
            'data' => $SalesOrderinvoices,
        ], 200);
    }
}