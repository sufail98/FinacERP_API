<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferRate extends Model
{
    protected $table = 'tbl_OfferRate';

    protected $primaryKey = 'offerId';
    public $timestamps = false;
    protected $fillable = [
        'offerId',
        'offerCode',
        'offerName',
        'fromDate',
        'toDate',
        'pricingLevel',
        'IsApproved',
        'IsActive',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];
    public function details()
    {
        return $this->hasMany(
            OfferRateDetails::class,
            'offerId',
            'offerId'
        );
    }
}
