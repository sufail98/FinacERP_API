<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDetails1 extends Model
{
    protected $table = 'tbl_PurchaseDetails1';
	protected $primaryKey = 'purchaseDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'purchaseMasterId',
        'receiptDetails1Id',
        'orderDetails1Id',
        'productCode',
        'productDescription',
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
        'SalesRate',
        'SlNo',
        'ConversionFactor',
        'barcode',
        'billDiscOnProduct',
        'AddCostonProduct',
        'otherchargeOnProduct',
        'GodownId',
        'RackId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}