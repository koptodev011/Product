<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shippingaddress extends Model
{
    protected $fillable = [
        'shipping_address',
        'party_id'
    ];

    protected $casts = [
        'party_id' => 'integer'
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }
}
