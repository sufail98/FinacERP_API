<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityHelper;
use App\Models\PhysicalStockMaster;
use App\Models\PhysicalStockDetails1;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PhysicalStockMasterController extends Controller
{
    /**
     * List Physical Stock
     */
    public function index(Request $request)
    {
        $limit = (int) ($request->limits ?? 10);
        $page  = (int) ($request->page ?? 1);

        $physical = DB::select('SELECT * FROM public.get_all_physicalstock_master_list(?,?,?,?,?,?)', [
            $request->fromDate,           // fromDate
            $request->toDate,              // toDate
            $request->limits,              // limits
            $request->page,              // page
            $request->branchId,            // branchId
            $request->vouchercode
        ]);

        $collection = collect($physical);

        $total = $collection->count();

        // Manual pagination
        $paginated = $collection
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->values();

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Physical Stock Retrieved Successfully',
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
     * Get Physical Stock by id
     */
    public function show($id)
    {
        $physical = DB::select(
            'SELECT * FROM public.get_physicalstock_master_byid(?)',
            [$id]
        );

        if (empty($physical)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Physical Stock Not Found',
            ], 404);
        }

        $physical = (array) $physical[0];

        // ✅ Get branchId from master
        $branchId = $physical['branchId'];

        $physicalDetails = DB::select(
            'SELECT * FROM public.get_physicalstock_details_byid(?)',
            [$physical['physicalStockMasterId']]
        );

        $physicalDetails = array_map(function ($item) use ($branchId) {
            $item = (array) $item;

            if (!empty($item['productCode'])) {
                $product = Product::with(['salesPrices' => function ($query) use ($branchId) {
                    $query->where('branchId', $branchId);
                }])
                    ->where('productCode', $item['productCode'])
                    ->first();

                $item['salesPrices'] = $product
                    ? $product->salesPrices->toArray()
                    : [];
            } else {
                $item['salesPrices'] = [];
            }

            return $item;
        }, $physicalDetails);

        $physical['physicalDetails'] = $physicalDetails;

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Physical Stock Retrieved Successfully',
            'data' => $physical,
        ], 200);
    }


    /**
     * Create Physical Stock
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

            // Insert Physical Stock
            $result = DB::select('SELECT insert_physicalstock_master(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?
            ) AS out_physicalStockMasterId', [
                $codeData['table_pk'], // physicalStockMasterId
                $codeData['suffixPrefixId'], // suffixPrefixId
                $codeData['voucherNo'], // voucherNo
                $codeData['voucherCode'], // physicalStockNo
                $request->date,
                $request->narration,
                $request->input('executiveId', null),
                $request->totalAmount,
                $request->RefNo,
                $request->RefDate,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $request->branchId,
                $createdDate,
                $request->CreatedUser,
                NULL, // ModifiedDate
                NULL  // ModifiedUser
            ]);

            // dd($result);
            $physical = PhysicalStockMaster::find($result[0]->out_physicalstockmasterid);

            // Insert into physicalDetails
            if ($request->has('physicalDetails')) {
                foreach ($request->physicalDetails as $sd) {
                    $result = DB::select('SELECT insert_physicalstock_details1(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS id', [
                        (PhysicalStockDetails1::max('physicalStockDetails1Id') ?? 0) + 1,
                        $physical->physicalStockMasterId,
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['rate'],
                        $sd['unitId'],
                        $sd['amount'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['productDescription'],
                        $sd['SlNo'],
                        $sd['GodownId'],
                        $sd['RackId'],
                        $sd['currentQty'],
                        $request->branchId,
                        $createdDate,
                        $request->CreatedUser,
                        NULL, // ModifiedDate
                        NULL  // ModifiedUser
                    ]);

                    // add StockPosting
                    $qty = $sd['qty'];
                    $currentQty = $sd['qty'] * $sd['ConversionFactor'];
                    $oldStock = $sd['currentQty'];

                    $inward = 0;
                    $outward = 0;

                    if ($qty == 0) {
                        if ($oldStock > 0) {
                            $inward = 0;
                            $outward = $oldStock;
                        } elseif ($oldStock < 0) {
                            $inward = -$oldStock;
                            $outward = 0;
                        } else {
                            $inward = 0;
                            $outward = 0;
                        }
                    } else {
                        if ($currentQty >= 0) {
                            if ($oldStock >= 0) {
                                $balance = $currentQty - $oldStock;
                                if ($balance >= 0) {
                                    $inward = $balance;
                                    $outward = 0;
                                } else {
                                    $inward = 0;
                                    $outward = -$balance;
                                }
                            } else {
                                $inward = (-$oldStock + $currentQty);
                                $outward = 0;
                            }
                        } else {
                            if ($oldStock >= 0) {
                                $inward = 0;
                                $outward = (-$currentQty + $oldStock);
                            } else {
                                $balance = -$currentQty + $oldStock;
                                if ($balance >= 0) {
                                    $inward = 0;
                                    $outward = $balance;
                                } else {
                                    $inward = -$balance;
                                    $outward = 0;
                                }
                            }
                        }
                    }

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $physical->date,           // date
                        'Physical Stock',        // voucherType
                        $physical->physicalStockMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $request->BatchId,     // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $sd['qty'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        $inward,                      // inwardQty
                        $outward,            // outwardQty
                        $sd['rate'],            // rate
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
                'Physical Stock insert',
                'Physical Stock',
                'INSERTED',
                $request->branchId,
                $request->CreatedUser ?? 'System',
                $physical->physicalStockMasterId,
                $codeData['voucherNo'],
                $physical->date,
                null,
                $request->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Physical Stock Inserted Successfully'
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
     * Update Physical Stock
     */
    public function update(Request $request, $id)
    {
        // dd($request->all());
        $physical = PhysicalStockMaster::find($id);

        if (!$physical) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Physical Stock Not Found',
            ], 404);
        }
        $oldValue = $physical->totalAmount;

        DB::beginTransaction();
        try {
            $branchId = $request->input('branchId', 1);
            $modifiedDate = getBranchNow($branchId);
            // Update Physical Stock
            DB::select('SELECT update_physical_stock_master(?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $id,
                $request->date,
                $request->branchId,
                $request->narration,
                $request->input('executiveId', null),
                $request->totalAmount,
                $request->RefNo,
                $request->RefDate,
                $request->postedStatus,
                $request->postedBy,
                $request->postedDate,
                $modifiedDate,
                $request->ModifiedUser
            ]);


            if ($request->has('physicalDetails')) {
                DB::select('SELECT delete_physicalstock_details1_byid(?)', [$physical->physicalStockMasterId]);

                DB::select('SELECT delete_stock_posting(?, ?)', [
                    'Physical Stock', // voucher_type
                    $physical->physicalStockMasterId // voucherNo
                ]);

                foreach ($request->physicalDetails as $sd) {
                    $result = DB::select('SELECT insert_physicalstock_details1(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS id', [
                        (PhysicalStockDetails1::max('physicalStockDetails1Id') ?? 0) + 1,
                        $physical->physicalStockMasterId,
                        $sd['productCode'],
                        $sd['qty'],
                        $sd['rate'],
                        $sd['unitId'],
                        $sd['amount'],
                        $sd['ConversionFactor'],
                        $sd['barcode'],
                        $sd['productDescription'],
                        $sd['SlNo'],
                        $sd['GodownId'],
                        $sd['RackId'],
                        $sd['currentQty'],
                        $request->branchId,
                        $physical->CreatedDate,
                        $physical->CreatedUser,
                        $modifiedDate, // ModifiedDate
                        $request->ModifiedUser  // ModifiedUser
                    ]);


                    // add StockPosting
                    $qty = $sd['qty'];
                    $currentQty = $sd['qty'] * $sd['ConversionFactor'];
                    $oldStock = $sd['currentQty'];

                    $inward = 0;
                    $outward = 0;

                    if ($qty == 0) {
                        if ($oldStock > 0) {
                            $inward = 0;
                            $outward = $oldStock;
                        } elseif ($oldStock < 0) {
                            $inward = -$oldStock;
                            $outward = 0;
                        } else {
                            $inward = 0;
                            $outward = 0;
                        }
                    } else {
                        if ($currentQty >= 0) {
                            if ($oldStock >= 0) {
                                $balance = $currentQty - $oldStock;
                                if ($balance >= 0) {
                                    $inward = $balance;
                                    $outward = 0;
                                } else {
                                    $inward = 0;
                                    $outward = -$balance;
                                }
                            } else {
                                $inward = (-$oldStock + $currentQty);
                                $outward = 0;
                            }
                        } else {
                            if ($oldStock >= 0) {
                                $inward = 0;
                                $outward = (-$currentQty + $oldStock);
                            } else {
                                $balance = -$currentQty + $oldStock;
                                if ($balance >= 0) {
                                    $inward = 0;
                                    $outward = $balance;
                                } else {
                                    $inward = -$balance;
                                    $outward = 0;
                                }
                            }
                        }
                    }

                    $result = DB::select('SELECT * FROM insert_stock_posting(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                        $physical->date,           // date
                        'Physical Stock',        // voucherType
                        $physical->physicalStockMasterId,  // voucherNo
                        $sd['productCode'],     // productCode
                        $request->BatchId,     // batchId
                        $sd['GodownId'],        // godownId
                        $sd['RackId'],          // rackId
                        $sd['qty'],            // VoucherQty
                        $sd['unitId'],          // VoucherUnitId
                        $sd['rate'],            // voucherRate
                        $inward,                      // inwardQty
                        $outward,            // outwardQty
                        $sd['rate'],            // rate
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
                'Physical Stock Update',
                'Physical Stock',
                'UPDATED',
                $physical->branchId,
                $request->ModifiedUser ?? 'System',
                $physical->physicalStockMasterId,
                $physical->voucherNo ?? null,
                $physical->date,
                $oldValue,
                $physical->totalAmount
            );

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Physical Stock Updated Successfully'
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
     * Delete Physical Stock
     */
    public function destroy($id)
    {
        $physical = PhysicalStockMaster::find($id);

        if (!$physical) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Physical Stock Not Found',
            ], 404);
        }
        $oldValue = $physical->totalAmount;

        DB::beginTransaction();
        try {
            DB::select('SELECT delete_physicalstock_details1_byid(?)', [$id]);

            DB::statement('SELECT public.delete_physical_stock_master_byid(?)', [$id]);

            DB::select('SELECT delete_stock_posting(?, ?)', [
                'Physical Stock', // voucher_type
                $physical->physicalStockMasterId // voucherNo
            ]);
            ActivityHelper::logActivity(
                auth()->id() ?? 0,
                'Physical Stock Delete',
                'Physical Stock',
                'DELETED',
                $physical->branchId,
                $physical->ModifiedUser ?? 'System',
                $physical->physicalStockMasterId,
                $physical->voucherNo ?? null,
                $physical->date,
                $oldValue,
                0
            );
            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Physical Stock Deleted Successfully',
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

    public function physicalStockReport(Request $request)
    {
        try {

            $data = DB::select(
                'SELECT * FROM public.physical_stock_register_fill(
                ?::timestamp,
                ?::timestamp,
                ?::integer,
                ?::varchar,
                ?::varchar
            )',
                [
                    $request->fromdate,
                    $request->todate,
                    $request->branchId,
                    $request->userId ?? null,
                    $request->mode ?? 'Detailed'   // ✅ ADDED MODE
                ]
            );

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Physical Stock Report fetched successfully',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => 500,
                'error'  => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function allInventoryApis(Request $request)
    {
        try {

            $voucherType = $request->input('voucherType', 'Sales Invoice');
            $branchId = $request->input('branchId', null);
            $yearId = $request->input('yearId', null);

            if (!$branchId) {
                return response()->json([
                    'status' => 400,
                    'error'  => true,
                    'message' => 'Branch ID is required',
                    'data'   => []
                ], 400);
            }

            $codeData = voucherNoGeneration($voucherType, $branchId, $yearId);
            $taxMaster = DB::select('SELECT * FROM public.get_all_tax_master() ORDER BY "taxName" ASC');
            $employees = DB::select('SELECT * FROM public.get_all_employee()');
            $branches = DB::select('SELECT * FROM public.get_all_branch()');
            $currencies = DB::select('SELECT * FROM public.get_all_currency()');
            $godowns = DB::select('SELECT * FROM public.get_all_godown(?)', [$branchId]);
            // Map logo path to full URL
            $branches = array_map(function ($branch) {
                if (!empty($branch->logo)) {
                    $branch->logoUrl = asset('storage/' . $branch->logo);
                } else {
                    $branch->logoUrl = null;
                }
                return $branch;
            }, $branches);

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Inventory Data',
                'data' => [
                    'voucherdata'   => [
                        'table_pk'        => $codeData['table_pk'],
                        'voucherCode'    => $codeData['voucherCode'],
                        'voucherNo'       => $codeData['voucherNo'],
                        'suffixPrefixId'  => $codeData['suffixPrefixId'],
                    ],
                    'taxMaster'  => $taxMaster,
                    'employees'  => $employees,
                    'branches'   => $branches,
                    'currencies' => $currencies,
                    'godowns'    => $godowns,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}