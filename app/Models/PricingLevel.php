<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingLevel extends Model
{
    protected $table = 'tbl_PricingLevel';
    protected $primaryKey = 'PricingLevelId';
    public $timestamps = false;

    protected $fillable = [
        'PricingLevelId',
        'PricingLevelName',
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
