<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerDocuments extends Model
{
    protected $table = 'tbl_LedgerDocuments';
	protected $primaryKey = 'id';
    public $timestamps = false;

	protected $fillable = [
        'ledgerId',
        'document_name',
        'file',
        'branchId',
        'createdDate',
        'createdUser',
        'modifiedUser',
        'modifiedDate',
    ];
}
