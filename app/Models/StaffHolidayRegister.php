<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffHolidayRegister extends Model
{
    protected $table = 'tbl_StaffHolidayRegister';
    protected $primaryKey = 'holidayId';
        public $timestamps = false;


    protected $fillable = [
        "holidayId",
        "date",
        "holidayName",
        "narration",
        "branchId",
        "CreatedDate",
        "CreatedUser",
        'ModifiedDate',
        'ModifiedUser'
    ];
}
