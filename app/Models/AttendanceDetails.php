<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceDetails extends Model
{
    protected $table = 'tbl_AttendanceDetails';
	protected $primaryKey = 'attendanceDetailsId';
    public $timestamps = false;

	protected $fillable = [
        'attendanceDetailsId',
        'attendanceMasterId',
        'employeeId',
        'status',
        'OTMnt',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];
}
