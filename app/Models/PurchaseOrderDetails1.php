<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetails1 extends Model
{
    protected $table = 'tbl_PurchaseOrderDetails1';
	protected $primaryKey = 'orderDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'orderMasterId',
        'productCode',
        'qty',
        'freeQty',
        'rate',
        'unitId',
        'amount',
        'ConversionFactor',
        'barcode',
        'discountPercentage',
        'taxId',
        'taxType',
        'taxAmount',
        'grossAmount',
        'netAmount',
        'billDiscOnProduct',
        'AddCostonProduct',
        'OtherChargeOnProduct',
        'productDescription',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
