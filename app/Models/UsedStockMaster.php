<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsedStockMaster extends Model
{
    protected $table = 'tbl_UsedStockMaster';
    protected $primaryKey = 'usedStockMasterId';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'usedStockMasterId',
        'suffixPrefixId',
        'voucherNo',
        'usedStockNo',
        'date',
        'narration',
        'totalAmount',
        'refNo',
        'refDate',
        'postedStatus',
        'postedBy',
        'postedDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifedDate',
        'ModifedUser'
    ];

    public function details()
    {
        return $this->hasMany(
            UsedStockMasterDetails::class,
            'usedStockMasterId',
            'usedStockMasterId'
        );
    }
}