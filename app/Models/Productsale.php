<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productsale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'quantity', 'amount', 'unit_id',
        'priceperunit', 'discount_percentage', 'discount_amount',
        'tax_percentage', 'tax_amount', 'sale_id'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'quantity' => 'integer',
        'amount' => 'integer',
        'unit_id' => 'integer',
        'priceperunit' => 'integer',
        'discount_percentage' => 'integer',
        'discount_amount' => 'integer',
        'tax_percentage' => 'integer',
        'tax_amount' => 'integer',
        'sale_id' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
