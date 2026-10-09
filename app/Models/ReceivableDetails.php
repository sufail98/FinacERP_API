<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivableDetails extends Model
{
    protected $table = 'tbl_ReceivableDetails';
	protected $primaryKey = 'receivableDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'ReceivableMasterId',
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
