<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZatcaResponse extends Model
{
    protected $table = 'tbl_ZatcaResponse';
    protected $primaryKey = 'zid';
    public $timestamps = false;

    protected $fillable = [
        'zid',
        'SalesMasterId',
        'ZatcaResponse',
        'CreatedDate',
    ];
}
