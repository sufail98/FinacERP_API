<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPrivilege extends Model
{
    protected $table = 'tbl_UserPrivileges';
    protected $primaryKey = 'privilege_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'privilege_id',
        'usergroup_id',
        'window_name',
        'can_all',
        'can_add',
        'can_edit',
        'can_delete',
        'can_post',
        'can_view',
        'can_home',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
