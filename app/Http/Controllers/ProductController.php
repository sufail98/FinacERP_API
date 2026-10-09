<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockPosting;
use App\Models\UnitConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Imports\ProductImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * List Product
     */
    public function index(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        // Read query params
        $limit = max((int) $request->query('limit', 10), 1);
        $page  = max((int) $request->query('page', 1), 1);

        $search = $request->query('search');
        $branchId = $request->query('branchId');

        $query = Product::with([
            'unitConversions.unit',
            'salesPrices',
            'group1:groupId,groupName',
            'group2:groupId,groupName',
            'group3:groupId,groupName',
            'group4:groupId,groupName',
            'brand:brandId,brandName'
        ])->orderBy('group1Id', 'asc')
            ->orderBy('productName', 'asc');

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('productCode', 'ilike', "%{$search}%")
                    ->orWhere('productName', 'ilike', "%{$search}%")
                    ->orWhereHas('unitConversions', function ($uq) use ($search) {
                        $uq->where('barcode', 'ilike', "%{$search}%");
                    });
            });
        }

        // Branch filter
        if ($branchId) {
            $query->where('branchId', $branchId);
        }

        $total = $query->count();

        $products = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $formatted = $products->flatMap(function ($product) {

            $getSalesPrice = function ($unitId) use ($product) {
                $price = optional($product->salesPrices->firstWhere('unitId', $unitId))->salesPrice;
                return is_array($price) ? ($price[0] ?? null) : $price;
            };

            $safeArray = fn($val) =>
            is_array($val) ? array_values(array_map('strval', $val))
                : ($val !== null ? [(string) $val] : []);
            // Determine units: if no conversions, use default unit
            $units = $product->unitConversions->isEmpty()
                ? [(object)['unitId' => $product->unitId, 'unitName' => $product->unit->UnitName ?? null, 'barcode' => $product->barcode]]
                : $product->unitConversions;

            return collect($units)->map(function ($conv) use ($product, $getSalesPrice, $safeArray) {
                $unitId = $conv->unitId ?? $product->unitId;

                return [
                    'productId'        => $product->productId,
                    'productCode'      => $product->productCode,
                    'productName'      => $product->productName,
                    'group1Id'         => $product->group1Id,
                    'group2Id'         => $product->group2Id,
                    'group3Id'         => $product->group3Id,
                    'group4Id'         => $product->group4Id,
                    'groupCode'        => $product->groupCode,
                    'category'         => $product->category,
                    'brandId'          => $product->brandId,
                    'unitId'           => $unitId,
                    'unitName'         => $conv->unit->UnitName ?? null,
                    'barcode'          => $conv->barcode ?? null,
                    'salesTaxes'       => $product->salesTaxes,
                    'purchaseTaxes'    => $product->purchaseTaxes,
                    'allowBatch'       => (bool) $product->allowBatch,
                    'partNo'           => $product->partNo,
                    'productImage'     => $product->productImage,
                    'purchaseRate'     => $product->purchaseRate,
                    'purchaseRatePer'  => $product->PurchaseRatePer,
                    'minimumStock'     => $product->minimumStock,
                    'maximumStock'     => $product->maximumStock,
                    'reorderLevel'     => $product->reorderLevel,
                    'openingStock'     => $product->openingStock,
                    'fixedSalesRate'   => $product->fixedSalesRate,
                    'avgCost'          => $product->avgCost,
                    'avgRate'          => $product->avgRate,
                    'fifoRate'         => $product->fifoRate,
                    'fifoValue'        => $product->fifoValue,
                    'bom'              => (bool) $product->bom,
                    'active'           => (bool) $product->active,
                    'showReminder'     => (bool) $product->showReminder,
                    'ShowExpiry'       => (bool) $product->ShowExpiry,
                    'ExpiryDays'       => $product->ExpiryDays,
                    'Ingredients'      => $safeArray($product->Ingredients),
                    'NutritionFact'    => $product->NutritionFact,
                    'NutritionName'    => $product->NutritionName,
                    'NutritionDetails' => $safeArray($product->NutritionDetails),

                    // ✅ SALES TAX (array-safe)
                    // 'salesTaxes' => collect($product->salesTaxId)->map(function ($tax) {
                    //     return [
                    //         'taxName' => $tax['taxName'] ?? null,
                    //         'taxRate' => $tax['taxRate'] ?? null,
                    //     ];
                    // }),

                    // // ✅ PURCHASE TAX (array-safe)
                    // 'purchaseTaxes' => collect($product->PurchaseTaxId)->map(function ($tax) {
                    //     return [
                    //         'taxName' => $tax['taxName'] ?? null,
                    //         'taxRate' => $tax['taxRate'] ?? null,
                    //     ];
                    // }),

                    'salesPrice'       => $getSalesPrice($unitId),
                    'taxType'          => $product->taxType,
                    'narration'        => $product->narration,
                    'branchId'         => $product->branchId,
                    'EnableSales'      => (bool) $product->EnableSales,
                    'EnablePurchase'   => (bool) $product->EnablePurchase,
                    'EnableInventory'  => (bool) $product->EnableInventory,
                    'CustomerLeadTime' => $product->CustomerLeadTime,
                    'favourite'        => (bool) $product->favourite,
                    'pointOfSale'      => (bool) $product->pointOfSale,
                    'main-group'       => $product->groupCode,
                    'group1Name'       => optional($product->group1)->groupName,
                    'group2Name'       => optional($product->group2)->groupName,
                    'group3Name'       => optional($product->group3)->groupName,
                    'group4Name'       => optional($product->group4)->groupName,
                    'brand'            => optional($product->brand)->brandName,
                    'location'         => $safeArray($product->Location),
                    'AlternativeNo'    => $safeArray($product->AlternativeNo),
                    'CreatedDate'      => $product->CreatedDate,
                    'CreatedUser'      => $product->CreatedUser,
                    'ModifiedDate'     => $product->ModifiedDate,
                    'ModifiedUser'     => $product->ModifiedUser,
                ];
            });
        });

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Products Retrieved Successfully',
            'data'    => $formatted,
            'meta'    => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($total / $limit),
            ]
        ]);
    }



    public function productsGridFill(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        try {
            $type = $request->query('type'); // sales | purchase | inventory
            $branchId = $request->query('branchId');

            $products = Product::with([
                'unit:unitId,UnitName',
                'unitConversions:productCode,unitId,conversionRate,barcode',
                'unitConversions.unit:unitId,UnitName',
                // 'salesPrices'
                'salesPrices' => function ($q) use ($branchId) {
                    if ($branchId) {
                        $q->where('branchId', $branchId);
                    }
                }
            ])
                ->when($type === 'sales', function ($q) {
                    $q->where('EnableSales', true);
                })
                ->when($type === 'purchase', function ($q) {
                    $q->where('EnablePurchase', true);
                })
                ->when($type === 'inventory', function ($q) {
                    $q->where('EnableInventory', true);
                })
                ->orderBy('productName', 'asc')
                ->get();

            $data = $products->flatMap(function ($product) {

                // -------------------------------
                // Build UNITS array
                // -------------------------------
                $units = collect();

                if ($product->unit) {
                    $units->push([
                        'unitId'   => $product->unit->unitId,
                        'unitName' => $product->unit->UnitName,
                    ]);
                }

                foreach ($product->unitConversions as $conv) {
                    if ($conv->unit) {
                        $units->push([
                            'unitId'   => $conv->unit->unitId,
                            'unitName' => $conv->unit->UnitName,
                        ]);
                    }
                }

                $units = $units->unique('unitId')->values();

                // $salesTaxes = is_array($product->salesTaxId)
                //     ? array_values($product->salesTaxId)
                //     : ($product->salesTaxId !== null ? [(string) $product->salesTaxId] : []);

                // $purchaseTaxes = is_array($product->PurchaseTaxId)
                //     ? array_values($product->PurchaseTaxId)
                //     : ($product->PurchaseTaxId !== null ? [(string) $product->PurchaseTaxId] : []);

                $salesTaxes = $product->salesTaxes;
                $purchaseTaxes = $product->purchaseTaxes;

                // If NO unit conversions → single row
                if ($product->unitConversions->isEmpty()) {
                    $salesPrice = optional($product->salesPrices->firstWhere('unitId', $product->unitId))->salesPrice;
                    $salesRow = $product->salesPrices->firstWhere('unitId', $product->unitId);
                    $maximumSellingPrice = optional($salesRow)->amount;
                    $lowestSellingPrice = optional($salesRow)->lowestSellingPrice;

                    return [[
                        'productId'        => $product->productId,
                        'productCode'      => $product->productCode,
                        'productName'      => $product->productName,
                        'productNameArb'   => $product->productNameArb,
                        'group1Id'         => $product->group1Id,
                        'group2Id'         => $product->group2Id,
                        'group3Id'         => $product->group3Id,
                        'group4Id'         => $product->group4Id,
                        'groupCode'        => $product->groupCode,
                        'category'         => $product->category,
                        'brandId'          => $product->brandId,
                        'baseunitId'       => $product->unitId,
                        'unitId'           => $product->unit->unitId ?? null,
                        'unitName'         => $product->unit->UnitName ?? null,
                        'barcode'          => $product->barcode,
                        'allowBatch'       => (bool) $product->allowBatch,
                        'partNo'           => $product->partNo,
                        'productImage'     => $product->productImage,
                        'purchaseRate'     => $product->purchaseRate,
                        'purchaseRatePer'  => $product->PurchaseRatePer,
                        'minimumStock'     => $product->minimumStock,
                        'maximumStock'     => $product->maximumStock,
                        'reorderLevel'     => $product->reorderLevel,
                        'openingStock'     => $product->openingStock,
                        'fixedSalesRate'   => $product->fixedSalesRate,
                        'avgCost'          => $product->avgCost,
                        'avgRate'          => $product->avgRate,
                        'fifoRate'         => $product->fifoRate,
                        'fifoValue'        => $product->fifoValue,
                        'bom'              => (bool) $product->bom,
                        'active'           => (bool) $product->active,
                        'showReminder'     => (bool) $product->showReminder,
                        'ShowExpiry'       => (bool) $product->ShowExpiry,
                        'ExpiryDays'       => $product->ExpiryDays,
                        'Ingredients'      => $product->Ingredients,
                        'NutritionFact'    => $product->NutritionFact,
                        'NutritionName'    => $product->NutritionName,
                        'NutritionDetails' => $product->NutritionDetails,
                        'salesTaxes'       => $salesTaxes,
                        'purchaseTaxes'    => $purchaseTaxes,
                        'salesPrice'       => $product->salesPrices,
                        'taxType'          => $product->taxType,
                        'narration'        => $product->narration,
                        'branchId'         => $product->branchId,
                        'EnableSales'      => $product->EnableSales,
                        'EnablePurchase'   => $product->EnablePurchase,
                        'EnableInventory'  => $product->EnableInventory,
                        'CustomerLeadTime' => $product->CustomerLeadTime,
                        'favourite'        => $product->favourite,
                        'pointOfSale'      => $product->pointOfSale,
                        'CreatedDate'      => $product->CreatedDate,
                        'CreatedUser'      => $product->CreatedUser,
                        'ModifiedDate'     => $product->ModifiedDate,
                        'ModifiedUser'     => $product->ModifiedUser,
                        'conversionRate'   => 1,
                        'units'            => $units,
                        'maximumSellingPrice' => $maximumSellingPrice,
                        'lowestSellingPrice'  => $lowestSellingPrice,
                    ]];
                }

                // Multiple unit conversions → multiple rows
                return $product->unitConversions->map(function ($conv) use ($product, $units, $salesTaxes, $purchaseTaxes) {

                    $salesPrice = optional($product->salesPrices->firstWhere('unitId', $conv->unitId))->salesPrice;
                    $salesRow = $product->salesPrices->firstWhere('unitId', $conv->unitId);
                    $maximumSellingPrice = optional($salesRow)->amount;
                    $lowestSellingPrice = optional($salesRow)->lowestSellingPrice;

                    return [
                        'productId'        => $product->productId,
                        'productCode'      => $product->productCode,
                        'productName'      => $product->productName,
                        'productNameArb'   => $product->productNameArb,
                        'group1Id'         => $product->group1Id,
                        'group2Id'         => $product->group2Id,
                        'group3Id'         => $product->group3Id,
                        'group4Id'         => $product->group4Id,
                        'groupCode'        => $product->groupCode,
                        'category'         => $product->category,
                        'brandId'          => $product->brandId,
                        'baseunitId'       => $product->unitId,
                        'unitId'           => $conv->unitId,
                        'unitName'         => $conv->unit->UnitName ?? null,
                        'barcode'          => $conv->barcode,
                        'allowBatch'       => (bool) $product->allowBatch,
                        'partNo'           => $product->partNo,
                        'productImage'     => $product->productImage,
                        'purchaseRate'     => $product->purchaseRate,
                        'purchaseRatePer'  => $product->PurchaseRatePer,
                        'minimumStock'     => $product->minimumStock,
                        'maximumStock'     => $product->maximumStock,
                        'reorderLevel'     => $product->reorderLevel,
                        'openingStock'     => $product->openingStock,
                        'fixedSalesRate'   => $product->fixedSalesRate,
                        'avgCost'          => $product->avgCost,
                        'avgRate'          => $product->avgRate,
                        'fifoRate'         => $product->fifoRate,
                        'fifoValue'        => $product->fifoValue,
                        'bom'              => (bool) $product->bom,
                        'active'           => (bool) $product->active,
                        'showReminder'     => (bool) $product->showReminder,
                        'ShowExpiry'       => (bool) $product->ShowExpiry,
                        'ExpiryDays'       => $product->ExpiryDays,
                        'Ingredients'      => $product->Ingredients,
                        'NutritionFact'    => $product->NutritionFact,
                        'NutritionName'    => $product->NutritionName,
                        'NutritionDetails' => $product->NutritionDetails,
                        'salesTaxes'       => $salesTaxes,
                        'purchaseTaxes'    => $purchaseTaxes,
                        'salesPrice'       => $product->salesPrices,
                        'taxType'          => $product->taxType,
                        'narration'        => $product->narration,
                        'branchId'         => $product->branchId,
                        'EnableSales'      => $product->EnableSales,
                        'EnablePurchase'   => $product->EnablePurchase,
                        'EnableInventory'  => $product->EnableInventory,
                        'CustomerLeadTime' => $product->CustomerLeadTime,
                        'favourite'        => $product->favourite,
                        'pointOfSale'      => $product->pointOfSale,
                        'CreatedDate'      => $product->CreatedDate,
                        'CreatedUser'      => $product->CreatedUser,
                        'ModifiedDate'     => $product->ModifiedDate,
                        'ModifiedUser'     => $product->ModifiedUser,
                        'conversionRate'   => $conv->conversionRate,
                        'units'            => $units,
                        'maximumSellingPrice' => $maximumSellingPrice,
                        'lowestSellingPrice'  => $lowestSellingPrice,
                    ];
                });
            });

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Products Retrieved Successfully',
                'data'    => $data->values()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function productsGridFillwithstock(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        try {
            $type = $request->query('type'); // sales | purchase | inventory
            $branchId = $request->query('branchId');

            $products = Product::with([
                'unit:unitId,UnitName',
                'unitConversions:productCode,unitId,conversionRate,barcode',
                'unitConversions.unit:unitId,UnitName',
                'salesPrices' => function ($q) use ($branchId) {
                    if ($branchId) {
                        $q->where('branchId', $branchId);
                    }
                }
            ])
                ->when($type === 'sales', function ($q) {
                    $q->where('EnableSales', true);
                })
                ->when($type === 'purchase', function ($q) {
                    $q->where('EnablePurchase', true);
                })
                ->when($type === 'inventory', function ($q) {
                    $q->where('EnableInventory', true);
                })
                ->orderBy('productName', 'asc')
                ->get();

            // ---------------------------------------------------
            // Get stock godown-wise for all products in one query
            // ---------------------------------------------------
            $productCodes = $products->pluck('productCode')->filter()->unique()->values()->toArray();

            $stockRows = DB::table('tbl_StockPosting as sp')
                ->leftJoin('tbl_Godown as g', 'g.GodownId', '=', 'sp.godownId')
                // ->when($branchId, function ($q) use ($branchId) {
                //     $q->where('sp.branchId', $branchId);
                // })
                ->whereIn('sp.productCode', $productCodes)
                ->groupBy('sp.productCode', 'sp.godownId', 'g.GodownName')
                ->select(
                    'sp.productCode',
                    'sp.godownId',
                    DB::raw('g."GodownName" as name'),
                    DB::raw('COALESCE(SUM(COALESCE(sp."inwardQty",0) - COALESCE(sp."outwardQty",0)),0) as "currentStock"')
                )
                ->get();

            $stockMap = $stockRows
                ->groupBy('productCode')
                ->map(function ($rows) {
                    return $rows->map(function ($row) {
                        return [
                            'godownId'     => $row->godownId,
                            'name'         => $row->name,
                            'currentStock' => (float) $row->currentStock,
                        ];
                    })->values()->toArray();
                });

            $data = $products->flatMap(function ($product) use ($stockMap) {

                $units = collect();

                if ($product->unit) {
                    $units->push([
                        'unitId'   => $product->unit->unitId,
                        'unitName' => $product->unit->UnitName,
                    ]);
                }

                foreach ($product->unitConversions as $conv) {
                    if ($conv->unit) {
                        $units->push([
                            'unitId'   => $conv->unit->unitId,
                            'unitName' => $conv->unit->UnitName,
                        ]);
                    }
                }

                $units = $units->unique('unitId')->values();

                $salesTaxes = $product->salesTaxes;
                $purchaseTaxes = $product->purchaseTaxes;

                // No unit conversion
                if ($product->unitConversions->isEmpty()) {
                    $salesRow = $product->salesPrices->firstWhere('unitId', $product->unitId);
                    $maximumSellingPrice = optional($salesRow)->amount;
                    $lowestSellingPrice = optional($salesRow)->lowestSellingPrice;

                    return [[
                        'productId'        => $product->productId,
                        'productCode'      => $product->productCode,
                        'stock'            => $stockMap[$product->productCode] ?? [],
                        'productName'      => $product->productName,
                        'productNameArb'   => $product->productNameArb,
                        'group1Id'         => $product->group1Id,
                        'group2Id'         => $product->group2Id,
                        'group3Id'         => $product->group3Id,
                        'group4Id'         => $product->group4Id,
                        'groupCode'        => $product->groupCode,
                        'category'         => $product->category,
                        'brandId'          => $product->brandId,
                        'baseunitId'       => $product->unitId,
                        'unitId'           => $product->unit->unitId ?? null,
                        'unitName'         => $product->unit->UnitName ?? null,
                        'barcode'          => $product->barcode,
                        'allowBatch'       => (bool) $product->allowBatch,
                        'partNo'           => $product->partNo,
                        'productImage'     => $product->productImage,
                        'purchaseRate'     => $product->purchaseRate,
                        'purchaseRatePer'  => $product->PurchaseRatePer,
                        'minimumStock'     => $product->minimumStock,
                        'maximumStock'     => $product->maximumStock,
                        'reorderLevel'     => $product->reorderLevel,
                        'openingStock'     => $product->openingStock,
                        'fixedSalesRate'   => $product->fixedSalesRate,
                        'avgCost'          => $product->avgCost,
                        'avgRate'          => $product->avgRate,
                        'fifoRate'         => $product->fifoRate,
                        'fifoValue'        => $product->fifoValue,
                        'bom'              => (bool) $product->bom,
                        'active'           => (bool) $product->active,
                        'showReminder'     => (bool) $product->showReminder,
                        'ShowExpiry'       => (bool) $product->ShowExpiry,
                        'ExpiryDays'       => $product->ExpiryDays,
                        'Ingredients'      => $product->Ingredients,
                        'NutritionFact'    => $product->NutritionFact,
                        'NutritionName'    => $product->NutritionName,
                        'NutritionDetails' => $product->NutritionDetails,
                        'salesTaxes'       => $salesTaxes,
                        'purchaseTaxes'    => $purchaseTaxes,
                        'salesPrice'       => $product->salesPrices,
                        'taxType'          => $product->taxType,
                        'narration'        => $product->narration,
                        'branchId'         => $product->branchId,
                        'EnableSales'      => $product->EnableSales,
                        'EnablePurchase'   => $product->EnablePurchase,
                        'EnableInventory'  => $product->EnableInventory,
                        'CustomerLeadTime' => $product->CustomerLeadTime,
                        'favourite'        => $product->favourite,
                        'pointOfSale'      => $product->pointOfSale,
                        'CreatedDate'      => $product->CreatedDate,
                        'CreatedUser'      => $product->CreatedUser,
                        'ModifiedDate'     => $product->ModifiedDate,
                        'ModifiedUser'     => $product->ModifiedUser,
                        'conversionRate'   => 1,
                        'units'            => $units,
                        'maximumSellingPrice' => $maximumSellingPrice,
                        'lowestSellingPrice'  => $lowestSellingPrice,
                    ]];
                }

                // Multiple unit conversions
                return $product->unitConversions->map(function ($conv) use ($product, $units, $salesTaxes, $purchaseTaxes, $stockMap) {
                    $salesRow = $product->salesPrices->firstWhere('unitId', $conv->unitId);
                    $maximumSellingPrice = optional($salesRow)->amount;
                    $lowestSellingPrice = optional($salesRow)->lowestSellingPrice;

                    return [
                        'productId'        => $product->productId,
                        'productCode'      => $product->productCode,
                        'stock'            => $stockMap[$product->productCode] ?? [],
                        'productName'      => $product->productName,
                        'productNameArb'   => $product->productNameArb,
                        'group1Id'         => $product->group1Id,
                        'group2Id'         => $product->group2Id,
                        'group3Id'         => $product->group3Id,
                        'group4Id'         => $product->group4Id,
                        'groupCode'        => $product->groupCode,
                        'category'         => $product->category,
                        'brandId'          => $product->brandId,
                        'baseunitId'       => $product->unitId,
                        'unitId'           => $conv->unitId,
                        'unitName'         => $conv->unit->UnitName ?? null,
                        'barcode'          => $conv->barcode,
                        'allowBatch'       => (bool) $product->allowBatch,
                        'partNo'           => $product->partNo,
                        'productImage'     => $product->productImage,
                        'purchaseRate'     => $product->purchaseRate,
                        'purchaseRatePer'  => $product->PurchaseRatePer,
                        'minimumStock'     => $product->minimumStock,
                        'maximumStock'     => $product->maximumStock,
                        'reorderLevel'     => $product->reorderLevel,
                        'openingStock'     => $product->openingStock,
                        'fixedSalesRate'   => $product->fixedSalesRate,
                        'avgCost'          => $product->avgCost,
                        'avgRate'          => $product->avgRate,
                        'fifoRate'         => $product->fifoRate,
                        'fifoValue'        => $product->fifoValue,
                        'bom'              => (bool) $product->bom,
                        'active'           => (bool) $product->active,
                        'showReminder'     => (bool) $product->showReminder,
                        'ShowExpiry'       => (bool) $product->ShowExpiry,
                        'ExpiryDays'       => $product->ExpiryDays,
                        'Ingredients'      => $product->Ingredients,
                        'NutritionFact'    => $product->NutritionFact,
                        'NutritionName'    => $product->NutritionName,
                        'NutritionDetails' => $product->NutritionDetails,
                        'salesTaxes'       => $salesTaxes,
                        'purchaseTaxes'    => $purchaseTaxes,
                        'salesPrice'       => $product->salesPrices,
                        'taxType'          => $product->taxType,
                        'narration'        => $product->narration,
                        'branchId'         => $product->branchId,
                        'EnableSales'      => $product->EnableSales,
                        'EnablePurchase'   => $product->EnablePurchase,
                        'EnableInventory'  => $product->EnableInventory,
                        'CustomerLeadTime' => $product->CustomerLeadTime,
                        'favourite'        => $product->favourite,
                        'pointOfSale'      => $product->pointOfSale,
                        'CreatedDate'      => $product->CreatedDate,
                        'CreatedUser'      => $product->CreatedUser,
                        'ModifiedDate'     => $product->ModifiedDate,
                        'ModifiedUser'     => $product->ModifiedUser,
                        'conversionRate'   => $conv->conversionRate,
                        'units'            => $units,
                        'maximumSellingPrice' => $maximumSellingPrice,
                        'lowestSellingPrice'  => $lowestSellingPrice,
                    ];
                });
            });

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Products Retrieved Successfully',
                'data'    => $data->values()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }






    /**
     * Get Product by id
     */
    public function show($id)
    {
        $Product = Product::with([
            'group1:groupId,groupName',
            'group2:groupId,groupName',
            'group3:groupId,groupName',
            'group4:groupId,groupName',
            'brand:brandId,brandName',
            'unit:unitId,UnitName',
            'billOfMaterials',
            'salesPrices',
            'unitConversions'
        ])->find($id);

        if (!$Product) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Product not found',
            ], 404);
        }
        $Product->load([
            'salesPrices' => function ($q) use ($Product) {
                $q->where('branchId', $Product->branchId);
            }
        ]);
        $data = $Product->toArray();
        if (isset($data['sales_prices'])) {
            $data['salesPrices'] = $data['sales_prices'];
            unset($data['sales_prices']);
        }
        $data['salesTaxes'] = $Product->salesTaxes;
        $data['purchaseTaxes'] = $Product->purchaseTaxes;
        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Product retrieved successfully',
            'data' => $data,
        ]);
    }




    /**
     * view Product by id
     */
    public function view($id)
    {
        $Product = Product::with([
            'group1:groupId,groupName',
            'group2:groupId,groupName',
            'group3:groupId,groupName',
            'group4:groupId,groupName',
            'brand:brandId,brandName',
            'unit:unitId,UnitName',
            'billOfMaterials',
            'salesPrices',
            'unitConversions'
        ])->find($id);

        if (!$Product) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Product not found',
            ], 404);
        }

        $data = $Product->toArray();

        if (isset($data['sales_prices'])) {
            $data['salesPrices'] = $data['sales_prices'];
            unset($data['sales_prices']);
        }

        $data['salesTaxes']   = $Product->salesTaxes;
        $data['purchaseTaxes'] = $Product->purchaseTaxes;

        $data['unit_conversions'] = $Product->unitConversions->map(function ($conv) {
            return [
                'unitConversionId' => $conv->unitConversionId,
                'productCode'      => $conv->productCode,
                'unitId'           => $conv->unitId,
                'conversionRate'   => $conv->conversionRate,
                'barcode'          => $conv->barcode,
                'CreatedDate'      => $conv->CreatedDate,
                'CreatedUser'      => $conv->CreatedUser,
                'ModifiedDate'     => $conv->ModifiedDate,
                'ModifiedUser'     => $conv->ModifiedUser,
                'branchId'         => $conv->branchId,
            ];
        });

        // --- Stock Data ---
        $branchId    = request('branchId'); // optional filter from query param
        $productCode = $Product->productCode;

        $stockRows = DB::table('tbl_StockPosting as sp')
            ->leftJoin('tbl_Godown as g', 'g.GodownId', '=', 'sp.godownId')
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('sp.branchId', $branchId);
            })
            ->where('sp.productCode', $productCode)
            ->groupBy('sp.productCode', 'sp.godownId', 'g.GodownName')
            ->select(
                'sp.productCode',
                'sp.godownId',
                DB::raw('g."GodownName" as name'),
                DB::raw('COALESCE(SUM(COALESCE(sp."inwardQty",0) - COALESCE(sp."outwardQty",0)),0) as "currentStock"')
            )
            ->get();

        $data['stock'] = $stockRows->map(function ($row) {
            return [
                'godownId'     => $row->godownId,
                'name'         => $row->name,
                'currentStock' => (float) $row->currentStock,
            ];
        })->values()->toArray();

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Product retrieved successfully',
            'data'    => $data,
        ]);
    }



    /**
     * Create Product
     */
    public function store(Request $request)
    {
        // Log::info('Product store request data:', $request->all());
        DB::beginTransaction();
        try {
            // Check for duplicate productName
            if ($request->has('productName') && Product::whereRaw('LOWER("productName") = ?', [strtolower($request->productName)])->exists()) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Product name already exists',
                ], 409);
            }

            // Check for duplicate productCode
            if ($request->has('productCode') && Product::whereRaw('LOWER("productCode") = ?', [strtolower($request->productCode)])->exists()) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Product Code already exists',
                ], 409);
            }

            // Check for duplicate partNo
            if ($request->has('partNo') && Product::whereRaw('LOWER("partNo") = ?', [strtolower($request->partNo)])->exists()) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Part No already exists',
                ], 409);
            }

            // Insert Product
            $Product = new Product($request->only([
                'productCode',
                'productName',
                'group1Id',
                'group2Id',
                'group3Id',
                'group4Id',
                'productNameArb',
                'Location',
                'AlternativeNo',
                'brandId',
                'unitId',
                'allowBatch',
                'partNo',
                'purchaseRate',

                'minimumStock',
                'maximumStock',
                'reorderLevel',
                'openingStock',
                'taxType',
                'narration',
                'bom',
                'active',
                'showReminder',
                'category',
                'groupCode',
                'ShowExpiry',
                'ExpiryDays',
                'PurchaseRatePer',
                'Ingredients',
                'NutritionFact',
                'NutritionName',
                'NutritionDetails',
                'branchId',
                'EnableSales',
                'EnablePurchase',
                'EnableInventory',
                'CustomerLeadTime',
                'favourite',
                'pointOfSale',
                'CreatedUser',
            ]));
            // dd($request);
            if ($request->filled('salesTaxId')) {
                $Product->salesTaxId = array_map(
                    'strval',
                    is_array($request->salesTaxId)
                        ? $request->salesTaxId
                        : [$request->salesTaxId]
                );
            }

            if ($request->filled('PurchaseTaxId')) {
                $Product->PurchaseTaxId = array_map(
                    'strval',
                    is_array($request->PurchaseTaxId)
                        ? $request->PurchaseTaxId
                        : [$request->PurchaseTaxId]
                );
            }

            //  File upload
            if ($request->hasFile('productImage')) {
                $file = $request->file('productImage');
                $extension = $file->getClientOriginalExtension();

                $filename = $request->productCode . '.' . $extension;
                // $relativePath = 'product_images/' . $filename;
                // Storage::disk('public')->putFileAs('product_images', $file, $filename);
                // $Product->productImage = $relativePath;


                $destination = public_path('storage/product_images');
                if (!file_exists($destination)) {
                    mkdir($destination, 0775, true);
                }

                $file->move($destination, $filename);

                $Product->productImage = 'product_images/' . $filename;
            }

            $Product->CreatedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $Product->save();

            // Insert into tbl_ProductSalesPrice
            // Insert into tbl_ProductSalesPrice
            if ($request->has('salesPrices')) {
                foreach ($request->salesPrices as $sp) {
                    DB::table('tbl_ProductSalesPrice')->insert([
                        'productCode'         => $request->productCode,
                        'unitId'              => $sp['unitId'],
                        'amount'              => $sp['amount'] ?? 0,
                        'discPercentage'      => $sp['discPercentage'] ?? 0,
                        'discAmount'          => $sp['discAmount'] ?? 0,
                        'salesPrice'          => $sp['salesPrice'] ?? 0,
                        'costPrice'           => $sp['costPrice'] ?? 0,
                        'marginPercentage'    => $sp['marginPercentage'] ?? 0,
                        'MaximumSellingPrice' => $sp['MaximumSellingPrice'] ?? 0,
                        'branchId'            => $sp['branchId'],
                        'PricingLevelId'      => $sp['PricingLevelId'] ?? null,
                        'lowestSellingPrice'  => $sp['lowestSellingPrice'] ?? 0,
                        'CreatedDate'         => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'CreatedUser'         => $request->CreatedUser ?? null,
                    ]);
                }
            }

            // Insert into tbl_UnitConversion
            if ($request->has('unitConversions')) {
                foreach ($request->unitConversions as $uc) {

                    // Check if barcode already exists
                    if (!empty($uc['barcode'])) {
                        $existingBarcode = DB::table('tbl_UnitConversion')
                            ->whereRaw('LOWER("barcode") = LOWER(?)', [$uc['barcode']])
                            ->exists();

                        if ($existingBarcode) {
                            DB::rollBack(); // rollback before returning
                            return response()->json([
                                'status' => 409,
                                'error' => true,
                                'message' => 'Barcode Already Exists: ' . $uc['barcode'],
                            ], 409);
                        }
                    }

                    DB::table('tbl_UnitConversion')->insert([
                        'unitConversionId' => (UnitConversion::max('unitConversionId') ?? 0) + 1,
                        'productCode'    => $request->productCode,
                        'unitId'         => $uc['unitId'],
                        'conversionRate' => $uc['conversionRate'],
                        'barcode'        => $uc['barcode'] ?? null,
                        'branchId'       => $request->branchId,
                        'CreatedDate'    => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'CreatedUser'    => $request->CreatedUser ?? null,
                    ]);
                }
            }

            // Increment nextNumber for the group
            if ($request->has('groupCode')) {
                DB::table('tbl_ProductMainGroup')
                    ->where('groupCode', $request->groupCode)
                    ->increment('nextNumber', 1);
            }

            // Insert into tbl_bom
            if ($request->bom == true) {
                if ($request->has('billOfMaterials')) {
                    foreach ($request->billOfMaterials as $bom) {
                        DB::table('tbl_BOM')->insert([
                            'productCode'    => $request->productCode,
                            'rowMaterialId'  => $bom['rowMaterialId'],
                            'UnitId'         => $bom['UnitId'],
                            'Quantity'       => $bom['Quantity'],
                            'branchId'          => $request->branchId,
                            'CreatedDate'    => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                            'CreatedUser'    => $request->CreatedUser ?? null,
                        ]);
                    }
                }
            }

            // add StockPosting
            if ($Product->openingStock > 0) {
                StockPosting::addPosting([
                    'date'           => $request->activeFinancialYear_fromDate,
                    'voucherType'    => 'Opening Stock',
                    'voucherNo'      => $Product->productCode,
                    'productCode'    => $Product->productCode,
                    'batchId'        => 0,
                    'godownId'       => 1,
                    'rackId'         => 1,
                    'VoucherQty'     => $Product->openingStock,
                    'VoucherUnitId'  => $Product->unitId,
                    'voucherRate'    => $Product->purchaseRate,
                    'inwardQty'      => $Product->openingStock,
                    'outwardQty'     => 0,
                    'rate'           => $Product->purchaseRate,
                    'unitId'         => $Product->unitId,
                    'referenceNo'    => null,
                    'referenceType'  => null,
                    'branchId'       => $Product->branchId,
                    'CreatedUser'    => $Product->CreatedUser,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 201,
                'error' => false,
                'message' => 'Product Created Successfully'
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
     * Update Product
     */
    public function update(Request $request, $id)
    {
        $Product = Product::find($id);

        if (!$Product) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Product not found',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Check for duplicate productName
            if (
                $request->has('productName') &&
                Product::whereRaw('LOWER("productName") = ?', [strtolower($request->productName)])
                ->where('productCode', '!=', $id)
                ->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Product name already exists',
                ], 409);
            }

            // Check for duplicate partNo
            if (
                $request->has('partNo') && Product::whereRaw('LOWER("partNo") = ?', [strtolower($request->partNo)])
                ->where('productCode', '!=', $id)
                ->exists()
            ) {
                return response()->json([
                    'status' => 409,
                    'error' => true,
                    'message' => 'Part No already exists',
                ], 409);
            }

            // ✅ Do NOT allow updating productCode
            // Removed duplicate check for productCode

            // Update product fields (excluding productCode)
            $Product->fill($request->only([
                'productName',
                'group1Id',
                'group2Id',
                'group3Id',
                'group4Id',
                'productNameArb',
                'Location',
                'AlternativeNo',
                'brandId',
                'unitId',
                'allowBatch',
                'partNo',
                'purchaseRate',
                'minimumStock',
                'maximumStock',
                'reorderLevel',
                'openingStock',
                'taxType',
                'narration',
                'bom',
                'active',
                'showReminder',
                'category',
                'groupCode',
                'ShowExpiry',
                'ExpiryDays',
                'PurchaseRatePer',
                'Ingredients',
                'NutritionFact',
                'NutritionName',
                'NutritionDetails',
                'branchId',
                'EnableSales',
                'EnablePurchase',
                'EnableInventory',
                'CustomerLeadTime',
                'favourite',
                'pointOfSale',
                'ModifiedUser'
            ]));

            // ✅ Normalize taxes → store as JSON array ["1","2"]
            // SALES TAX
            if ($request->has('salesTaxId')) {

                $salesTaxIds = is_array($request->salesTaxId)
                    ? $request->salesTaxId
                    : [$request->salesTaxId];

                // extract only ID values & cast to string
                $Product->salesTaxId = array_values(
                    array_filter(
                        array_map(function ($item) {
                            // if object/array comes, try to get id
                            if (is_array($item) && isset($item['taxId'])) {
                                return (string) $item['taxId'];
                            }

                            if (is_object($item) && isset($item->taxId)) {
                                return (string) $item->taxId;
                            }

                            return is_null($item) ? null : (string) $item;
                        }, $salesTaxIds)
                    )
                );
            }

            // PURCHASE TAX
            if ($request->has('PurchaseTaxId')) {

                $purchaseTaxIds = is_array($request->PurchaseTaxId)
                    ? $request->PurchaseTaxId
                    : [$request->PurchaseTaxId];

                $Product->PurchaseTaxId = array_values(
                    array_filter(
                        array_map(function ($item) {
                            if (is_array($item) && isset($item['taxId'])) {
                                return (string) $item['taxId'];
                            }

                            if (is_object($item) && isset($item->taxId)) {
                                return (string) $item->taxId;
                            }

                            return is_null($item) ? null : (string) $item;
                        }, $purchaseTaxIds)
                    )
                );
            }



            // Handle file upload (replace old if new uploaded)
            if ($request->hasFile('productImage')) {
                if (!empty($Product->getRawOriginal('productImage'))) {
                    Storage::disk('public')->delete($Product->getRawOriginal('productImage'));
                }

                $file = $request->file('productImage');
                $extension = $file->getClientOriginalExtension();
                $filename = $Product->productCode . '.' . $extension;

                $destination = public_path('storage/product_images');
                if (!file_exists($destination)) {
                    mkdir($destination, 0775, true);
                }

                $file->move($destination, $filename);
                $Product->productImage = 'product_images/' . $filename;
            }

            $Product->ModifiedDate = now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
            $Product->save();

            // Update sales prices
            // ✅✅ UPDATE SALES PRICES - DELETE ALL, THEN INSERT NEW ✅✅
            if ($request->has('salesPrices')) {

                // STEP 1: DELETE existing sales prices
                DB::table('tbl_ProductSalesPrice')
                    ->where('productCode', $id)
                    ->delete();

                // STEP 2: INSERT updated sales prices
                foreach ($request->salesPrices as $sp) {

                    // Skip deleted rows
                    if (
                        isset($sp['deleted']) &&
                        filter_var($sp['deleted'], FILTER_VALIDATE_BOOLEAN)
                    ) {
                        continue;
                    }

                    DB::table('tbl_ProductSalesPrice')->insert([
                        'productCode'          => $id,
                        'unitId'               => $sp['unitId'],
                        'branchId'             => $sp['branchId'],
                        'amount'               => $sp['amount'] ?? 0,
                        'discPercentage'       => $sp['discPercentage'] ?? 0,
                        'discAmount'           => $sp['discAmount'] ?? 0,
                        'salesPrice'           => $sp['salesPrice'] ?? 0,
                        'costPrice'            => $sp['costPrice'] ?? 0,
                        'marginPercentage'     => $sp['marginPercentage'] ?? 0,
                        'MaximumSellingPrice'   => $sp['MaximumSellingPrice'] ?? 0,
                        'PricingLevelId'       => $sp['PricingLevelId'] ?? null,
                        'lowestSellingPrice'   => $sp['lowestSellingPrice'] ?? 0,
                        'CreatedDate'          => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'CreatedUser'          => $request->ModifiedUser ?? null,
                        'ModifiedDate'         => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'ModifiedUser'         => $request->ModifiedUser ?? null,
                    ]);
                }
            }

            // Update Unit Conversions
            if ($request->has('unitConversions')) {
                DB::table('tbl_UnitConversion')->where('productCode', $id)->delete();

                foreach ($request->unitConversions as $uc) {
                    if (!empty($uc['barcode'])) {
                        $exists = DB::table('tbl_UnitConversion')
                            ->whereRaw('LOWER("barcode") = LOWER(?)', [$uc['barcode']])
                            ->where('productCode', '!=', $id)
                            ->exists();

                        if ($exists) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 409,
                                'error' => true,
                                'message' => 'Barcode Already Exists: ' . $uc['barcode'],
                            ], 409);
                        }
                    }

                    DB::table('tbl_UnitConversion')->insert([
                        'unitConversionId' => (UnitConversion::max('unitConversionId') ?? 0) + 1,
                        'productCode'      => $id,
                        'unitId'           => $uc['unitId'],
                        'conversionRate'   => $uc['conversionRate'],
                        'barcode'          => $uc['barcode'] ?? null,
                        'branchId'         => $request->branchId,
                        'ModifiedDate'     => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'ModifiedUser'     => $request->ModifiedUser ?? null,
                    ]);
                }
            }

            // Update BOM
            if ($Product->bom && $request->has('billOfMaterials')) {
                DB::table('tbl_BOM')->where('productCode', $id)->delete();

                foreach ($request->billOfMaterials as $bom) {
                    DB::table('tbl_BOM')->insert([
                        'productCode'   => $id,
                        'rowMaterialId' => $bom['rowMaterialId'],
                        'UnitId'        => $bom['UnitId'],
                        'Quantity'      => $bom['Quantity'],
                        'branchId'      => $request->branchId,
                        'ModifiedDate'  => now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s'),
                        'ModifiedUser'  => $request->ModifiedUser ?? null,
                    ]);
                }
            }

            // Update StockPosting
            StockPosting::where('voucherType', 'Opening Stock')
                ->where('voucherNo', $id)
                ->where('productCode', $id)
                ->delete();
            if ($Product->openingStock > 0) {
                // StockPosting::where('voucherType', 'Opening Stock')
                //     ->where('voucherNo', $id)
                //     ->where('productCode', $id)
                //     ->delete();

                StockPosting::addPosting([
                    'date'           => $request->activeFinancialYear_fromDate,
                    'voucherType'    => 'Opening Stock',
                    'voucherNo'      => $Product->productCode,
                    'productCode'    => $Product->productCode,
                    'batchId'        => 0,
                    'godownId'       => 1,
                    'rackId'         => 1,
                    'VoucherQty'     => $Product->openingStock,
                    'VoucherUnitId'  => $Product->unitId,
                    'voucherRate'    => $Product->purchaseRate,
                    'inwardQty'      => $Product->openingStock,
                    'outwardQty'     => 0,
                    'rate'           => $Product->purchaseRate,
                    'unitId'         => $Product->unitId,
                    'referenceNo'    => null,
                    'referenceType'  => null,
                    'branchId'       => $Product->branchId,
                    'CreatedUser'    => $Product->CreatedUser,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product updated successfully',
            ]);
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
     * Delete Product
     */
    public function destroy($id)
    {
        $Product = Product::find($id);

        if (!$Product) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Product not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Check if product is used in Sales
            // $existsInSales = DB::table('tbl_SalesDetails')
            // 	->where('productCode', $Product->productCode)
            // 	->exists();

            // if ($existsInSales) {
            // 	return response()->json([
            // 		'status' => 409,
            // 		'error' => true,
            // 		'message' => 'Cannot delete product. It is already used in Sales.',
            // 	], 409);
            // }

            // check if product exists in tbl_ProductSalesPrice or tbl_UnitConversion
            DB::select('SELECT delete_product_salesprice(?)', [$id]);
            DB::select('SELECT delete_unitconversion(?)', [$id]);
            DB::select('SELECT delete_bom(?)', [$id]);

            DB::select('SELECT delete_stock_posting(?, ?)', [
                'Opening Stock', // voucherType
                $Product->productCode  // voucherNo
            ]);

            // Delete the product image file from storage if exists
            if ($Product->getRawOriginal('productImage')) {
                Storage::disk('public')->delete($Product->getRawOriginal('productImage'));
            }

            // Delete product
            $Product->delete();

            DB::commit();

            return response()->json([
                'status' => 200,
                'error' => false,
                'message' => 'Product deleted successfully',
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

    public function searchProducts(Request $request)
    {
        $branchId    = $request->branchId;
        $godownId    = $request->godownId ?? '';
        $isBOM       = filter_var($request->isBOM, FILTER_VALIDATE_BOOLEAN);
        $startText   = $request->startText ?? '';
        $voucherType = $request->voucherType ?? '';

        // Get decimal places for branch
        $decimalPoint = DB::table('tbl_Currency')
            ->where('branchId', $branchId)
            ->value('noOfDecimalPlace');

        // Raw SQL base
        $sql = "
			SELECT
				CASE WHEN u.barcode = '' THEN p.\"productCode\" ELSE u.barcode END AS barcode,
				p.\"partNo\",
				p.\"productCode\",
				p.\"productName\",
				p.\"taxType\",
				t.\"taxId\",
				t.\"taxName\",
				t.\"rate\",
				un.\"unitId\",
				un.\"UnitName\",
				pg1.\"groupName\" AS Subgroup1,
				pg2.\"groupName\" AS Subgroup2,
				pg3.\"groupName\" AS Subgroup3,
				pg4.\"groupName\" AS Subgroup4,
				b.\"brandName\",
				p.\"AlternativeNo\",
				p.\"Location\",
				p.\"purchaseRate\"
		";

        if (in_array($voucherType, ['Sales Invoice', 'SalesR eturn', 'Sales Order', 'Sales Quotation', 'Rejection In'])) {
            $sql .= ",
				CAST(s.\"salesPrice\" AS DECIMAL(24,6)) AS SalesRate
			";
        } elseif ($voucherType === 'Purchase Invoice') {
            $sql .= ",
				CAST(s.\"salesPrice\" AS DECIMAL(24,6)) AS SalesRate,
				CAST(p.\"purchaseRate\" AS VARCHAR) AS BaseCost
			";
        }

        $sql .= "
			FROM \"tbl_Product\" p";
        if (in_array($voucherType, ['Sales Invoice', 'Sales Return', 'Sales Order', 'Sales Quotation', 'Rejection In', 'Purchase Invoice'])) {
            $sql .= "
			LEFT JOIN \"tbl_ProductSalesPrice\" s
				ON p.\"productCode\" = s.\"productCode\"
			AND s.\"PricingLevelId\" = 1
			";
        }
        $sql .= "
			LEFT JOIN \"tbl_Unit\" un ON s.\"unitId\" = un.\"unitId\"
			LEFT JOIN \"tbl_TaxMaster\" t ON t.\"taxId\" = p.\"taxId\"
			LEFT JOIN \"tbl_ProductGroup\" pg1 ON p.\"group1Id\" = pg1.\"groupId\"
			LEFT JOIN \"tbl_ProductGroup\" pg2 ON p.\"group2Id\" = pg2.\"groupId\"
			LEFT JOIN \"tbl_ProductGroup\" pg3 ON p.\"group3Id\" = pg3.\"groupId\"
			LEFT JOIN \"tbl_ProductGroup\" pg4 ON p.\"group4Id\" = pg4.\"groupId\"
			LEFT JOIN \"tbl_UnitConversion\" u
				ON un.\"unitId\" = u.\"unitId\"
			AND p.\"productCode\" = u.\"productCode\"
			LEFT JOIN \"tbl_Brand\" b ON p.\"brandId\" = b.\"brandId\"
		";

        $sql .= "
			WHERE p.\"active\" = 'True'
			AND p.\"branchId\" = :branchId
		";

        if ($isBOM) {
            $sql .= " AND p.\"bom\" = 'true' ";
        }

        if (!empty($startText)) {
            $sql .= " AND p.\"productName\" ILIKE :startText ";
        }

        $query = DB::select($sql, [
            'branchId'  => $branchId,
            'startText' => '%' . $startText . '%',
        ]);

        return response()->json([
            'status' => 200,
            'error'  => false,
            'message' => 'Product retrieved successfully',
            'data'   => $query,
        ]);
    }

    // get unit,unitconversion, salesprice data by productcode
    public function product_unit_sales_details($id)
    {
        $rows = DB::select('SELECT * FROM get_product_unit_sales_details(?)', [$id]);

        if (empty($rows)) {
            return response()->json([
                'status' => 404,
                'error' => true,
                'message' => 'Product Not Found',
            ], 404);
        }

        // Take product info from first row
        $productData = [
            'productcode' => $rows[0]->productcode,
            'productname' => $rows[0]->productname,
            'units'       => [],
        ];

        // Loop through rows and push unit details
        foreach ($rows as $row) {
            $productData['units'][] = [
                'barcode'          => $row->barcode,
                'unitid'           => $row->unitid,
                'unitname'         => $row->unitname,
                'unitconversionid' => $row->unitconversionid,
                'conversionrate'   => $row->conversionrate,
                'salesprice'       => $row->salesprice,
            ];
        }

        return response()->json([
            'status'  => 200,
            'error'   => false,
            'message' => 'Product Details Retrieved Successfully',
            'data'    => $productData,
        ]);
    }

    public function ProductMovement(Request $request)
    {
        try {

            $fromDate    = $request->fromDate;
            $toDate      = $request->toDate;
            $voucherType = $request->voucherType ?? null;
            $productCode = $request->productCode;
            $party       = $request->party ?? null;
            $branchId    = $request->branchId;
            $godownId    = $request->godownId ?? null;
            $voucherType = ($voucherType === 'All' || $voucherType === '') ? null : $voucherType;
            $party       = ($party === '' || $party === 'All') ? null : $party;
            $godownId    = ($godownId === '' || $godownId === 'All') ? null : $godownId;

            $data = DB::select(
                'SELECT * FROM public."product_movement_stock_posting"(?,?,?,?,?,?,?)',
                [
                    $fromDate,
                    $toDate,
                    $voucherType,
                    $productCode,
                    $party,
                    $branchId,
                    $godownId
                ]
            );

            if (empty($data)) {
                return response()->json([
                    'status'  => 200,
                    'error'   => false,
                    'message' => 'No data found for the given filters',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product Movement Stock Report fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function ProductWiseSalesSummaryReport(Request $request)
    {
        try {
            $fromdate = $request->fromdate;
            $todate = $request->todate;
            $branchid = $request->branchid;
            $category = $request->category ?? null;
            $data = DB::select(
                'SELECT * FROM public.productwise_sales_summary_report(:fromdate, :todate, :branchid, :category)',
                [
                    'fromdate' => $fromdate,
                    'todate' => $todate,
                    'branchid' => $branchid,
                    'category' => $category
                ]
            );
            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'productwise sales summary report fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function ProductSearchVocuherWise(Request $request)
    {
        try {

            $branchId     = $request->input('branchId');
            $startDate    = $request->input('startDate');
            $endDate      = $request->input('endDate');
            $productCode  = $request->input('productCode');
            $voucherType  = $request->input('voucherType');
            $groupId      = $request->input('groupId');
            $ledgerId     = $request->input('ledgerId');
            $employeeId   = $request->input('employeeId');
            $startText    = $request->input('startText');

            $query = DB::table('vWStockQuery')
                ->where('branchId', $branchId);

            /* ✅ Date Filter (safe for timestamp) */
            if (!empty($startDate) && !empty($endDate)) {
                $query->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate);
            }

            /* ✅ Product */
            if (!empty($productCode) && $productCode !== 'All') {
                $query->where('productCode', $productCode);
            }

            /* ✅ Voucher Type */
            if (!empty($voucherType) && $voucherType !== 'All') {
                $query->where('voucherType', $voucherType);
            }

            /* ✅ Group (UPDATED COLUMN NAME ✅) */
            if (!empty($groupId) && $groupId !== 'All') {
                $query->where('groupId', $groupId);
            }

            /* ✅ Ledger */
            if (!empty($ledgerId) && $ledgerId !== 'All') {
                $query->where('ledgerId', $ledgerId);
            }

            /* ✅ Employee */
            if (!empty($employeeId) && $employeeId !== 'All') {
                $query->where('employeeId', $employeeId);
            }

            /* ✅ Product Name Search */
            if (!empty($startText)) {
                $query->where('productName', 'ILIKE', '%' . trim($startText) . '%');
            }

            $data = $query
                ->orderByDesc('inwardQty')
                ->get();

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'Product Search Voucher-Wise fetched successfully',
                'data'    => $data
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function SalesHistory(Request $request)
    {


        try {
            $result = DB::select(
                'SELECT * FROM "product_movement_lookup"(:productCode, :branchId, :currencyId, :historyType, :ledgerId, :voucherType)',
                [
                    'productCode' => $request->productCode,
                    'branchId'    => $request->branchId,
                    'currencyId'  => $request->currencyId,
                    'historyType' => 'Sales',
                    'ledgerId'    => $request->ledgerId,
                    'voucherType' => 'Sales Invoice',
                ]
            );

            return response()->json([
                'status' => true,
                'data'   => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error fetching sales history',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    public function PurchaseHistory(Request $request)
    {


        try {
            $result = DB::select(
                'SELECT * FROM "product_movement_lookup"(:productCode, :branchId, :currencyId, :historyType, :ledgerId, :voucherType)',
                [
                    'productCode' => $request->productCode,
                    'branchId'    => $request->branchId,
                    'currencyId'  => $request->currencyId,
                    'historyType' => 'Purchase',
                    'ledgerId'    => $request->ledgerId,
                    'voucherType' => 'Purchase Invoice',
                ]
            );

            return response()->json([
                'status' => true,
                'data'   => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error fetching purchase history',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function getBomData(Request $request)
    {
        try {
            $branchId = $request->input('branchId', null);

            $result = DB::select(
                'SELECT get_products_with_bom(?)',
                [$branchId]
            );

            $data = json_decode($result[0]->get_products_with_bom, true);

            return response()->json([
                'status'  => 200,
                'error'   => false,
                'message' => 'BOM Products fetched successfully',
                'data'    => $data ?? []
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function getProductStock(Request $request)
    {
        try {
            $productCode = $request->productCode;
            $branchId    = $request->branchId;
            $godownId    = $request->godownId ?? null;
            $date        = $request->date ?? null;

            $stock = DB::select(
                "SELECT * FROM public.get_product_stock(?, ?, ?, ?)",
                [
                    $productCode,
                    $branchId,
                    $godownId,
                    $date
                ]
            );

            return response()->json([
                'status' => 200,
                'error'  => false,
                'data'   => $stock
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 500,
                'error'   => true,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function importProducts(Request $request)
    {
        // $request->validate([
        //     'file' => 'required|mimes:xlsx,csv'
        // ]);

        $import = new ProductImport();

        Excel::import($import, $request->file('file'));

        return response()->json([
            'status' => 200,
            'error' => false,
            'message' => 'Bulk Upload Completed',
            'data' => [
                'success_count' => count($import->success),
                'failed_count'  => count($import->failed),
                'success_products' => $import->success,
                'failed_products'  => $import->failed
            ]
        ]);
    }
    public function InvendoryReports(Request $request)
    {
        try {
            $branchId = $request->branchId;

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'branchId is required'
                ], 400);
            }

            // Only coerce to boolean when the key is actually present.
            // Missing keys stay null, matching the SQL function's own defaults.
            $toBool = fn($value) => is_null($value) ? null : filter_var($value, FILTER_VALIDATE_BOOLEAN);

            $result = DB::select("
                SELECT * FROM public.product_search_test(
                    :branchId,
                    :groupId,
                    :productCode,
                    :brandId,
                    :taxId,
                    :taxType,
                    :godownId,
                    :rackId,
                    :startText,
                    :isActive,
                    :isDatewise,
                    :fromDate,
                    :toDate,
                    :isFromReminder,
                    :isMaximum,
                    :isMinimum,
                    :isReorder,
                    :isNegative,
                    :isDamaged,
                    :isFastMoving,
                    :isSlowMoving,
                    :isUnused,
                    :isUsedStock
                )
            ", [
                'branchId'       => $branchId,
                'groupId'        => $request->groupId,
                'productCode'    => $request->productCode,
                'brandId'        => $request->brandId,
                'taxId'          => $request->taxId,
                'taxType'        => $request->taxType,
                'godownId'       => $request->godownId,
                'rackId'         => $request->rackId,
                'startText'      => $request->startText ?? '',
                'isActive'       => $toBool($request->isActive),           // stays null if unset
                'isDatewise'     => $toBool($request->isDatewise) ?? false,
                'fromDate'       => $request->fromDate,
                'toDate'         => $request->toDate,
                'isFromReminder' => $toBool($request->isFromReminder) ?? false,
                'isMaximum'      => $toBool($request->isMaximum) ?? false,
                'isMinimum'      => $toBool($request->isMinimum) ?? false,
                'isReorder'      => $toBool($request->isReorder) ?? false,
                'isNegative'     => $toBool($request->isNegative) ?? false,
                'isDamaged'      => $toBool($request->isDamaged) ?? false,
                'isFastMoving'   => $toBool($request->isFastMoving) ?? false,
                'isSlowMoving'   => $toBool($request->isSlowMoving) ?? false,
                'isUnused'       => $toBool($request->isUnused) ?? false,
                'isUsedStock'    => $toBool($request->isUsedStock) ?? false,
            ]);

            return response()->json([
                'success' => true,
                'count' => count($result),
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}