<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceMaster extends Model
{
    protected $table = 'tbl_proformaInvoiceMaster';
	protected $primaryKey = 'proformaMasterId';
    public $timestamps = false;

	protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'proformaNo',
        'date',
        'dueDate',
        'cancelled',
        'ledgerId',
        'partyName',
        'partyAddress',
        'partyMobile',
        'partyVatNo',
        'partyRefNo',
        'partyRefDate',
        'status',
        'taxType',
        'quotationMasterId',
        'AgainstNo',
        'pricingLevelId',
        'employeeId',
        'narration',
        'currecyConversionId',
        'exchangeRate',
        'exchangeDate',
        'costCentreId',
        'BatchId',
        'LPONO',
        'LPODate',
        'deliveryDate',
        'DeliveryTerms',
        'PaymentTerms',
        'lrNo',
        'transportCompany',
        'subTotal',
        'additionalCost',
        'billDiscount',
        'othercharge',
        'otherChargeLedgerId',
        'taxableAmt',
        'totalTax',
        'roundOff',
        'totalAmount',
        'AdvancePerc',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];
}
