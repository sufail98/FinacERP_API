<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceSettings extends Model
{
    protected $table = 'tbl_FinanceSettings';
    protected $primaryKey = 'FinanceSettingsId';
    
    protected $fillable = [
        'ShowAllTransactions',
        'MaintainBillbyBill',
        'multiCurrency',
        'showLedgerbalance'
    ];
    
    public $timestamps = false;
}
