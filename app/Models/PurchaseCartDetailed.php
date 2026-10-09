<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseCartDetailed extends Model
{

    protected $table = 'tbl_PurchaseCartDetails';


    protected $primaryKey = 'PurchaseCartDetailsId';






    public $timestamps = false;


    protected $fillable = [
        'PurchaseCartMasterId',
        'productCode',
        'barcode',
        'manualItemName',
        'manualItemDescription',
        'Qty',
        'expectedPrice',
        'Priority',
        'status',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifedDate',
        'ModifedUser'
    ];


    public function master()
    {
        return $this->belongsTo(PurchaseCartMaster::class, 'PurchaseCartMasterId', 'PurchaseCartMasterId');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'productCode', 'productCode');
    }
}
