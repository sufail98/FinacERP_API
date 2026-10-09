<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class Company extends Model
{
    protected $table = 'tbl_Company';
    protected $primaryKey = 'companyId';
    public $timestamps = false;

    protected $fillable = [
        'companyId',
        'companyName',
        'companyNameFL',
        'companyAddress',
        'phoneNo',
        'AlternativePhoneNo',
        'companyLogo',
        'createdDate',
        'modifiedDate',
        'modifiedUser'
    ];

    // Override companyLogo to return full URL
    public function getCompanyLogoAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }

    // Ensure companyLogo is transformed when converting to array/JSON
    public function toArray()
    {
        $array = parent::toArray();
        if (isset($array['companyLogo']) && $array['companyLogo']) {
            $array['companyLogo'] = asset('storage/' . $array['companyLogo']);
        }
        return $array;
    }
    
}
