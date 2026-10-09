<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseQuotation extends Model
{
    protected $table = "tbl_PurchaseQuotation";
    protected $primaryKey = 'quotationMasterId';
    public $timestamps = false;
    protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'orderNo',
        'date',
        'dueDate',
        'ledgerId',
        'partyName',
        'partyAddress',
        'partyMobile',
        'partyVatNo',
        'partyRefNo',
        'partyRefDate',
        'narration',
        'currencyConversionId',
        'exchangeRate',
        'costCenterId',
        'BatchId',
        'taxType',
        'subTotal',
        'billDiscount',
        'additionalCost',
        'OtherCharge',
        'otherChargeLedgerId',
        'taxableAmt',
        'totalTax',
        'roundoff',
        'totalAmount',
        'transportCompany',
        'PaymentTerms',
        'Certificate',
        'DeliveryTerms',
        'cancelled',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
    public function details()
    {
        return $this->hasMany(
            PurchaseQuotationDetails::class,
            'quotationMasterId',
            'quotationMasterId'
        );
    }
}
