<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContraDetails extends Model
{
    protected $table = 'tbl_ContraDetails';
	protected $primaryKey = 'contraDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'contraMasterId',
        'ledgerId',
        'amount',
        'chequeNo',
        'chequeDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
