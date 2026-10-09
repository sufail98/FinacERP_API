<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryMaster extends Model
{
    protected $table = 'tbl_SalaryMaster';
    protected $primaryKey = 'SalaryMasterId';
    public $timestamps = false;


    protected $fillable = [
        "SalaryMasterId",
        "VoucherNo",
        "Date",
        "Month",
        "Narration",
        "FinancialYearId",
        "LedgerId",
        "branchId",
        "CreatedDate",
        "CreatedUser",
        'ModifiedDate',
        'ModifiedUser'
    ];
}
