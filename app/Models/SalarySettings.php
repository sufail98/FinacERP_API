<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySettings extends Model
{
    protected $table = 'tbl_SalarySettings';
	protected $primaryKey = 'salarySettingsId';
    public $timestamps = false;

	protected $fillable = [
        'salarySettingsId',
        'employeeId',
        'date',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
