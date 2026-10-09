<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderdetails1 extends Model
{
    protected $table = 'tbl_SalesOrderdetails1';
	protected $primaryKey = 'orderDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'orderMasterId',
        'quotationDetailsId',
        'proformaDetails1Id',
        'productCode',
        'productDescription',
        'barcode',
        'qty',
        'freeQty',
        'rate',
        'unitId',
        'amount',
        'ConversionFactor',
        'discountPercentage',
        'taxId',
        'taxType',
        'taxAmount',
        'grossAmount',
        'netAmount',
        'PurchaseRate',
        'billDiscOnProduct',
        'AddCostonProduct',
        'otherchargeonproduct',
        'SlNo',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];
}
