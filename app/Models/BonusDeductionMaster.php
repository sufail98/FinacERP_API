<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusDeductionMaster extends Model
{
    protected $table = 'tbl_BonusDeductionMaster';
	protected $primaryKey = 'bonusDeductionMasterId';
    public $timestamps = false;

	protected $fillable = [
        'bonusDeductionMasterId',
        'date',
        'month',
        'narration',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
