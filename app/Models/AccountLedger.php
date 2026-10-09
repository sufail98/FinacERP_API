<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLedger extends Model
{
    protected $table = 'tbl_AccountLedger';
    protected $primaryKey = 'ledgerId';
    public $timestamps = false;

    protected $fillable = [
        'ledgerId',
        'ledgerName',
        'groupId',
        'affectInventory',
        'openingBalance',
        'crOrDr',
        'narration',
        'nameArb',
        'accountNo',
        'address',
        'phoneNo',
        'faxNo',
        'email',
        'creditPeriod',
        'creditLimit',
        'pricingLevelId',
        'currencyId',
        'interestOrNot',
        'marketId',
        'default',
        'tinNumber',
        'cstNumber',
        'panNumber',
        'extraDate',
        'extra1',
        'extra2',
        'areaId',
        'ledgerCode',
        'BuildingNo',
        'AdditionalNo',
        'StreetName',
        'PostboxNo',
        'CityName',
        'Country',
        'creditLimitStatus',
        'bankaccname',
        'bankname',
        'ibanno',
        'District',
        'StreetNameArb',
        'BuildingNoArb',
        'CityNameArb',
        'DistrictArb',
        'CountryArb',
        'AdditionalNoArb',
        'PostboxNoArb',
        'bankBranchName',
        'bankSwiftCode',
        'AddressArabic',
        'ledgerType',
        'routeId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'billBybill'
    ];

    protected $casts = [
        'branchId' => 'array',
    ];

    public function ShippingAddress()
    {
        return $this->hasMany(\App\Models\ShippingAddress::class, 'ledgerId', 'ledgerId');
    }
    public function productPurchasePrices()
    {
        return $this->hasMany(ProductPurchasePrice::class, 'ledgerId', 'ledgerId');
    }
    public function LedgerDocuments()
    {
        return $this->hasMany(LedgerDocuments::class, 'ledgerId', 'ledgerId');
    }
}
