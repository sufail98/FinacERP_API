<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayableDetails extends Model
{
    protected $table = 'tbl_PayableDetails';
	protected $primaryKey = 'payableDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'payableMasterId',
        'ledgerId',
        'grossAmount',
        'discountPercentage',
        'netAmount',
        'taxId',
        'taxType',
        'taxAmount',
        'amount',
        'Narration',
        'chequeNo',
        'chequeDate',
        'lineIndex',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
