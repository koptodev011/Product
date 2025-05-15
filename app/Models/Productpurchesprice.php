<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Productpurchesprice extends Model
{
    protected $fillable = [

        'product_purches_price',
        'withorwithouttax'
    ];

    protected $casts = [
        'product_purches_price' => 'integer',
        'withorwithouttax' => 'integer',
        'product_id' => 'integer'
    ];
   
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
