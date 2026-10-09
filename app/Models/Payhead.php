<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payhead extends Model
{
    protected $table = 'tbl_Payhead';
	protected $primaryKey = 'payheadId';
    public $timestamps = false;

	protected $fillable = [
        'payheadId',
        'payheadName',
        'type',
        'narration',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
