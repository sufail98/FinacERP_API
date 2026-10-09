<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $table = 'tbl_Organization';
    protected $primaryKey = 'OrganizationId';
    public $timestamps = false;

    protected $fillable = [
        'OrganizationName',
        'OrganizationNameFL',
        'Address1',
        'Address2',
        'Address3',
        'Phone',
        'email',
        'Web',
        'Currency',
        'CurrencySymbol',
        'SerialNumber',
        'DatabaseName',
        'VATNumber',
        'VatEnable',
        'VATType',
        'isVatIncluded',
        'ExpiryDate',
        'Organization_logo',
        'sale',
        'receipt',
        'payment',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'AppVersion',
    ];
}
