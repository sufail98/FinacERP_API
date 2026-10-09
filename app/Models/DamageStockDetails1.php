<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageStockDetails1 extends Model
{
    protected $table = 'tbl_DamageStockDetails1';
	protected $primaryKey = 'damageStockDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'damageStockMasterId',
        'productCode',
        'qty',
        'rate',
        'unitId',
        'amount',
        'ConversionFactor',
        'barcode',
        'productDescription',
        'SlNo',
        'GodownId',
        'RackId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
