<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $table = 'tbl_UserGroup';
    protected $primaryKey = 'usergroupId';
    public $timestamps = false;

    protected $fillable = [
        'usergroupId',
        'usergroupName',
        'activeStatus',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];

    protected $casts = [
        'activeStatus' => 'boolean',
        'CreatedDate' => 'datetime',
        'ModifiedDate' => 'datetime'
    ];

    // Scope for active user groups
    public function scopeActive($query)
    {
        return $query->where('activeStatus', 1);
    }
}
