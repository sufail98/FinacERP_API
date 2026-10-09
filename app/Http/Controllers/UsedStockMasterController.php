<?php

namespace App\Http\Controllers;

use App\Models\UsedStockMaster;
use App\Models\UsedStockMasterDetails;
use Illuminate\Http\Request;
use App\Helpers\ActivityHelper;

use Illuminate\Support\Facades\DB;

class UsedStockMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = UsedStockMaster::with('details')->get();
        return response()->json([
            'status' => 200,
            'error' => false,
            'data' => $data
        ], 200);
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
            $branchId    = (int) $request->branchId;
            $voucherType = $request->voucherType;
            $yearId      = (int) $request->yearId;

            // Generate voucher number and master ID
            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);

            
            // Insert Used Stock Master
            DB::select(
                'SELECT sp_used_stock_master(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::json,?)',
                [
                    'insert',
                    $codeData['table_pk'],
                    $codeData['suffixPrefixId'],
                    $codeData['voucherNo'],
                    $codeData['voucherCode'],
                    $request->date,
                    $request->narration,
                    $request->totalAmount,
                    $request->RefNo,
                    $request->RefDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $branchId,
                    $request->CreatedUser,
                    json_encode($request->usedStockDetails),
                    $createdDate
                ]
            );
            foreach ($request->usedStockDetails as $sd) {

                $conversionFactor = $sd['ConversionFactor']; // cf
                $qty              = $sd['qty'];
                $rate             = $sd['rate'];

                DB::select(
                    'SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $request->date,                       // date
                        'Used Stock',                         // voucherType
                        $codeData['voucherNo'],               // voucherNo
                        $sd['productCode'],                   // productCode
                        $sd['batchId'] ?? 0,                  // batchId
                        $sd['GodownId'],                      // godownId
                        $sd['RackId'],                        // rackId
                        $qty,                                 // VoucherQty
                        $sd['unitId'],                        // VoucherUnitId
                        $rate / $conversionFactor,            // voucherRate
                        0,                                    // inwardQty
                        $qty * $conversionFactor,             // outwardQty
                        $rate / $conversionFactor,            // rate (base rate)
                        $sd['baseunitId'],                    // unitId (BaseUnitId)
                        null,                                 // referenceNo
                        null,                                 // referenceType
                        $branchId,                            // branchId
                        $request->CreatedUser,                 // createdUser
                        $createdDate
                    ]
                );
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'used Stock insert',
                'used Stock',
                'INSERTED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $codeData['table_pk'],
                $codeData['voucherNo'],
                $request->date,
                null,
                $request->totalAmount
            );
            DB::commit();

            return response()->json([
                'status'  => 201,
                'error'   => false,
                'message' => 'Used Stock Inserted Successfully',
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
    public function show(string $id)
    {
        try {

            $usedStock = UsedStockMaster::with('details.product.salesPrices')->findOrFail($id);
            $usedStock->details->transform(function ($detail) {
                if ($detail->product) {
                    $detail->salesPrices = $detail->product->salesPrices->toArray();
                } else {
                    $detail->salesPrices = [];
                }
                return $detail;
            });
            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $usedStock
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => 404,
                'error'   => true,
                'message' => "Used Stock with ID {$id} not found."
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
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
            $branchId    = (int) $request->branchId;
            $voucherType = $request->voucherType;
            $yearId      = (int) $request->yearId;

            // Generate voucher details
            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $modifiedDate = getBranchNow($branchId);
            // ✅ Update Used Stock Master + Details
            DB::select('SELECT sp_used_stock_master(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                'update',
                (int)$id,
                $codeData['suffixPrefixId'],
                $codeData['voucherNo'],
                $codeData['voucherCode'],
                $request->date,
                $request->narration,
                $request->totalAmount,
                $request->RefNo,
                $request->RefDate,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $branchId,
                $request->CreatedUser,
                json_encode($request->usedStockDetails),
                $modifiedDate
            ]);

            // ✅ 1. DELETE OLD STOCK POSTING ENTRIES
            DB::statement(
                'DELETE FROM "tbl_StockPosting"
     WHERE "voucherNo" = ?
     AND "voucherType" = ?
     AND "branchId" = ?',
                [
                    $codeData['voucherNo'],
                    'Used Stock',
                    $branchId
                ]
            );

            // ✅ 2. RE-INSERT STOCK POSTING (OUTWARD)
            foreach ($request->usedStockDetails as $sd) {

                $conversionFactor = $sd['ConversionFactor'];
                $qty              = $sd['qty'];
                $rate             = $sd['rate'];

                DB::select(
                    'SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $request->date,                       // date
                        'Used Stock',                         // voucherType
                        $codeData['voucherNo'],               // voucherNo
                        $sd['productCode'],                   // productCode
                        $sd['batchId'] ?? 0,                  // batchId
                        $sd['GodownId'],                      // godownId
                        $sd['RackId'],                        // rackId
                        $qty,                                 // VoucherQty
                        $sd['unitId'],                        // VoucherUnitId
                        $rate / $conversionFactor,            // voucherRate
                        0,                                    // inwardQty
                        $qty * $conversionFactor,             // outwardQty
                        $rate / $conversionFactor,            // rate (base rate)
                        $sd['baseunitId'],                    // unitId (BaseUnitId)
                        null,                                 // referenceNo
                        null,                                 // referenceType
                        $branchId,                            // branchId
                        $request->CreatedUser,                 // createdUser
                        $modifiedDate

                    ]
                );
            }

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'used Stock update',
                'used Stock',
                'UPDATED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $id,
                $codeData['voucherNo'],
                $request->date,
                $request->totalAmount,
                $request->totalAmount
            );
            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Used Stock Updated Successfully'
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



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $usedStockMasterId)
    {
        DB::beginTransaction();
        try {
            // Call your stock deletion function
            DB::select('SELECT delete_stock_posting(?, ?)', [
                'Used Stock',
                $usedStockMasterId
            ]);
            $Masterdata = UsedStockMaster::find($usedStockMasterId);

            // Delete master and details directly using SQL
            DB::delete('DELETE FROM "tbl_UsedStockDetails" WHERE "usedStockMasterId" = ?', [
                $usedStockMasterId
            ]);

            $deleted = DB::delete('DELETE FROM "tbl_UsedStockMaster" WHERE "usedStockMasterId" = ?', [
                $usedStockMasterId
            ]);

            if ($deleted === 0) {
                DB::rollBack();
                return response()->json([
                    'status'  => 404,
                    'error'   => true,
                    'message' => "Used Stock with ID $usedStockMasterId not found."
                ], 404);
            }

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'used Stock delete',
                'used Stock',
                'DELETED',
                $Masterdata->branchId,
                $Masterdata->CreatedUser ?? 'System',
                $usedStockMasterId,
                $Masterdata->voucherNo,
                $Masterdata->date,
                $Masterdata->totalAmount,
                $Masterdata->totalAmount
            );
            DB::commit();
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Used Stock Deleted Successfully'
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

    public function usedStockReport(Request $request)
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
                'SELECT * FROM "used_stock_report_fill"(?, ?, ?, ?, ?)',
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
                'message' => 'Used Stock Report fetched successfully',
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