<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsedStockMasterDetails extends Model
{
    protected $table = 'tbl_UsedStockDetails';
    protected $primaryKey = 'usedStockDetails1Id';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'usedStockMasterId',
        'productCode',
        'qty',
        'rate',
        'unitId',
        'amount',
        'ConversionFactor',
        'barcode',
        'SlNo',
        'GodownId',
        'RackId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifedDate',
        'ModifedUser'
    ];
    public function master()
    {
        return $this->belongsTo(
            UsedStockMaster::class,
            'usedStockMasterId',
            'usedStockMasterId'
        );
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'productCode',
            'productCode'
        );
    }
}