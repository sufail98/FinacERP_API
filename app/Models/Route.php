<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'tbl_Route';
    protected $primaryKey = 'RouteId';
    public $timestamps = false;

    protected $fillable = [
        'RouteId',
        'RouteName',
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
