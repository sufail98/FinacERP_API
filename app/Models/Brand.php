<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'tbl_Brand';
    protected $primaryKey = 'brandId';
    public $timestamps = false;

    protected $fillable = [
        'brandId',
        'brandName',
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
