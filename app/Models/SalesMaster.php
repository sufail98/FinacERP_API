<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesMaster extends Model
{
    protected $table = 'tbl_SalesMaster';
	protected $primaryKey = 'salesMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'invoiceNo',
        'suffixPrefixId',
        'date',
        'billTime',
        'ledgerId',
        'pricingLevelId',
        'employeeId',
        'salesAccount',
        'currencyConversionId',
        'taxType',
        'GodownId',
        'costCentreId',
        'BatchId',
        'customerName',
        'CustomerAddress',
        'CustomerPhone',
        'customerVATNo',
        'RefNo',
        'refDate',
        'orderRefNo',
        'orderRefDate',
        'creditPeriod',
        'dueDate',
        'deliveryDate',
        'exchangeRate',
        'exchangeDate',
        'deliveryNoteMasterId',
        'orderMasterId',
        'quotationMasterId',
        'proformaMasterId',
        'AgainstNo',
        'lrNo',
        'transportCompany',
        'vehicleNo',
        'narration',
        'taxableAmt',
        'subTotal',
        'totalTax',
        'additionalCost',
        'OtherChargeRemark',
        'otherChargeLedgerId',
        'othercharge',
        'billDiscount',
        'roundOff',
        'totalAmount',
        'AdjustmentAmount',
        'AdvancePerc',
        'RetentionLedgerId',
        'RetentionType',
        'RetentionDueDate',
        'RetentionPercentage',
        'RetentionAmount',
        'paymentMode',
        'CashLedgerId',
        'CashRefNo',
        'CashAmount',
        'BankLedgerId',
        'BankRefNo',
        'BankAmount',
        'BillBalanceAmount',
        'CarMake',
        'CarModel',
        'Kilometer',
        'NextService',
        'VehicleInTime',
        'VehicleOutTime',
        'preinvoiceHash',
        'currentinvoiceHash',
        'UUID',
        'Zatcastatus',
        'logdata',
        'qr_link',
        'postedStatus',
        'postedBy',
        'postedDate',
        'billDiscOnProductStatus',
        'AddCostonProductStatus',
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


    public function salesDetails1()
    {
        return $this->hasMany(SalesDetails1::class, 'salesMasterId', 'salesMasterId');
    }

    public function salesReturnMaster()
    {
        return $this->hasMany(SalesReturnMaster::class, 'salesMasterId', 'salesMasterId');
    }
}
