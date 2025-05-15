<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $fillable = [
        'TIN_number',
        'party_name',
        'email',
        'phone_number',
        'billing_address',
        'opening_balance',
        'to_pay_or_to_receive',
        'credit_limit',
        'is_active',
        'user_id',
        'tenant_id',
        'group_id',
    ];

    protected $casts = [
    'phone_number' => 'string',
    'TIN_number' => 'integer',
    'opening_balance' => 'integer',
    'topayortorecive' => 'integer',
    'creditlimit' => 'integer',
    'isactive' => 'integer',
    'user_id' => 'integer',
    'tenant_id' => 'integer',
    'group_id' => 'integer',
    'party_status' => 'integer',
    'is_delete' => 'integer'
];

    
    public function shippingAddresses()
    {
        return $this->hasMany(Shippingaddress::class);
    }

    public function additionalFields()
    {
        return $this->hasMany(Partyaddationalfields::class);
    }

    public function paymentins()
    {
        return $this->hasMany(Paymentin::class);
    }
}
