<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuffixPrefix extends Model
{
    protected $table = 'tbl_SuffixPrefix';
	protected $primaryKey = 'suffixPrefixId';
    public $timestamps = false;

	protected $fillable = [
        'voucherType',
        'yearId',
        'suffix',
        'prefix',
        'startIndex',
        'addWithZero',
        'noOfZero',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
