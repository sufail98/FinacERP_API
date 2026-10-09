<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferDetails extends Model
{
    protected $table = 'tbl_StockTransferDetails';
	protected $primaryKey = 'transferDetailsId';
    public $timestamps = false;

	protected $fillable = [
		'transferDetailsId',
        'transferMasterId',
        'productCode',
        'quantity',
        'unitId',
        'rate',
        'amount',
        'rackIdFrom',
        'rackIdTo',
        'ConversionFactor',
        'barcode',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'receivedQuantity',
	];
}
