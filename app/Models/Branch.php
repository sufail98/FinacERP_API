<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Branch extends Model
{
    protected $table = 'tbl_Branch';
    protected $primaryKey = 'branchId';
    public $timestamps = false;

    protected $fillable = [
        'companyId',
        'currencyId',
        'branchName',
        'branchNameFL',
        'address',
        'addressFL',
        'streetName',
        'streetNameFL',
        'buildingNo',
        'buildingNoFL',
        'additionalNumber',
        'additionalNumberFL',
        'cityName',
        'cityNameFL',
        'district',
        'districtFL',
        'country',
        'countryFL',
        'postalCode',
        'postalCodeFL',
        'phoneNo',
        'fax',
        'mobile',
        'email',
        'web',
        'taxNo',
        'panNo',
        'crNo',
        'logo',
        'mainBranch',
        'startDate',
        'dbName',
        'activeStatus',
        'subscriptionNo',
        'createdDate',
        'createdUser',
        'modifiedDate',
        'modifiedUser',
        'branchCode',
    ];

    public function getLogoAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }
    public function productPurchasePrices()
    {
        return $this->hasMany(ProductPurchasePrice::class, 'branchid', 'branchid');
    }
}
