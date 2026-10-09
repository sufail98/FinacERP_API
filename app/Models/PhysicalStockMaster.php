<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalStockMaster extends Model
{
    protected $table = 'tbl_PhysicalStockMaster';
	protected $primaryKey = 'physicalStockMasterId';
    public $timestamps = false;

	protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'physicalStockNo',
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
