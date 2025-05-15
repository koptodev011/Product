<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paymentout extends Model
{
    protected $casts =[
    'party_id' => 'integer',
    'payment_type_id' => 'integer',
    'paid_amount' => 'integer',
    ];
}
