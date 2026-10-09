<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZatcaRegistration extends Model
{
    protected $table = 'tbl_Zatca_Registration';
    protected $primaryKey = 'zatca_registration';
    public $timestamps = false;

    protected $fillable = [
        'zatca_registration',
        'ProfectionBinaryToken',
        'PrivateKey',
        'PublicKey',
        'ProductionSecret',
        'BinaryToken',
        'Auth_BinarySecurityToken_PCSID',
        'branchId',
        'createdDate',
        'createdUser',
        'modifiedDate',
        'modifiedUser',
        'PrevInvoiceHash',
    ];
}
