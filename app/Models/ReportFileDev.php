<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportFileDev extends Model
{
    protected $table = "tbl_ReportFileDev";
    protected $primaryKey = 'moduleId';
    public $timestamps = false;
    protected $fillable = [
        'formName',
        'formType',
        'printType',
        'reportName',
        'isDefault',
        'isActive',
        'extraDate',
        'extra1',
        'ReportFormat',
    ];
}
