<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSalesPrice extends Model
{
    protected $table = 'tbl_ProductSalesPrice';
    protected $primaryKey = 'salespriceId'; 
    public $timestamps = false;

    protected $fillable = [
        'productCode', 'unitId', 'amount', 'discPercentage',
        'discAmount', 'salesPrice', 'branchId', 'PricingLevelId','lowestSellingPrice',
        'CreatedDate', 'CreatedUser', 'ModifiedDate', 'ModifiedUser'
    ];

    // Unit relation
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unitId', 'unitId');
    }

    // Pricing level relation
    public function pricingLevel()
    {
        return $this->belongsTo(PricingLevel::class, 'PricingLevelId', 'PricingLevelId');
    }
}
