<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $table = 'tbl_SalesOrderMaster';
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
        'pricingLevelId',
        'employeeId',
        'narration',
        'costCentreId',
        'currencyConversionId',
        'exchangeRate',
        'exchangeDate',
        'AgainstNo',
        'quotationMasterId',
        'proformaMasterId',
        'LPONO',
        'LPODate',
        'status',
        'taxType',
        'subTotal',
        'additionalCost',
        'billDiscount',
        'othercharge',
        'taxableAmt',
        'totalTax',
        'roundOff',
        'totalAmount',
        'otherChargeLedgerId',
        'lrNo',
        'transportCompany',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];
}
