<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseSettings extends Model
{
    protected $table = 'tbl_PurchaseSettings';
    protected $primaryKey = 'PurchaseSettingsId';

    protected $fillable = [
        'VendorInvoiceNoChecking',
        'showProductDescription',
        'showFeeQtyColumn',
        'ActivatePurchaseRetention',
        'ChangeSalesPrice',
        'showLineDiscount',
        'ActiveGodown',
        'printAfterSave',
    ];

    public $timestamps = false;
}