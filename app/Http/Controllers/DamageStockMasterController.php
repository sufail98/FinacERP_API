<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\DamageStockMaster;
use App\Models\DamageStockDetails1;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DamageStockMasterController extends Controller
{
    /**
     * List Damage Stock
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $damage = DB::select('SELECT * FROM public.get_all_damage_stock_master(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($damage);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Damage Stock Retrieved Successfully',
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
     * Get Damage Stock by id
     */
    public function show($id)
    {
        $damage = DB::select('SELECT * FROM public.get_damage_stock_master_byid(?)', [$id]);

        // Check if found
        if (empty($damage)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Damage Stock Not Found',
            ], 404);
        }

        $damage = (array) $damage[0];

        $damageDetails = DB::select('SELECT * FROM public.get_damage_stock_details_byid(?)', [$damage['damageStockMasterId']]);

        $damageDetails = array_map(function ($item) {
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
        }, $damageDetails);
        $damage['damageDetails'] = $damageDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Damage Stock Retrieved Successfully',
            'data' => $damage,
        ], 200);
    }


    /**
     * Create Damage Stock
     */
    public function store(Request $request)
    {
        // Log::info('Damage Stock Store called');
        // Log::debug('Incoming Request:', $request->all());
        DB::beginTransaction();
        try {
            $branchId = (int) $request->input('branchId', 1);
            $voucherType = $request->input('voucherType');
            $yearId = (int) $request->input('yearId', 1);

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $createdDate = getBranchNow($branchId);
            // Insert Damage Stock
            $result = DB::select(
                'SELECT insert_damage_stock_master(
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                ) AS out_damageStockMasterId',
                [
                    $codeData['table_pk'], // damageStockMasterId
                    $codeData['suffixPrefixId'], // suffixPrefixId
                    $codeData['voucherNo'], // voucherNo
                    $codeData['voucherCode'], // damageStockNo
                    $request->date,
                    $request->narration,
                    $request->totalAmount,
                    $request->RefNo,
                    $request->RefDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->branchId,
                    $createdDate,
                    $request->CreatedUser,  // CreatedUser
                    NULL, // ModifiedDate
                    NULL  // ModifiedUser
                ]
            );

            // dd($result);
            $damage = DamageStockMaster::find($result[0]->out_damagestockmasterid);

            // Insert into damageDetails
            if ($request->has('damageDetails')) {
                foreach ($request->damageDetails as $sd) {
                    $result = DB::select('SELECT insert_damage_stock_details1(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS id', [
                        (DamageStockDetails1::max('damageStockDetails1Id') ?? 0) + 1,    // damageStockDetails1Id
                        $damage->damageStockMasterId,        // damageStockMasterId
                        $sd['productCode'],                  // productCode
                        $sd['qty'],                          // qty
                        $sd['rate'],                         // rate
                        $sd['unitId'],                       // unitId
                        $sd['amount'],                       // amount
                        $sd['ConversionFactor'],             // ConversionFactor
                        $sd['barcode'],                      // barcode
                        $sd['productDescription'],           // productDescription
                        $sd['SlNo'],                         // SlNo
                        $sd['GodownId'],                     // GodownId
                        $sd['RackId'],                       // RackId
                        $request->branchId,                  // branchId
                        $createdDate,  // CreatedDate
                        $request->CreatedUser,               // CreatedUser
                        null,                                // ModifiedDate
                        null                                 // ModifiedUser
                    ]);

                    // add StockPosting
                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $damage->date,           // date
                        'Damage Stock',        // voucherType
                        $damage->damageStockMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $request->BatchId,     // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $sd['qty'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'] / $sd['ConversionFactor'],            // voucherRate
                        0,                      // inwardQty
                        $sd['qty'] * $sd['ConversionFactor'],            // outwardQty
                        $sd['rate'] / $sd['ConversionFactor'],            // rate
                        $sd['unitId'],         // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $request->branchId,      // branchId
                        $request->CreatedUser,  // CreatedUser
                        $createdDate
                    ]);
                }
            }

            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Damage Stock insert',
                'Damage Stock',
                'INSERTED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $damage->damageStockMasterId,
                $codeData['voucherNo'],
                $request->date,
                null,
                $request->totalAmount
            );
            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Damage Stock Inserted Successfully'
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
     * Update Damage Stock
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $damage = DamageStockMaster::find($id);

        if (!$damage) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Damage Stock Not Found',
            ], 404);
        }
        $oldValue = $damage->totalAmount;

        DB::beginTransaction();
        try {
            $branchId = $request->input('branchId', 1);
            $modifiedDate = getBranchNow($branchId);
            // Update Damage Stock
            $result = DB::select(
                'SELECT update_damage_stock_master(
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				) AS id',
                [
                    $id,
                    $request->date,
                    $request->narration,
                    $request->totalAmount,
                    $request->RefNo,
                    $request->RefDate,
                    $request->postedStatus,
                    $request->postedBy,
                    $request->postedDate,
                    $request->branchId,
                    $request->CreatedDate,
                    $request->CreatedUser,
                    $modifiedDate,       // ModifiedDate
                    $request->ModifiedUser // ModifiedUser
                ]
            );

            if ($request->has('damageDetails')) {
                DB::select('SELECT delete_damage_stock_details1_byid(?)', [$damage->damageStockMasterId]);

                DB::select('SELECT delete_stock_posting(?, ?)', [
                    'Damage Stock', // voucher_type
                    $damage->damageStockMasterId // voucherNo
                ]);

                foreach ($request->damageDetails as $sd) {
                    $result = DB::select('SELECT insert_damage_stock_details1(
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    ) AS id', [
                        (DamageStockDetails1::max('damageStockDetails1Id') ?? 0) + 1,    // damageStockDetails1Id
                        $damage->damageStockMasterId,        // damageStockMasterId
                        $sd['productCode'],                  // productCode
                        $sd['qty'],                          // qty
                        $sd['rate'],                         // rate
                        $sd['unitId'],                       // unitId
                        $sd['amount'],                       // amount
                        $sd['ConversionFactor'],             // ConversionFactor
                        $sd['barcode'],                      // barcode
                        $sd['productDescription'],           // productDescription
                        $sd['SlNo'],                         // SlNo
                        $sd['GodownId'],                     // GodownId
                        $sd['RackId'],                       // RackId
                        $request->branchId,                  // branchId
                        $damage->CreatedDate,                  // CreatedDate
                        $damage->CreatedUser,               // CreatedUser
                        $modifiedDate,      // ModifiedDate
                        $request->ModifiedUser                                 // ModifiedUser
                    ]);

                    // add StockPosting
                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $damage->date,           // date
                        'Damage Stock',        // voucherType
                        $damage->damageStockMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $request->BatchId,     // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $sd['qty'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'] / $sd['ConversionFactor'],            // voucherRate
                        0,                      // inwardQty
                        $sd['qty'] * $sd['ConversionFactor'],            // outwardQty
                        $sd['rate'] / $sd['ConversionFactor'],            // rate
                        $sd['unitId'],         // unitId
                        null,                   // referenceNo
                        null,                   // referenceType
                        $request->branchId,      // branchId
                        $request->ModifiedUser,  // CreatedUser
                        $modifiedDate
                    ]);
                }
            }
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Damage Stock Update',
                'Damage Stock',
                'UPDATED',
                $damage->branchId,
                $request->ModifiedUser ?? 'System',
                $damage->damageStockMasterId,
                $damage->voucherNo ?? null,
                $damage->date,
                $damage->totalAmount,
                $damage->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Damage Stock Updated Successfully'
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
     * Delete Damage Stock
     */
    public function destroy($id)
    {
        $damage = DamageStockMaster::find($id);

        if (!$damage) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Damage Stock Not Found',
            ], 404);
        }
        $oldValue = $damage->totalAmount;

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_damage_stock_details1_byid(?)', [$id]);

            DB::statement('SELECT public.delete_damage_stock_master_byid(?)', [$id]);

            DB::select('SELECT delete_stock_posting(?, ?)', [
                'Damage Stock', // voucher_type
                $damage->damageStockMasterId // voucherNo
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Damage Stock Delete',
                'Damage Stock',
                'DELETED',
                $damage->branchId,
                $damage->ModifiedUser ?? 'System',
                $damage->damageStockMasterId,
                $damage->voucherNo ?? null,
                $damage->date,
                $damage->totalAmount,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Damage Stock Deleted Successfully',
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


    public function damageStockReport(Request $request)
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
                'SELECT * FROM "damage_stock_report_fill"(?, ?, ?, ?, ?)',
                [
                    $request->fromDate,
                    $request->toDate,
                    $request->branchId,
                    $request->userId ?? null,
                    $request->mode,           // 'Detailed' or 'Summary'
                ]
            );

            return response()->json([
                'status'  => 200,
                'errors'  => false,
                'data'    => $data,
                'message' => 'Damage Stock Report fetched successfully',
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