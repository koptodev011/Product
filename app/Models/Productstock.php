<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Productstock extends Model
{
    protected $fillable = [
        'product_id',
        'product_stock',
        'at_price',
        'min_stock',
        'location'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'product_stock' => 'float',
        'at_price' => 'integer',
        'min_stock' => 'integer',
        'product_id' => 'integer',
        'secondaryunit_stock_value' => 'integer',
        'previous_stock' => 'integer'
    ];

    public function stock()
    {
        return $this->hasOne(Productstock::class);
    }

}
