<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionPrivilege extends Model
{
    protected $table = 'tbl_SectionPrivilege';
	protected $primaryKey = 'sectionPrevId';
    public $timestamps = false;

	protected $fillable = [
        'sectionPrevId',
        'userGroupId',
        'SectionName',
        'status',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
