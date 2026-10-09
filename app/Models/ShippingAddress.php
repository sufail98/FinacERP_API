<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $table = 'tbl_ShippingAddress';
	protected $primaryKey = 'addressId';
    public $timestamps = false;

	protected $fillable = [
		'ledgerId',
		'address1',
		'address2',
		'address3',
		'address4',
		'Isdefault',
		'CreatedDate',
		'CreatedUser',
		'ModifiedDate',
		'ModifiedUser',
	];
}
