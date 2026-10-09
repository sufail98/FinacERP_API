<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VanDayRegister extends Model
{
    protected $table = 'tbl_VanDayRegister';
    protected $primaryKey = 'DayRegisterId';
    public $timestamps = false;

    protected $fillable = [
        'DayRegisterId',
        'ExecutiveId',
        'DriverName',
        'IsOnDuty',
        'StartKM',
        'EndKM',
        'OpenStatus',
        'StartDate',
        'EndDate',
        'AreaId',
        'RouteId',
        'VehicleNo',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
        'branchId'
    ];

    
}
