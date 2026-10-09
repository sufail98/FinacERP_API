<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkLocation extends Model
{
    protected $table = 'tbl_workLocation';
	protected $primaryKey = 'workLocationId';
    public $timestamps = false;

	protected $fillable = [
        'workLocationName',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
