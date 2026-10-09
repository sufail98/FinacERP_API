<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'tbl_User';
    protected $primaryKey = 'userId';
    public $timestamps = false;

    protected $fillable = [
        'userId',
        'userName',
        'Password',
        'phoneNo',
        'email',
        'profilePhoto',
        'Narration',
        'branchIds',
        'UserRoleId',
        'ActiveStatus',
        'CreatedDate',
        'CreatedUser',
        'ModifiedDate',
        'ModifiedUser',
    ];

    protected $hidden = [
        'Password',
    ];

    protected $casts = [
        'ActiveStatus' => 'boolean',
        'branchIds' => 'array',
        'CreatedDate' => 'datetime',
        'ModifiedDate' => 'datetime',
    ];

    public function getProfilePhotoAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return null;
    }

    // Ensure profilePhoto is a full URL in JSON responses
    public function toArray()
    {
        $array = parent::toArray();
        $raw = $this->getRawOriginal('profilePhoto');
        if ($raw) {
            $array['profilePhoto'] = asset('storage/' . $raw);
        } elseif (isset($array['profilePhoto']) && $array['profilePhoto']) {
            $array['profilePhoto'] = asset('storage/' . $array['profilePhoto']);
        }
        return $array;
    }

    /**
     * Get the user group associated with the user
     */
    public function userGroup()
    {
        return $this->belongsTo(UserGroup::class, 'UserRoleId', 'usergroupId');
    }
}
