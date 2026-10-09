<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNoteDetails1 extends Model
{
    protected $table = 'tbl_DeliveryNoteDetails1';
	protected $primaryKey = 'deliveryNoteDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'deliveryNoteMasterId',
        'productCode',
        'SlNo',
        'qty',
        'freeQty',
        'rate',
        'unitId',
        'amount',
        'ConversionFactor',
        'barcode',
        'discountPercentage',
        'taxAmount',
        'grossAmount',
        'taxType',
        'netAmount',
        'billDiscOnProduct',
        'AddCostonProduct',
        'productDescription',
        'PurchaseRate',
        'otherchargeonproduct',
        'Remark',
        'taxId',
        'GodownId',
        'RackId',
        'orderDetails1Id',
        'quotationDetailsId',
        'proformaDetails1Id',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];
}
