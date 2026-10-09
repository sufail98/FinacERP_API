<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderMaster extends Model
{
    protected $table = 'tbl_PurchaseOrderMaster';
	protected $primaryKey = 'orderMasterId';
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
        'exchangeDate',
        'costCentreId',
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
}
