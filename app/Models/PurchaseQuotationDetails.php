<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseQuotationDetails extends Model
{
    protected $table = 'tbl_PurchaseQuotationDetails';

    protected $primaryKey = 'quotationDetailsId';

    public $timestamps = false;

    protected $fillable = [
        'quotationDetailsId',
        'quotationMasterId',
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
        'AddCostOnProduct',
        'OtherChargeOnProduct',
        'productDescription',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];
    public function quotation()
    {
        return $this->belongsTo(
            PurchaseQuotation::class,
            'quotationMasterId',
            'quotationMasterId'
        );
    }
}