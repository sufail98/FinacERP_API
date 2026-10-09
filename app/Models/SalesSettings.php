<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesSettings extends Model
{
    protected $table = 'tbl_SalesSettings';
    protected $primaryKey = 'SalesSettingsId';

    protected $fillable = [
        'showProductDescription',
        'showFeeQtyColumn',
        'showLineDiscount',
        'showPurchaserate',
        'ShowPartNo',
        'ActivateSalesRetention',
        'showBillProfit',
        'showBillDiscountAmount',
        'showBillDiscountPerc',
        'focusAfterSalesRate',
        'showProductDetails',
        'ActiveGodown',
        'ledgerPricingAlert',
        'DefaultPaymentMode',
        'DefaultBankAccountForInvoicePrint'
    ];

    public $timestamps = false;
}
