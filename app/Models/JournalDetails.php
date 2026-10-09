<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalDetails extends Model
{
    protected $table = 'tbl_JournalDetails';
	protected $primaryKey = 'JournalDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'JournalMasterId',
        'ledgerId',
        'credit',
        'debit',
        'currencyConversionId',
        'exchangeRate',
        'exchangeDate',
        'lineIndex',
        'Narration',
        'RefNo',
        'costCentreId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
