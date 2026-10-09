<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMainGroup extends Model
{
    protected $table = 'tbl_ProductMainGroup';
	protected $primaryKey = 'groupCode';
    public $timestamps = false;

   // IMPORTANT: since groupCode is not auto-increment
    public $incrementing = false;

    // Tell Laravel it's a string (not int)
    protected $keyType = 'string';

	protected $fillable = [
		'groupCode',
		'groupName',
		'scaleGroup',
		'productCodeLength',
		'nextNumber',
		'branchId',
		'CreatedDate',
		'CreatedUser',
		'ModifiedDate',
		'ModifiedUser',
	];

}
