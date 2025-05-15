<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Product;

class Productstockadjectment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'stock_quantity',
        'priceperunit',
        'addorreduct_product_stock',
        'details'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'stock_quantity' => 'integer',
        'priceperunit' => 'integer',
        'addorreduct_product_stock' => 'integer',
        'productadjectmentdate' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
