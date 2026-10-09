<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySettingsDetails extends Model
{
    protected $table = 'tbl_SalarySettingsDetails';
	protected $primaryKey = 'salarySettingsDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'salarySettingsDetailsId',
        'salarySettingsId',
        'payheadId',
        'AmtOrPer',
        'amount',
        'payheadIdOf',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
