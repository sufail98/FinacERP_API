<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferRateDetails extends Model
{
    protected $table = 'tbl_OfferRateDetails';
    protected $primaryKey = 'offerDetailsId';
    public $timestamps = false;

    protected $fillable = [
        'offerDetailsId',
        'offerId',
        'lineIndex',
        'productCode',
        'barcode',
        'unitId',
        'fromDate',
        'toDate',
        'pricingLevelId',
        'IsApproved',
        'IsActive',
        'AmountBeforeDisc',
        'DiscPer',
        'DiscAmount',
        'salesPrice',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];

    public function offer()
    {
        return $this->belongsTo(
            OfferRate::class,
            'offerId',
            'offerId'
        );
    }
}
