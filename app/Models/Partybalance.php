<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partybalance extends Model
{
    protected $table = 'tbl_Partybalance';
	protected $primaryKey = 'balanceId';
    public $timestamps = false;

	protected $fillable = [
        'date',
        'ledgerId',
        'voucherType',
        'voucherNo',
        'againstVoucherType',
        'againstvoucherNo',
        'referenceType',
        'debit',
        'credit',
        'creditPeriod',
        'currencyConversionId',
        'invoiceNo',
        'referenceNo',
        'BillAmount',
        'invoiceDate',
        'costCentreId',
        'exchangeRate',
        'exchangeDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
    ];

    /**
     * Common function to insert Partybalance posting
     */
    public static function addPosting(array $data)
    {
        $data['CreatedDate'] = $data['CreatedDate'] ?? now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        return self::create($data);
    }
}
