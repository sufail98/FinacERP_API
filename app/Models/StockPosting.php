<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockPosting extends Model
{
    protected $table = 'tbl_StockPosting';
	protected $primaryKey = 'stockPostingId';
    public $timestamps = false;

	protected $fillable = [
        'date',
        'voucherType',
        'voucherNo',
        'productCode',
        'batchId',
        'godownId',
        'rackId',
        'VoucherQty',
        'VoucherUnitId',
        'voucherRate',
        'inwardQty',
        'outwardQty',
        'rate',
        'unitId',
        'referenceNo',
        'referenceType',
        'branchId',
        'CreatedDate',
        'CreatedUser',
    ];

    /**
     * Common function to insert stock posting
     */
    public static function addPosting(array $data)
    {
        $data['CreatedDate'] = $data['CreatedDate'] ?? now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        return self::create($data);
    }
}
