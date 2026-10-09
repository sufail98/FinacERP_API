<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationMaster extends Model
{
    protected $table = 'tbl_QuotationMaster';
	protected $primaryKey = 'quotationMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'quotationNo',
        'suffixPrefixId',
        'employeeId',
        'pricingLevelId',
        'date',
        'currencyConversionId',
        'exchangeRate',
        'exchangeDate',
        'BatchId',
        'ledgerId',
        'customerName',
        'CustomerAddress',
        'CustomerPhone',
        'CustomerVatNo',
        'creditPeriod',
        'costCentreId',
        'narration',
        'partyRefNo',
        'partyRefDate',
        'taxType',
        'totalAmount',
        'billDiscount',
        'additionalCost',
        'otherChargeLedgerId',
        'othercharge',
        'taxableAmt',
        'totalTax',
        'roundOff',
        'grandTotal',
        'billDiscOnProductStatus',
        'AddCostonProductStatus',
        'approved',
        'contactno',
        'contactperson',
        'deliveredwithin',
        'deliverysite',
        'paymentterms',
        'quatationvalidity',
        'DeliveryTerms',
        'Certificate',
        'IncoTerms',
        'Email',
        'CarMake',
        'CarModel',
        'Kilometer',
        'NextService',
        'VehicleInTime',
        'VehicleOutTime',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];
}
