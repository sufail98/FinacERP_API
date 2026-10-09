<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalMaster extends Model
{
    protected $table = 'tbl_JournalMaster';
	protected $primaryKey = 'JournalMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'suffixPrefixId',
        'JournalNo',
        'date',
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
    ];
}
