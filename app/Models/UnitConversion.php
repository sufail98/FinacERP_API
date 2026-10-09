<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitConversion extends Model
{
    protected $table = 'tbl_UnitConversion';
    protected $primaryKey = 'unitConversionId'; 
    public $timestamps = false;

    protected $fillable = [
        'productCode', 'unitId', 'conversionRate', 'barcode',
        'CreatedDate', 'CreatedUser', 'ModifiedDate', 'ModifiedUser'
    ];

    // Unit relation
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unitId', 'unitId');
    }
}
