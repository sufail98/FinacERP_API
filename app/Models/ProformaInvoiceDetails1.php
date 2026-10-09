<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceDetails1 extends Model
{
    protected $table = 'tbl_proformaInvoiceDetails1';
	protected $primaryKey = 'proformaDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'proformaMasterId',
        'productCode',
        'qty',
        'freeQty',
        'rate',
        'unitId',
        'amount',
        'quotationDetailsId',
        'ConversionFactor',
        'barcode',
        'productDescription',
        'discountPercentage',
        'taxId',
        'taxType',
        'taxAmount',
        'grossAmount',
        'netAmount',
        'PurchaseRate',
        'billDiscOnProduct',
        'AddCostonProduct',
        'SlNo',
        'Size',
        'otherchargeonproduct',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];
}
