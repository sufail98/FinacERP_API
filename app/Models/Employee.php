<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    protected $table = 'tbl_Employee';
	protected $primaryKey = 'employeeId';
    public $timestamps = false;

	protected $fillable = [
        'employeeId',
        'suffixPrefixId',
        'voucherNo',
        'employeeCode',
        'employeeName',
        'designationId',
        'DepartmentId',
        'workLocationId',
        'dob',
        'meritalStatus',
        'gender',
        'qualification',
        'PermenentAddress',
        'CurrentAddress',
        'Nationality',
        'whatsAppNumber',
        'phoneNo',
        'mobileNo',
        'email',
        'emergencyContactName',
        'emergencyContactNumber',
        'joiningDate',
        'terminationDate',
        'active',
        'narration',
        'bloodGroup',
        'routeId',
        'areaId',
        'marketId',
        'passportNo',
        'passportIssueDate',
        'passportExpDate',
        'visaType',
        'labourCardNo',
        'labourCardIssueDate',
        'labourCardExpDate',
        'visaNo',
        'visaExpDate',
        'OTHourlyAmount',
        'InsuranceNumber',
        'InsuranceExpirydate',
        'MOLContractNumber',
        'SponsorName',
        'GOSI_SSOnumber',
        'WPScompliance',
        'BankName',
        'BankAccountNo',
        'IBAN',
        'SwiftCode',
        'BankBranch',
        'employeephoto',
        'documentuploadOptionNeeded',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];

    public function getemployeephotoAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }

    public function designation()
    {
        return $this->belongsTo(\App\Models\Designation::class, 'designationId', 'designationId');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class, 'DepartmentId', 'departmentId');
    }

    public function worklocation()
    {
        return $this->belongsTo(\App\Models\WorkLocation::class, 'workLocationId', 'workLocationId');
    }

    public function route()
    {
        return $this->belongsTo(\App\Models\Route::class, 'routeId', 'RouteId');
    }

    public function area()
    {
        return $this->belongsTo(\App\Models\Area::class, 'areaId', 'AreaId');
    }

    public function market()
    {
        return $this->belongsTo(\App\Models\Market::class, 'marketId', 'MarketId');
    }
}
