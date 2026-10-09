<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'tbl_Department';
	protected $primaryKey = 'departmentId';
    public $timestamps = false;

	protected $fillable = [
        'departmentName',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
