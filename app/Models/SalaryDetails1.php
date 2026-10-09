<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryDetails1 extends Model
{
    protected $table = 'tbl_SalaryDetails1';
    protected $primaryKey = 'SalaryDetails1Id';
    public $timestamps = false;


    protected $fillable = [
        "SalaryDetails1Id",
        "SalaryMasterId",
        "EmployeeId",
        "SalaryDate",
        "Bonus",
        "Deduction",
        "Lop",
        "Salary",
        "branchId",
        "CreatedDate",
        "CreatedUser",
        'ModifiedDate',
        'ModifiedUser'
    ];
}
