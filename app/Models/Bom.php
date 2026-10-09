<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    protected $table = 'tbl_BOM';
    protected $primaryKey = 'bomId'; 
    public $timestamps = false;

    protected $fillable = [
        'productCode', 'rowMaterialId', 'UnitId', 'Quantity',
        'CreatedDate', 'CreatedUser', 'ModifiedDate', 'ModifiedUser'
    ];

    // Unit relation
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'UnitId', 'unitId');
    }
}
