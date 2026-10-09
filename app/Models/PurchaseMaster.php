<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseMaster extends Model
{
    protected $table = 'tbl_Purchasemaster';
	protected $primaryKey = 'purchaseMasterId';
    public $timestamps = false;

	protected $fillable = [
        'suffixPrefixId',
        'voucherNo',
        'invoiceNo',
        'vendorInvoiceNo',
        'date',
        'invoiceDate',
        'creditPeriod',
        'ledgerId',
        'partyName',
        'partyAddress',
        'partyPhone',
        'RefNo',
        'partyRefDate',
        'partyVatNo',
        'narration',
        'purchaseAccount',
        'receiptMasterId',
        'orderMasterId',
        'AgainstNo',
        'costCentreId',
        'BatchId',
        'currencyConversionId',
        'exchangeRate',
        'exchangeDate',
        'taxType',
        'paymentMode',
        'subTotal',
        'additionalCost',
        'billDiscount',
        'othercharge',
        'otherChargeLedgerId',
        'taxableAmt',
        'totalTax',
        'roundoff',
        'totalAmount',
        'transportCompany',
        'CashLedgerId',
        'CashRefNo',
        'CashAmount',
        'BankLedgerId',
        'BankRefNo',
        'BankAmount',
        'BillBalanceAmount',
        'postedStatus',
        'postedBy',
        'postedDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'GodownId',
        'Documents'
    ];
}
