<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\StockTransferMaster;
use App\Models\StockTransferDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockTransferMasterController extends Controller
{
    /**
     * List Stock Transfer
     */
    public function index($id)
    {
        try {
            $data = DB::select('SELECT * FROM public.get_all_stock_transfer_master(?)', [$id]);

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Stock Transfer Retrieved Successfully',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Stock Transfer by id
     */
    public function show($id, $branchId)
    {
        $stocktrans = DB::select('SELECT * FROM public.get_all_stock_transfer_master_byId(?)', [$id]);

        // Check if found
        if (empty($stocktrans)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Stock Transfer Not Found',
            ], 404);
        }

        // Convert to object (since DB::select returns an array)
        $stocktrans = (array) $stocktrans[0];

        // Step 2: Get Receipt details using receiptMasterId
        $transferDetails = DB::select('SELECT * FROM public.get_stock_transfer_details_by_masterId(?)', [$stocktrans['transferMasterId']]);

        // Step 3: Attach Receipt details to master data
        $stocktrans['transferDetails'] = $transferDetails;

        // Step 4: Return combined JSON response
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Stock Transfer Retrieved Successfully',
            'data' => $stocktrans,
        ], 200);
    }


    /**
     * Create Stock Transfer
     */
    public function store(Request $request)
    {
        // Log::info('Stock Transfer Store called');
        // Log::debug('Incoming Request:', $request->all());
        DB::beginTransaction();
        try {
            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);

            // Insert Stock Transfer
            $result = DB::select(
                'SELECT insert_stock_transfer_master(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                ) AS id',
                [
                    $codeData['table_pk'], // transferMasterId
                    $codeData['voucherNo'],  // voucherNo
                    $codeData['suffixPrefixId'],  // suffixPrefixId
                    $codeData['voucherCode'],  // transferNo
                    $request->date,
                    $request->branchIdFrom,
                    $request->branchIdTo,
                    $request->godownIdFrom,
                    $request->godownIdTo,
                    $request->transportCompany,
                    $request->grandTotal,
                    $request->narration,
                    $request->status,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->currencyConversionId,
                    $request->exchangeRate,
                    $request->exchangeDate,
                    $request->branchId,
                    $createdDate, // CreatedDate
                    $request->CreatedUser,
                    'Pending'  // approvedStatus
                ]
            );

            // dd($result);
            $stocktrans = StockTransferMaster::find($result[0]->id);

            // Insert into transferDetails
            if ($request->has('transferDetails')) {
                foreach ($request->transferDetails as $sd) {
                    $result = DB::select('SELECT insert_stock_transfer_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS id', [
                        (StockTransferDetails::max('transferDetailsId') ?? 0) + 1,    // transferDetailsId
                        $stocktrans->transferMasterId,        // transferMasterId
                        $sd['productCode'],                  // productCode
                        $sd['quantity'],                          // quantity
                        $sd['unitId'],                          // unitId
                        $sd['rate'],                            // rate
                        $sd['amount'],                          // amount
                        $sd['rackIdFrom'],                      // rackIdFrom
                        $sd['rackIdTo'],                        // rackIdTo
                        $sd['ConversionFactor'],                // ConversionFactor
                        $sd['barcode'],                         // barcode
                        $request->branchIdTo,                     // branchId
                        $createdDate,  // CreatedDate
                        $request->CreatedUser,               // CreatedUser
                        null,                                // ModifiedDate
                        null,                                 // ModifiedUser
                        null //receivedQuantity
                    ]);

                    $createdDate = getBranchNow($request->branchIdFrom);

                    // add StockPosting
                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $stocktrans->date,           // date
                        'Stock Transfer',        // voucherType
                        $stocktrans->transferMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $request->BatchId,                  // batchId
                        $stocktrans->godownIdFrom,        // godownId
                        $sd['rackIdFrom'],          // rackId
                        $sd['quantity'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'] / $sd['ConversionFactor'],            // voucherRate
                        0,                      // inwardQty
                        $sd['quantity'] * $sd['ConversionFactor'],            // outwardQty
                        $sd['rate'] / $sd['ConversionFactor'],            // rate
                        $sd['unitId'],         // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $request->branchIdFrom,      // branchId
                        $stocktrans->CreatedUser,  // CreatedUser
                        $createdDate
                    ]);
                    ActivityHelper::logActivity(
                        auth()->id() ?? 0,
                        'Stock Transfer insert',
                        'Stock Transfer',
                        'INSERTED',
                        $stocktrans->branchId,
                        $request->CreatedUser ?? 'System',
                        $stocktrans->transferMasterId,
                        $codeData['voucherNo'],
                        $request->date,
                        null,
                        $request->grandTotal
                    );

                    // $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    // 	$stocktrans->date,           // date
                    // 	'Stock Transfer',        // voucherType
                    // 	$stocktrans->transferMasterId,  // voucherNo
                    // 	$sd['productCode'],     // productCode
                    // 	$request->BatchId,     // batchId
                    // 	$stocktrans->godownIdTo,        // godownId
                    // 	$sd['rackIdTo'],          // rackId
                    // 	$sd['quantity'],            // VoucherQty
                    // 	$sd['unitId'],          // VoucherUnitId
                    // 	$sd['rate'] / $sd['ConversionFactor'],            // voucherRate
                    // 	$sd['quantity'] * $sd['ConversionFactor'],                      // inwardQty
                    // 	0,            // outwardQty
                    // 	$sd['rate'] / $sd['ConversionFactor'],            // rate
                    // 	$sd['unitId'],         // unitId
                    // 	null,                   // referenceNo
                    // 	null,                   // referenceType
                    // 	$sd['branchId'],      // branchId
                    // 	$stocktrans->CreatedUser,  // CreatedUser
                    // ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Stock Transfer Inserted Successfully'
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
     * Update Stock Transfer
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $stocktrans = StockTransferMaster::find($id);

        if (!$stocktrans) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Stock Transfer Not Found',
            ], 404);
        }
        $oldValue = $stocktrans->grandTotal;
        $branchId = $request->input('branchId', 1);
        $modifiedDate = getBranchNow($branchId);
        DB::beginTransaction();
        try {
            // Update Stock Transfer
            $result = DB::select(
                'SELECT update_stock_transfer_master(
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				) AS id',
                [
                    $id,       // transferMasterId
                    $request->date,
                    $request->branchIdFrom,
                    $request->branchIdTo,
                    $request->godownIdFrom,
                    $request->godownIdTo,
                    $request->transportCompany,
                    $request->grandTotal,
                    $request->narration,
                    $request->status,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->currencyConversionId,
                    $request->exchangeRate,
                    $request->exchangeDate,
                    $request->branchId,
                    $modifiedDate, // ModifiedDate
                    $request->ModifiedUser
                ]
            );

            if ($request->has('transferDetails')) {
                DB::select('SELECT delete_stock_transfer_details_byid(?)', [$stocktrans->transferMasterId]);

                DB::select('SELECT delete_stock_posting(?, ?)', [
                    'Stock Transfer', // voucher_type
                    $stocktrans->transferMasterId // voucherNo
                ]);

                foreach ($request->transferDetails as $sd) {
                    $result = DB::select('SELECT insert_stock_transfer_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS id', [
                        (StockTransferDetails::max('transferDetailsId') ?? 0) + 1,    // transferDetailsId
                        $stocktrans->transferMasterId,        // transferMasterId
                        $sd['productCode'],                  // productCode
                        $sd['quantity'],                          // quantity
                        $sd['unitId'],                          // unitId
                        $sd['rate'],                            // rate
                        $sd['amount'],                          // amount
                        $sd['rackIdFrom'],                      // rackIdFrom
                        $sd['rackIdTo'],                        // rackIdTo
                        $sd['ConversionFactor'],                // ConversionFactor
                        $sd['barcode'],                         // barcode
                        $request->branchIdTo,                     // branchId
                        $stocktrans->CreatedDate,  // CreatedDate
                        $stocktrans->CreatedUser,               // CreatedUser
                        $modifiedDate,                                // ModifiedDate
                        $request->ModifiedUser,               // ModifiedUser
                        null //receivedQuantity
                    ]);
                    $createdDate = getBranchNow($request->branchIdFrom);
                    // add StockPosting
                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $stocktrans->date,           // date
                        'Stock Transfer',        // voucherType
                        $stocktrans->transferMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $request->BatchId,                  // batchId
                        $stocktrans->godownIdFrom,        // godownId
                        $sd['rackIdFrom'],          // rackId
                        $sd['quantity'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'] / $sd['ConversionFactor'],            // voucherRate
                        0,                      // inwardQty
                        $sd['quantity'] * $sd['ConversionFactor'],            // outwardQty
                        $sd['rate'] / $sd['ConversionFactor'],            // rate
                        $sd['unitId'],         // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $request->branchIdFrom,      // branchId
                        $stocktrans->ModifiedUser,  // CreatedUser
                        $modifiedDate
                    ]);

                    // $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    // 	$stocktrans->date,           // date
                    // 	'Stock Transfer',        // voucherType
                    // 	$stocktrans->transferMasterId,  // voucherNo
                    // 	$sd['productCode'],     // productCode
                    // 	$request->BatchId,     // batchId
                    // 	$stocktrans->godownIdTo,        // godownId
                    // 	$sd['rackIdTo'],          // rackId
                    // 	$sd['quantity'],            // VoucherQty
                    // 	$sd['unitId'],          // VoucherUnitId
                    // 	$sd['rate'] / $sd['ConversionFactor'],            // voucherRate
                    // 	$sd['quantity'] * $sd['ConversionFactor'],                      // inwardQty
                    // 	0,            // outwardQty
                    // 	$sd['rate'] / $sd['ConversionFactor'],            // rate
                    // 	$sd['unitId'],         // unitId
                    // 	null,                   // referenceNo
                    // 	null,                   // referenceType
                    // 	$sd['branchId'],      // branchId
                    // 	$stocktrans->ModifiedUser,  // CreatedUser
                    // ]);
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Stock Transfer Update',
                'Stock Transfer',
                'UPDATED',
                $stocktrans->branchId,
                $request->ModifiedUser ?? 'System',
                $stocktrans->transferMasterId,
                $stocktrans->voucherNo,
                $stocktrans->date,
                $oldValue,
                $stocktrans->grandTotal
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Stock Transfer Updated Successfully'
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
     * Delete Stock Transfer
     */
    public function destroy($id)
    {
        $stocktrans = StockTransferMaster::find($id);

        if (!$stocktrans) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Stock Transfer Not Found',
            ], 404);
        }
        $oldValue = $stocktrans->grandTotal;

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_stock_transfer_details_byid(?)', [$id]);

            DB::statement('SELECT public.delete_stock_transfer_master_byid(?)', [$id]);

            DB::select('SELECT delete_stock_posting(?, ?)', [
                'Stock Transfer', // voucher_type
                $stocktrans->transferMasterId // voucherNo
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Stock Transfer Delete',
                'Stock Transfer',
                'DELETED',
                $stocktrans->branchId,
                $stocktrans->ModifiedUser ?? 'System',
                $stocktrans->transferMasterId,
                $stocktrans->voucherNo,
                $stocktrans->date,
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Stock Transfer Deleted Successfully',
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


    public function AcceptTransfer(Request $request, $id)
    {
        // dd($request->all());
        $stocktrans = StockTransferMaster::find($id);

        if (!$stocktrans) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Stock Transfer Not Found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Update Stock Transfer
            $result = DB::select(
                'SELECT update_stock_transfer_accept_details(
					?, ?, ?, ?
				) AS id',
                [
                    $id,       // transferMasterId
                    $request->approvedstatus,
                    now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),  // approvedDate
                    $request->approvedUser
                ]
            );

            if ($request->has('transferDetails')) {
                foreach ($request->transferDetails as $sd) {
                    $createdDate = getBranchNow($sd['branchId']);
                    $result = DB::select('SELECT insert_stock_transfer_details(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS id', [
                        (StockTransferDetails::max('transferDetailsId') ?? 0) + 1,    // transferDetailsId
                        $stocktrans->transferMasterId,        // transferMasterId
                        $sd['productCode'],                  // productCode
                        $sd['quantity'],                          // quantity
                        $sd['unitId'],                          // unitId
                        $sd['rate'],                            // rate
                        $sd['amount'],                          // amount
                        $sd['rackIdFrom'],                      // rackIdFrom
                        $sd['rackIdTo'],                        // rackIdTo
                        $sd['ConversionFactor'],                // ConversionFactor
                        $sd['barcode'],                         // barcode
                        $sd['branchId'],                     // branchId
                        $stocktrans->CreatedDate,  // CreatedDate
                        $stocktrans->CreatedUser,               // CreatedUser
                        now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),                                // ModifiedDate
                        $request->ModifiedUser,               // ModifiedUser
                        $sd['receivedQuantity']
                    ]);

                    // add StockPosting

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $stocktrans->date,           // date
                        'Stock Transfer',        // voucherType
                        $stocktrans->transferMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $request->BatchId,     // batchId
                        $stocktrans->godownIdTo,        // godownId
                        $sd['rackIdTo'],          // rackId
                        $sd['receivedQuantity'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'] / $sd['ConversionFactor'],            // voucherRate
                        $sd['receivedQuantity'] * $sd['ConversionFactor'],                      // inwardQty
                        0,            // outwardQty
                        $sd['rate'] / $sd['ConversionFactor'],            // rate
                        $sd['unitId'],         // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $sd['branchId'],      // branchId
                        $stocktrans->ModifiedUser,  // CreatedUser
                        $createdDate

                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Stock Transfer Accepted Successfully'
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


    public function stockTransferReport(Request $request)
    {
        try {
            $request->validate([
                'fromDate'  => 'required|date',
                'toDate'    => 'required|date',
                'branchId'  => 'required|integer',
                'userId'    => 'nullable|string',
                'mode'       => 'required|string|in:Detailed,Summary',
            ]);

            $data = DB::select(
                'SELECT * FROM "stock_transfer_report_fill"(?, ?, ?, ?, ?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->branchId,
                    $request->userId ?? null,
                    $request->mode,
                ]
            );

            return response()->json([
                'status'  => 200,
                'errors'  => false,
                'data'    => $data,
                'message' => 'Stock Transfer Report fetched successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'errors'  => true,
                'message' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}