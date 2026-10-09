<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAttendanceMaster extends Model
{
    protected $table = 'tbl_StaffAttendanceMaster';
	protected $primaryKey = 'attendanceMasterId';
    public $timestamps = false;

	protected $fillable = [
        'attendanceMasterId',
        'date',
        'narration',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
