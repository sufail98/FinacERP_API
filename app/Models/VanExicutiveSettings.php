<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VanExicutiveSettings extends Model
{
    protected $table = "tbl_VanExecutiveSettings";
    protected $primaryKey = 'executiveSettingsId';
    public $timestamps = false;
    protected $fillable = [
        'executiveSettingsId',
        'executiveId',
        'cashAccountLedgerId',
        'salesAccountLedgerId',
        'salesTargetAmount',
        'bankAccountLedgerId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];
}
