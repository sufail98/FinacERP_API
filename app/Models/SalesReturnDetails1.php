<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnDetails1 extends Model
{
    protected $table = 'tbl_SalesReturnDetails1';
	protected $primaryKey = 'ReturnDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'ReturnMasterId',
        'salesDetails1Id',
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
        'ConversionFactor',
        'barcode',
        'PurchaseRate',
        'billDiscOnProduct',
        'AddCostonProduct',
        'GodownId',
        'RackId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];

    public function salesReturnMaster()
    {
        return $this->belongsTo(SalesReturnMaster::class, 'ReturnMasterId', 'ReturnMasterId');
    }
}
