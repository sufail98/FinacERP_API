<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialReceiptMaster extends Model
{
    protected $table = 'tbl_MaterialReceiptMaster';
	protected $primaryKey = 'receiptMasterId';
    public $timestamps = false;

	protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'receieptNo',
        'date',
        'ledgerId',
        'orderMasterId',
        'AgainstNo',
        'BatchId',
        'narration',
        'currecyConversionId',
        'exchangeRate',
        'exchangeDate',
        'costCentreId',
        'transportCompany',
        'taxType',
        'subTotal',
        'additionalCost',
        'billDiscount',
        'othercharge',
        'roundoff',
        'taxableAmt',
        'totalTax',
        'totalAmount',
        'partyAddress',
        'partyMobile',
        'partyName',
        'partyVatNo',
        'partyRefDate',
        'partyRefNo',
        'postedStatus',
        'postedBy',
        'postedDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
