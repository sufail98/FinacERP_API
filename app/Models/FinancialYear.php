<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    protected $table = 'tbl_FinancialYear';
    protected $primaryKey = 'yearId';
    public $timestamps = false;

    protected $fillable = [
        'fromDate',
        'toDate',
        'closed',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
