<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Godown extends Model
{
    protected $table = 'tbl_Godown';
    protected $primaryKey = 'GodownId';
    public $timestamps = false;

    protected $fillable = [
        'GodownId',
        'GodownName',
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
