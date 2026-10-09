<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    protected $table = 'tbl_ProductGroup';
	protected $primaryKey = 'groupId';
    public $timestamps = false;

	protected $fillable = [
		'groupId',
		'groupName',
		'category',
		'narration',
		'branchId',
		'CreatedDate',
		'CreatedUser',
		'ModifiedDate',
		'ModifiedUser',
	];
}
