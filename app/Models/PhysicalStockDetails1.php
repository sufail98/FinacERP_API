<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalStockDetails1 extends Model
{
    protected $table = 'tbl_PhysicalStockDetails1';
	protected $primaryKey = 'physicalStockDetails1Id';
    public $timestamps = false;

	protected $fillable = [
        'physicalStockMasterId',
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
        'currentQty',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
