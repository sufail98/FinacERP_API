<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxMaster extends Model
{
    protected $table = 'tbl_TaxMaster';
	protected $primaryKey = 'taxId';
    public $timestamps = false;

	protected $fillable = [
		'taxId',
		'taxName',
		'rate',
		'calculatingMode',
		'active',
		'narration',
		'branchId',
		'CreatedDate',
		'CreatedUser',
		'ModifiedDate',
		'ModifiedUser',
	];
}
