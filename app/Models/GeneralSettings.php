<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSettings extends Model
{
    protected $table = 'tbl_GeneralSettings';
    protected $primaryKey = 'generalSettingsId';
    public $timestamps = false;

    protected $fillable = [
        'branchId',
        'ActivateTax',
        'costCentre',
        'AccountPosting',
        'negativeCashTransaction',
        'AccountCalculationMethod',
        'decimalPart',
        'dateformat',
        'taxType',
        'taxincluded',
        'taxCessincluded',
        'branchHeader',
        'branchFooter',
        'zatcaType',
        'RoundOff',
        'RoundOffDigit',
        'showCurrencyprefix',
        'askConfirmation',
        'taxLedgerId'
    ];

    protected $casts = [
        'ActivateTax' => 'boolean',
        'costCentre' => 'boolean',
        'AccountPosting' => 'boolean',
        'decimalPart' => 'integer',
        'taxincluded' => 'boolean',
        'taxCessincluded' => 'boolean',
        'RoundOff' => 'integer',
        'showCurrencyprefix' => 'boolean',
        'askConfirmation' => 'boolean'
    ];

    public function getLogoAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }
}
