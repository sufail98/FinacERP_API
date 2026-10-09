<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptMaster extends Model
{
    protected $table = 'tbl_ReceiptMaster';
	protected $primaryKey = 'receiptMasterId';
    public $timestamps = false;

	protected $fillable = [
        'voucherNo',
        'suffixPrefixId',
        'receiptNo',
        'date',
        'ledgerId',
        'narration',
        'totalAmount',
        'userId',
        'employeeId',
        'costCentreId',
        'ReferenceNo',
        'ReferenceDate',
        'postedStatus',
        'postedBy',
        'postedDate',
        'vanExecutiveId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifedDate',
        'ModifedUser',
    ];



    public function Ledger()
    {
        return $this->belongsTo(\App\Models\AccountLedger::class, 'ledgerId', 'ledgerId');
    }

    public function CostCentre()
    {
        return $this->belongsTo(\App\Models\CostCentre::class, 'costCentreId', 'costCentreId');
    }
}
