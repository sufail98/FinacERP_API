<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMaster extends Model
{
    protected $table = 'tbl_PaymentMaster';
	protected $primaryKey = 'paymentMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'suffixPrefixId',
        'paymentNo',
        'date',
        'ledgerId',
        'narration',
        'totalAmount',
        'costCentreId',
        'ReferenceNo',
        'ReferenceDate',
        'postedStatus',
        'postedBy',
        'postedDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'Documents'
    ];
}
