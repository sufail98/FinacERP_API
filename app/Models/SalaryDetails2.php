<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryDetails2 extends Model
{
    protected $table = 'tbl_SalaryDetails2';
    protected $primaryKey = 'SalaryDetails2Id';
    public $timestamps = false;


    protected $fillable = [
        "SalaryDetails2Id",
        "SalaryDetails1Id",
        "PayheadId",
        "Amount",
        "branchId",
        "CreatedDate",
        "CreatedUser",
        'ModifiedDate',
        'ModifiedUser'
    ];
}
