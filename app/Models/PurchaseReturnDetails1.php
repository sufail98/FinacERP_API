<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetails1 extends Model
{
    protected $table = 'tbl_PurchaseReturnDetails1';
	protected $primaryKey = 'returnDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'returnMasterId',
        'purchaseDetails1Id',
        'productCode',
        'qty',
        'freeQty',
        'rate',
        'unitId',
        'discountPercentage',
        'taxId',
        'taxType',
        'taxAmount',
        'grossAmount',
        'netAmount',
        'amount',
        'ConversionFactor',
        'barcode',
        'billDiscOnProduct',
        'AddCostonProduct',
        'SalesRate',
        'otherchargeOnProduct',
        'productDescription',
        'GodownId',
        'RackId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
