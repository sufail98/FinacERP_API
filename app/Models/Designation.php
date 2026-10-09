<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $table = 'tbl_Designation';
	protected $primaryKey = 'designationId';
    public $timestamps = false;

	protected $fillable = [
        'designationName',
        'leaveDays',
        'advancePercentage',
        'narration',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
