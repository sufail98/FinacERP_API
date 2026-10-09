<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'tbl_Area';
    protected $primaryKey = 'AreaId';
    public $timestamps = false;

    protected $fillable = [
        'AreaName',
        'Narration',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];

    protected $casts = [
        'CreatedDate' => 'datetime',
        'ModifiedDate' => 'datetime'
    ];
}
