<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchaseproduct extends Model
{
    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'unit_id' => 'integer',
        'priceperunit' => 'unsignedInteger',
        'discount_percentage' => 'integer',
        'discount_amount' => 'unsignedInteger',
        'tax_percentage' => 'integer',
        'tax_amount' => 'unsignedInteger',
        'total_amount' => 'unsignedInteger',
        'purchase_id' => 'integer',
    ];
}
