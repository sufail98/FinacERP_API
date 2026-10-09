<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarcodePrintAlignment extends Model
{
    protected $table = 'tbl_BarcodePrintAlignment';
    protected $primaryKey = 'Id';
        public $timestamps = false;


    protected $fillable = [
        'Id',
        'PaddingLeft',
        'PaddingRight',
        'PaddingTop',
        'PaddingBottom',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'height',
        'width',
        'BranchName',
        'ShowBranchName',
        'ShowPrice',
        'ShowProductName',
        'ShowSupplierCode'
    

    
    ];
}
