<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
	protected $table = 'tbl_Currency';
	protected $primaryKey = 'currencyId';

	protected $fillable = [
		'currencySymbol',
		'currencyName',
		'subunitName',
		'noOfDecimalPlace',
		'narration',
		'default',
		'branchId',
		'CreatedDate',
		'CreatedUser',
		'ModifiedDate',
		'ModifiedUser',
	];

	public $timestamps = false;
}
