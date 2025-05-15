<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Productwholesaleprice extends Model
{
    protected $fillable = [
        'product_id',
        'whole_sale_price',
        'withorwithouttax',
        'wholesale_min_quantity'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'whole_sale_price' => 'integer',
        'withorwithouttax' => 'integer',
        'wholesale_min_quantity' => 'integer'
    ];

    public function wholesalePrice()
    {
        return $this->hasOne(Productwholesaleprice::class);
    }
}
