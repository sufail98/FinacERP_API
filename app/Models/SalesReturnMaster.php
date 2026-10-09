<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnMaster extends Model
{
    protected $table = 'tbl_SalesReturnMaster';
	protected $primaryKey = 'ReturnMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'suffixPrefixId',
        'returnNo',
        'date',
        'billTime',
        'salesMasterId',
        'ledgerId',
        'salesAccountId',
        'pricingLevelId',
        'employeeId',
        'costCentreId',
        'BatchId',
        'currencyConversionId',
        'exchangeRate',
        'exchangeDate',
        'partyName',
        'partyAddress',
        'partyMobile',
        'partyVatNo',
        'partyRefNo',
        'partyRefDate',
        'lrNo',
        'transportCompany',
        'narration',
        'taxType',
        'taxableAmt',
        'subTotal',
        'totalTax',
        'additionalCost',
        'billDiscount',
        'roundOff',
        'totalAmount',
        'paymentMode',
        'CashLedgerId',
        'CashRefNo',
        'CashAmount',
        'BankLedgerId',
        'BankRefNo',
        'BankAmount',
        'BillBalanceAmount',
        'billDiscOnProductStatus',
        'AddCostonProductStatus',
        'postedStatus',
        'postedBy',
        'postedDate',
        'AgainstNo',
        'isVan',
        'vanExecutiveId',
        'preinvoiceHash',
        'currentinvoiceHash',
        'UUID',
        'status',
        'logdata',
        'qr_link',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
	];

    public function pricingLevel()
    {
        return $this->belongsTo(\App\Models\PricingLevel::class, 'pricingLevelId', 'PricingLevelId');
    }

    public function Ledger()
    {
        return $this->belongsTo(\App\Models\AccountLedger::class, 'ledgerId', 'ledgerId');
    }

    public function Godown()
    {
        return $this->belongsTo(\App\Models\Godown::class, 'GodownId', 'GodownId');
    }

    public function CostCentre()
    {
        return $this->belongsTo(\App\Models\CostCentre::class, 'costCentreId', 'costCentreId');
    }

    public function salesReturnDetails1()
    {
        return $this->hasMany(SalesReturnDetails1::class, 'ReturnMasterId', 'ReturnMasterId');
    }
}
