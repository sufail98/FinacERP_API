<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationDetails extends Model
{
    protected $table = 'tbl_QuotationDetails';
	protected $primaryKey = 'quotationDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'quotationMasterId',
        'productCode',
        'unitId',
        'taxId',
        'qty',
        'freeQty',
        'rate',
        'amount',
        'discountPercentage',
        'discountAmount',
        'taxAmount',
        'grossAmount',
        'taxType',
        'netAmount',
        'ConversionFactor',
        'barcode',
        'billDiscOnProduct',
        'AddCostonProduct',
        'productDescription',
        'SlNo',
        'PurchaseRate',
        'otherchargeonproduct',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifedDate',
        'ModifedUser',
	];
}
