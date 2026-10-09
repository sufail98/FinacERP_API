<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContraMaster extends Model
{
    protected $table = 'tbl_ContraMaster';
	protected $primaryKey = 'contraMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'suffixPrefixId',
        'contraNo',
        'date',
        'ledgerId',
        'type',
        'narration',
        'totalAmount',
        'costCentreId',
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
