<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCentre extends Model
{
    protected $table = 'tbl_CostCentre';
    protected $primaryKey = 'costCentreId';
    public $timestamps = false;

    protected $fillable = [
        'costCentreId',
        'CostCentre',
        'Narration',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];

    protected $casts = [
        'CreatedDate' => 'datetime',
        'ModifiedDate' => 'datetime',
    ];
}
