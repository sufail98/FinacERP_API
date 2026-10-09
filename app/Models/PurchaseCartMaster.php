<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseCartMaster extends Model
{
    protected $table = 'tbl_PurchaseCartMaster';


    protected $primaryKey = 'PurchaseCartMasterId';


    public $incrementing = false;





    public $timestamps = false;

    protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'PurchaseCartNo',
        'date',
        'Narration',
        'CustomerName',
        'CustomerPhone',
        'Status',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifedDate',
        'ModifedUser'
    ];

    // Relationship with details
    public function details()
    {
        return $this->hasMany(PurchaseCartDetailed::class, 'PurchaseCartMasterId', 'PurchaseCartMasterId');
    }
}
