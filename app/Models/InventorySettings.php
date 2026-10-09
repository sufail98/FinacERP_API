<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySettings extends Model
{
    protected $table = 'tbl_InventorySettings';
    protected $primaryKey = 'InventorySettingsId';

    protected $fillable = [
        'NegativeStock',
        'stockValueCalculation',
        'SalesPriceUpdateByCostPricePercentage',
        'maintainGodown',
        'maintainRack',
        'lowStockReminder',
    ];

    public $timestamps = false;
}
