<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusDeductionDetails extends Model
{
    protected $table = 'tbl_BonusDeductionDetails';
	protected $primaryKey = 'bonusDeductionDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'bonusDeductionDetailsId',
        'bonusDeductionMasterId',
        'employeeId',
        'bonus',
        'deduction',
        'bonusNarration',
        'deductionNarration',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
