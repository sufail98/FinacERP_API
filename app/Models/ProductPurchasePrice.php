<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchasePrice extends Model
{
    use HasFactory;

    protected $table = 'tbl_productpurchaseprice';
    protected $primaryKey = 'purchasepriceid';
    public $timestamps = false;

    protected $fillable = [
        'purchasepriceid',
        'ledgerid',
        'productcode',
        'unitid',
        'amount',
        'discpercentage',
        'discamount',
        'purchaseprice',
        'userid',
        'branchid',
        'extradate',
        'extra1',
        'extra2',
        'purchasedate',
        'purchasemasterid'
    ];



    public function ledger()
    {
        return $this->belongsTo(AccountLedger::class, 'ledgerid', 'ledgerId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'userId');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchId');
    }

    public function purchaseMaster()
    {
        return $this->belongsTo(PurchaseMaster::class, 'purchasemasterid', 'purchaseMasterId');
    }
}
