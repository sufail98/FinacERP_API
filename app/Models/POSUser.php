<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class POSUser extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'tbl_POS_User';
    protected $primaryKey = 'User_Id';
    public $timestamps = false;

    protected $fillable = [
        'User_Id',
        'branchIds',
        'EmpName',
        'userName',
        'Password',
        'PosUserGroupId',
        'PosAdmin',
        'MaxLineDiscountPer',
        'MaxBillDiscountPer',
        'Active',
        'language',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];

    protected $hidden = [
        'Password',
    ];

    protected $casts = [
        'Active' => 'boolean',
        'branchIds' => 'array',
        'CreatedDate' => 'datetime',
        'ModifiedDate' => 'datetime',
    ];
}
