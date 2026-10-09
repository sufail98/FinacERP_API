<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerPosting extends Model
{
    protected $table = 'tbl_LedgerPosting';
	protected $primaryKey = 'ledgerPostingId';
    public $timestamps = false;

	protected $fillable = [
        'date',
        'voucherType',
        'voucherNo',
        'ledgerId',
        'debit',
        'credit',
        'InvoiceNo',
        'Narration',
        'costCentreId',
        'PostingType',
        'exchangeRate',
        'exchangeDate',
        'branchId',
        'CreatedDate',
        'CreatedUser',
    ];


    /**
     * Common function to insert ledger posting
     */
    public static function addPosting(array $data)
    {
        $data['CreatedDate'] = $data['CreatedDate'] ?? now()->setTimezone('Asia/Kolkata')->format('Y-m-d H:i:s');
        return self::create($data);
    }
}
