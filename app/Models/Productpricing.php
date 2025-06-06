<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Productpricing extends Model
{
    protected $fillable = [
        'product_id',
        'sale_price',
        'withorwithouttax',
        'discount',
        'percentageoramount'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sale_price' => 'float',
        'withorwithouttax' => 'integer',
        'discount' => 'float',
        'percentageoramount' => 'integer'
    ];

    public function pricing()
    {
        return $this->hasOne(Productpricing::class);
    }
}
