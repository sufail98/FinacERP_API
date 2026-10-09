<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialReceiptDetails1 extends Model
{
    protected $table = 'tbl_MaterialReceiptDetails1';
	protected $primaryKey = 'receiptDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'receiptMasterId',
        'productCode',
        'orderDetails1Id',
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
        'productDescription',
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
