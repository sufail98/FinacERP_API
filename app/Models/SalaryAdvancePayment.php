<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryAdvancePayment extends Model
{
    protected $table = 'tbl_SalaryAdvancePayment';
	protected $primaryKey = 'advanceMasterId';
    public $timestamps = false;

	protected $fillable = [
        'advanceMasterId',
        'voucherNo',
        'suffixPrefixId',
        'advanceNo',
        'date',
        'employeeId',
        'ledgerId',
        'amount',
        'month',
        'narration',
        'chequeNo',
        'chequeDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
