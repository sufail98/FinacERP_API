<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickAccess extends Model
{
    protected $table = 'tbl_QuickAccess';
    protected $primaryKey = 'Id';
        public $timestamps = false;


    protected $fillable = [
        'Id',
        'WindowName',
        'Status',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    

    
    ];
}
