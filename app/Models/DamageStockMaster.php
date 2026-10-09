<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageStockMaster extends Model
{
    protected $table = 'tbl_DamageStockMaster';
	protected $primaryKey = 'damageStockMasterId';
    public $timestamps = false;

	protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'damageStockNo',
        'date',
        'narration',
        'totalAmount',
        'RefNo',
        'RefDate',
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
