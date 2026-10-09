<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    //
    protected $table = 'tbl_Batch';
    protected $primaryKey = 'batchId';
        public $timestamps = false;


    protected $fillable = [
        'batchName',
        'mfd',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    

    
    ];
}
