<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayableMaster extends Model
{
    protected $table = 'tbl_PayableMaster';
	protected $primaryKey = 'PayableMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'suffixPrefixId',
        'PayableNo',
        'date',
        'ledgerId',
        'partyName',
        'partyAddress',
        'partyPhone',
        'partyVatNo',
        'narration',
        'totalTax',
        'totalAmount',
        'costCentreId',
        'ReferenceNo',
        'ReferenceDate',
        'paymentMode',
        'CashLedgerId',
        'CashRefNo',
        'CashAmount',
        'BankLedgerId',
        'BankRefNo',
        'BankAmount',
        'BillBalanceAmount',
        'taxType',
        'currecyConversionId',
        'exchangeRate',
        'exchangeDate',
        'employeeId',
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
