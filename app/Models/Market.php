<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $table = 'tbl_Market';
    protected $primaryKey = 'MarketId';
    public $timestamps = false;

    protected $fillable = [
        'MarketName',
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
