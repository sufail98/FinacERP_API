<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    protected $table = 'tbl_DeliveryNoteMaster';
	protected $primaryKey = 'deliveryNoteMasterId';
    public $timestamps = false;

	protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'deliveryNoteNo',
        'date',
        'ledgerId',
        'customerName',
        'CustomerAddress',
        'CustomerPhone',
        'CustomerVATNo',
        'RefNo',
        'RefDate',
        'costCentreId',
        'pricingLevelId',
        'employeeId',
        'narration',
        'currecyConversionId',
        'exchangeRate',
        'exchangeDate',
        'LPONo',
        'LPODate',
        'AgainstNo',
        'orderMasterId',
        'quotationMasterId',
        'proformaMasterId',
        'BatchId',
        'taxType',
        'totalAmount',
        'taxableAmt',
        'additionalCost',
        'totalTax',
        'billDiscount',
        'roundOff',
        'othercharge',
        'otherChargeLedgerId',
        'taxTotal',
        'grandTotal',
        'transportCompany',
        'deliveryLocation',
        'driverName',
        'contactNo',
        'deliveryVehicleNo',
        'postedStatus',
        'postedBy',
        'postedDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];
}
