<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferMaster extends Model
{
    protected $table = 'tbl_StockTransferMaster';
	protected $primaryKey = 'transferMasterId';
    public $timestamps = false;

	protected $fillable = [
		'transferMasterId',
        'voucherNo',
        'suffixPrefixId',
        'transferNo',
        'date',
        'branchIdFrom',
        'branchIdTo',
        'godownIdFrom',
        'godownIdTo',
        'transportCompany',
        'grandTotal',
        'narration',
        'status',
        'postedStatus',
        'postedBy',
        'postedDate',
        'currecyConversionId',
        'exchangeRate',
        'exchangeDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'approvedStatus',
        'approvedUser',
        'approvedDate',
	];
}
