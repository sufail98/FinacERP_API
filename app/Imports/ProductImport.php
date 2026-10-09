<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\UnitConversion;
use App\Models\StockPosting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection, WithHeadingRow
{
    public $success = [];
    public $failed  = [];

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {

            foreach ($rows as $index => $row) {

                $rowNo = $index + 2;

                try {

                    $productCode = trim($row['product_code'] ?? '');
                    $productName = trim($row['product_name'] ?? '');
                    $mainGroup   = trim($row['product_main_group'] ?? '');
                    $group1Name  = trim($row['product_group1'] ?? '');
                    $unitValue   = trim($row['unit'] ?? '');
                    $branchId    = trim($row['branch'] ?? '');

                    if (!$productCode || !$productName || !$mainGroup || !$unitValue || !$branchId) {
                        throw new \Exception("Mandatory fields missing");
                    }

                    // ✅ Duplicate check
                    if (Product::whereRaw('LOWER("productCode") = LOWER(?)', [$productCode])->exists()) {
                        throw new \Exception("Product Code already exists");
                    }

                    // ✅ UNIT
                    if (is_numeric($unitValue)) {
                        $unit = DB::table('tbl_Unit')->where('unitId', (int)$unitValue)->first();
                    } else {
                        $unit = DB::table('tbl_Unit')
                            ->whereRaw('LOWER("UnitName") = LOWER(?)', [$unitValue])
                            ->first();
                    }

                    if (!$unit) {
                        throw new \Exception("Unit not found: $unitValue");
                    }

                    $unitId = $unit->unitId;

                    // ✅ MAIN GROUP
                    $mainGroupData = DB::table('tbl_ProductMainGroup')
                        ->whereRaw('LOWER("groupName") = LOWER(?)', [$mainGroup])
                        ->first();

                    if (!$mainGroupData) {
                        throw new \Exception("Main Group not found: $mainGroup");
                    }

                    $groupCode = $mainGroupData->groupCode;

                    // ✅ GROUP1
                    $group1Id = null;
                    if ($group1Name) {
                        $group1 = DB::table('tbl_ProductGroup')
                            ->whereRaw('LOWER("groupName") = LOWER(?)', [$group1Name])
                            ->first();

                        if (!$group1) {
                            throw new \Exception("Group1 not found: $group1Name");
                        }

                        $group1Id = $group1->groupId;
                    }

                    // ✅ BRAND
                    $brandId = null;
                    if (!empty($row['brand'])) {

                        $brandName = trim($row['brand']);

                        $brand = DB::table('tbl_Brand')
                            ->whereRaw('LOWER("brandName") = LOWER(?)', [$brandName])
                            ->first();

                        if ($brand) {
                            $brandId = $brand->brandId;
                        } else {

                            $nextBrandId = (DB::table('tbl_Brand')->max('brandId') ?? 0) + 1;

                            DB::table('tbl_Brand')->insert([
                                'brandId' => $nextBrandId,
                                'brandName' => $brandName,
                                'CreatedDate' => now(),
                                'CreatedUser' => 'admin',
                                'branchId' => $branchId
                            ]);

                            $brandId = $nextBrandId;
                        }
                    }

                    // ✅ INSERT PRODUCT
                    $product = new Product([
                        'productCode'  => $productCode,
                        'productName'  => $productName,
                        'groupCode'    => $groupCode,
                        'group1Id'     => $group1Id,
                        'brandId'      => $brandId,
                        'unitId'       => $unitId,
                        'purchaseRate' => $row['purchase_rate'] ?? 0,
                        'openingStock' => $row['stock'] ?? 0,
                        'branchId'     => $branchId,
                        'CreatedUser'  => 'admin',
                        'CreatedDate'  => now(),
                    ]);

                    $product->save();

                    // ✅ SALES PRICE
                    if (!empty($row['sales_rate']) || !empty($row['mrp'])) {

                        DB::table('tbl_ProductSalesPrice')->insert([
                            'productCode'         => $productCode,
                            'unitId'              => $unitId,
                            'salesPrice'          => $row['sales_rate'] ?? 0,
                            'MaximumSellingPrice' => $row['mrp'] ?? 0,
                            'branchId'            => $branchId,
                            'CreatedDate'         => now(),
                            'CreatedUser'         => 'admin',
                        ]);
                    }

                    // ✅ UNIT CONVERSION (BARCODE)
                    if (!empty($row['barcode'])) {

                        $exists = DB::table('tbl_UnitConversion')
                            ->whereRaw('LOWER("barcode") = LOWER(?)', [$row['barcode']])
                            ->exists();

                        if ($exists) {
                            throw new \Exception("Barcode already exists");
                        }

                        $nextConversionId = (DB::table('tbl_UnitConversion')->max('unitConversionId') ?? 0) + 1;

                        DB::table('tbl_UnitConversion')->insert([
                            'unitConversionId' => $nextConversionId,
                            'productCode'      => $productCode,
                            'unitId'           => $unitId,
                            'conversionRate'   => 1,
                            'barcode'          => $row['barcode'],
                            'branchId'         => $branchId,
                            'CreatedDate'      => now(),
                            'CreatedUser'      => 'admin',
                        ]);
                    }

                    // ✅ STOCK POSTING
                    if (!empty($row['stock']) && $row['stock'] > 0) {

                        StockPosting::addPosting([
                            'date'        => now(),
                            'voucherType' => 'Opening Stock',
                            'voucherNo'   => $productCode,
                            'productCode' => $productCode,
                            'VoucherQty'  => $row['stock'],
                            'inwardQty'   => $row['stock'],
                            'outwardQty'  => 0,
                            'unitId'      => $unitId,
                            'branchId'    => $branchId,
                            'CreatedUser' => 'admin',
                        ]);
                    }

                    $this->success[] = $productCode;
                } catch (\Throwable $e) {

                    $this->failed[] = [
                        'row' => $rowNo,
                        'product_code' => $row['product_code'] ?? '',
                        'reason' => $e->getMessage()
                    ];
                }
            }

            if (count($this->failed) > 0) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception("Import Failed: " . $e->getMessage());
        }
    }
}