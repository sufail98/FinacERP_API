<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseQuotationDetails;
use Illuminate\Http\Request;
use App\Helpers\ActivityHelper;

use Illuminate\Support\Facades\DB;

class PurchaseQuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {

            $fromDate = $request->fromDate ?? null;
            $toDate   = $request->toDate ?? null;

            $quotations = DB::select(
                'SELECT * FROM public.show_all_purchase_quotations_with_details(?, ?)',
                [$fromDate, $toDate]
            );

            $quotations = array_map(function ($q) {
                $q->details = $q->details ? json_decode($q->details) : [];
                return $q;
            }, $quotations);

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $quotations
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


            $result = DB::select(
                'SELECT public.insert_purchase_quotation_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) AS out_quotationMasterId',
                [
                    $codeData['table_pk'],
                    $codeData['suffixPrefixId'],
                    $codeData['voucherNo'],
                    $codeData['voucherCode'],
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
                    $branchId,
                    $createdDate,
                    $request->CreatedUser
                ]
            );

            $quotationMasterId = $result[0]->out_quotationmasterid;


            if ($request->has('quotationDetails')) {
                foreach ($request->quotationDetails as $qd) {

                    DB::select(
                        'SELECT public.insert_purchase_quotation_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )',
                        [
                            (PurchaseQuotationDetails::max('quotationDetailsId') ?? 0) + 1,
                            $quotationMasterId,
                            $qd['productCode'],
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
                            $qd['AddCostOnProduct'],
                            $qd['OtherChargeOnProduct'],
                            $qd['productDEscription'],
                            $branchId,
                            $createdDate,
                            $request->CreatedUser,
                            NULL,
                            NULL
                        ]
                    );
                }
            }
            $master = DB::table('tbl_PurchaseQuotation')
                ->where('quotationMasterId', $quotationMasterId)
                ->first();

            $details = DB::table('tbl_PurchaseQuotationDetails')
                ->where('quotationMasterId', $quotationMasterId)
                ->get();


                ActivityHelper::logActivity(
                $request->employeeId,               // userId
                'purchase Invoice Insert',          // activityType
                'purchase Invoice',                 // voucherType
                'INSERTED',                          // action
                $request->branchId,                 // branchId
                $request->CreatedUser ?? 'System', // createdUser
                $quotationMasterId,        // masterId
                $master->voucherNo ?? null,       // voucherNo
                $master->date ?? null,            // voucherDate
                null,                         // oldAmount
                $request->totalAmount               // newAmount
            );
            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Purchase Quotation Inserted Successfully',
                'data'    => [
                    'quotationMasterId' => $quotationMasterId,
                    'quotationNo'       => $master->quotationNo ?? null,

                    'payload' => [
                        'purchaseQuotationMaster' => [
                            ...(array) $master,
                            'quotationDetails' => $details->toArray()
                        ]
                    ]
                ]
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */

    public function show($id)
    {
        try {

            $quotation = DB::select(
                'SELECT * FROM public.show_purchase_quotation_with_details_by_id(?)',
                [$id]
            );

            if (empty($quotation)) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Purchase Quotation not found'
                ], 404);
            }

            $quotation = array_map(function ($q) {

                if (isset($q->details)) {
                    $details = json_decode($q->details, true);

                    if (is_array($details)) {

                        foreach ($details as &$item) {

                            if (!empty($item['productCode'])) {

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
            }, $quotation);

            return response()->json([
                'status' => 200,
                'error' => false,
                'data' => $quotation
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
            // Find the Purchase Quotation Master
            $purchaseQuotation = PurchaseQuotation::find($id);

            if (!$purchaseQuotation) {
                return response()->json([
                    'status' => 404,
                    'error' => true,
                    'message' => 'Purchase Quotation not found',
                ], 404);
            }

            $branchId = (int) $request->input('branchId', 1);
            // $modifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $modifiedUser = $request->ModifiedUser;
            $modifiedDate = getBranchNow($branchId);

            // Update Purchase Quotation Master (except suffixPrefixId, voucherNo, orderNo)
            DB::select(
                'SELECT public.update_purchase_quotation_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )',
                [
                    $id, // quotationMasterId
                    $purchaseQuotation->suffixPrefixId, // keep original
                    $purchaseQuotation->voucherNo,       // keep original
                    $purchaseQuotation->orderNo,         // keep original
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
                    $branchId,
                    $modifiedDate,
                    $modifiedUser
                ]
            );

            // Delete existing details
            DB::table('tbl_PurchaseQuotationDetails')
                ->where('quotationMasterId', $id)
                ->delete();

            // Insert new details
            if ($request->has('quotationDetails')) {
                foreach ($request->quotationDetails as $qd) {
                    DB::select(
                        'SELECT public.insert_purchase_quotation_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )',
                        [
                            (PurchaseQuotationDetails::max('quotationDetailsId') ?? 0) + 1,
                            $id,
                            $qd['productCode'],
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
                            $qd['AddCostOnProduct'],
                            $qd['OtherChargeOnProduct'],
                            $qd['productDEscription'],
                            $branchId,
                            $modifiedDate,   // CreatedDate → using update time
                            $modifiedUser,   // CreatedUser → using update user
                            $modifiedDate,   // ModifiedDate
                            $modifiedUser    // ModifiedUser
                        ]
                    );
                }
            }
            ActivityHelper::logActivity(
                $request->employeeId,               // userId
                'purchase Invoice update',          // activityType
                'purchase Invoice',                 // voucherType
                'UPDATED',                          // action
                $request->branchId,                 // branchId
                $request->CreatedUser ?? 'System', // createdUser
                $id,        // masterId
                $purchaseQuotation->voucherNo,       // voucherNo
                $request->date,            // voucherDate
                $purchaseQuotation->totalAmount,                         // oldAmount
                $request->totalAmount               // newAmount
            );

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Purchase Quotation Updated Successfully',
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
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $purchaseQuotation = PurchaseQuotation::find($id);
            
            ActivityHelper::logActivity(
                $purchaseQuotation->employeeId,               // userId
                'purchase Invoice delete',          // activityType
                'purchase Invoice',                 // voucherType
                'DELETED',                          // action
                $purchaseQuotation->branchId,                 // branchId
                $purchaseQuotation->CreatedUser ?? 'System', // createdUser
                $id,        // masterId
                $purchaseQuotation->voucherNo,       // voucherNo
                $purchaseQuotation->date,            // voucherDate
                $purchaseQuotation->totalAmount,                         // oldAmount
                $purchaseQuotation->totalAmount               // newAmount
            );

            // Call the PostgreSQL delete function
            DB::select('SELECT public.delete_purchase_quotation_master(?)', [$id]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Purchase Quotation deleted successfully',
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
                "SELECT * FROM purchase_quotation_report(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?
                )",
                [
                    $request->from_date,
                    $request->to_date,
                    $request->branch_id,
                    $request->ledger_id ?? 'All',
                    $request->costcenter_id ?? 'All',
                    $request->party_name ?? 'All',
                    $request->condition ?? null,
                    $request->currency_id,
                    $request->mode
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Purchase Quotation Report Generated',
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