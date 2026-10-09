<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDetails extends Model
{
    protected $table = 'tbl_PaymentDetails';
	protected $primaryKey = 'paymentDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'paymentMasterId',
        'ledgerId',
        'amount',
        'currencyConversionId',
        'exchangeRate',
        'exchangeDate',
        'chequeNo',
        'chequeDate',
        'lineIndex',
        'Narration',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
