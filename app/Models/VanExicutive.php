<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VanExicutive extends Model
{
    protected $table = "tbl_VanExecutive";
    protected $primaryKey = 'ExecutiveId';
    public $timestamps = false;
    protected $fillable = [
        'ExecutiveId',
        'ExecutiveName',
        'Address',
        'Email',
        'PhoneNo',
        'GodownId',
        'userName',
        'password',
        'Status',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];
}