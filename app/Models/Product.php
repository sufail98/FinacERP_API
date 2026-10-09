<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $table = 'tbl_Product';
    protected $primaryKey = 'productCode';
    public $timestamps = false;

    // productCode is string, not auto-increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
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
        'salesTaxId',
        'PurchaseTaxId',
        'minimumStock',
        'maximumStock',
        'reorderLevel',
        'openingStock',
        'taxType',
        'narration',
        'bom',
        'active',
        'showReminder',
        'fixedSalesRate',
        'avgCost',
        'fifoValue',
        'avgRate',
        'fifoRate',
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
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];

    // Automatically cast JSON and boolean fields
    protected $casts = [
        'salesTaxId' => 'array',
        'PurchaseTaxId'   => 'array',
        'bom'             => 'boolean',
        'active'          => 'boolean',
        'showReminder'    => 'boolean',
        'EnableSales'     => 'boolean',
        'EnablePurchase'  => 'boolean',
        'EnableInventory' => 'boolean',
        'NutritionFact'   => 'boolean',
        'ShowExpiry'      => 'boolean',
        'allowBatch'      => 'boolean',
    ];

    // Accessor for productImage
    protected $appends = ['productImage'];
    public function getProductImageAttribute($value)
    {
        $original = $this->getRawOriginal('productImage'); // always fetch DB value
        return $original ? asset('storage/' . $original) : null;
    }

    // Relations
    public function group1()
    {
        return $this->belongsTo(\App\Models\ProductGroup::class, 'group1Id', 'groupId');
    }
    public function group2()
    {
        return $this->belongsTo(\App\Models\ProductGroup::class, 'group2Id', 'groupId');
    }
    public function group3()
    {
        return $this->belongsTo(\App\Models\ProductGroup::class, 'group3Id', 'groupId');
    }
    public function group4()
    {
        return $this->belongsTo(\App\Models\ProductGroup::class, 'group4Id', 'groupId');
    }
    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brandId', 'brandId');
    }
    public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class, 'unitId', 'unitId');
    }
    public function salesTax()
    {
        return $this->belongsTo(\App\Models\TaxMaster::class, 'salesTaxId', 'taxId');
    }
    public function purchaseTax()
    {
        return $this->belongsTo(\App\Models\TaxMaster::class, 'purchaseTaxId', 'taxId');
    }
    public function salesPrices()
    {
        return $this->hasMany(\App\Models\ProductSalesPrice::class, 'productCode', 'productCode')
            ->with(['unit:unitId,unitId,UnitName', 'pricingLevel:PricingLevelId,PricingLevelId,PricingLevelName']);
    }

    public function unitConversions()
    {
        return $this->hasMany(\App\Models\UnitConversion::class, 'productCode', 'productCode')
            ->with(['unit:unitId,unitId,UnitName']);
    }

    public function billOfMaterials()
    {
        return $this->hasMany(\App\Models\Bom::class, 'productCode', 'productCode')
            ->with(['unit:unitId,unitId,UnitName']);
    }
    public function getSalesTaxesAttribute()
    {
        $ids = $this->salesTaxId ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        return TaxMaster::whereIn('taxId', $ids)
            ->get(['taxId', 'taxName', 'rate']); // <-- use real column name
    }

    public function getPurchaseTaxesAttribute()
    {
        $ids = $this->PurchaseTaxId ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        return TaxMaster::whereIn('taxId', $ids)
            ->get(['taxId', 'taxName', 'rate']); // <-- same here
    }
}
