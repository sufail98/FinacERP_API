<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyConversion extends Model
{
    protected $table = 'tbl_CurrencyConversion';
    protected $primaryKey = 'currencyConversionId';
    public $timestamps = false;

    protected $fillable = [
        'currencyId',
        'date',
        'rate',
        'narration',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
