<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnMaster extends Model
{
    protected $table = 'tbl_PurchaseReturnMaster';
	protected $primaryKey = 'returnMasterId';
    public $timestamps = false;

	protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'returnNo',
        'date',
        'purchaseMasterId',
        'AgainstNo',
        'ledgerId',
        'purchaseAccount',
        'narration',
        'costCentreId',
        'BatchId',
        'currecyConversionId',
        'exchangeRate',
        'exchangeDate',
        'taxType',
        'subTotal',
        'additionalCost',
        'billDiscount',
        'othercharge',
        'taxableAmt',
        'roundOff',
        'totalTax',
        'totalAmount',
        'otherChargeLedgerId',
        'CashLedgerId',
        'CashRefNo',
        'CashAmount',
        'BankLedgerId',
        'BankRefNo',
        'BankAmount',
        'BillBalanceAmount',
        'transportCompany',
        'paymentMode',
        'partyName',
        'partyAddress',
        'partyPhone',
        'partyVatNo',
        'partyRefNo',
        'partyRefDate',
        'postedStatus',
        'postedBy',
        'postedDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'GodownId',
    ];
}
