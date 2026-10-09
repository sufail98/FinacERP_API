<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionBatch extends Model
{
    protected $table = 'tbl_taransactionBatch';
    protected $primaryKey = 'transactionBatchId';
    public $timestamps = false;
    protected $fillable = [
        'transactionBatchId',
        'batchId',
        'VoucherType',
        'BatchName',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batchId', 'batchId');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchId', 'branchId');
    }
}