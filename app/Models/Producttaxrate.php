<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producttaxrate extends Model
{
    protected $casts = [
        'product_tax_group_id' => 'integer',
        'product_tax_rate' => 'float',
    ];
}
