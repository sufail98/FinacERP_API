<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralReminder extends Model
{
    protected $table = 'tbl_generalReminders';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'all_day',
        'recurrence',
        'reminder_before',
        'priority',
        'status',
        'userId',
        'branchId',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser'
    ];
}
