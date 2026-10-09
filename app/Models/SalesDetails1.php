<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesDetails1 extends Model
{
    protected $table = 'tbl_SalesDetails1';
	protected $primaryKey = 'salesDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'salesMasterId',
        'deliveryNoteDetails1Id',
        'orderDetails1Id',
        'quotationDetailsId',
        'proformaDetails1Id',
        'SlNo',
        'productCode',
        'qty',
        'freeQty',
        'rate',
        'unitId',
        'discountPercentage',
        'taxId',
        'taxType',
        'ConversionFactor',
        'barcode',
        'PurchaseRate',
        'taxAmount',
        'grossAmount',
        'netAmount',
        'amount',
        'productDescription',
        'billDiscOnProduct',
        'AddCostonProduct',
        'otherchargeonproduct',
        'salesManId',
        'GodownId',
        'RackId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];

    public function salesMaster()
    {
        return $this->belongsTo(SalesMaster::class, 'salesMasterId', 'salesMasterId');
    }
}
