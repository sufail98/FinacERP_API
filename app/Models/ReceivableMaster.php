<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivableMaster extends Model
{
    protected $table = 'tbl_ReceivableMaster';
	protected $primaryKey = 'ReceivableMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'suffixPrefixId',
        'ReceivableNo',
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
