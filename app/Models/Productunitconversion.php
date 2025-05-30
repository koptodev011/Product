<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Productunitconversion extends Model
{
    protected $fillable = [
        'product_id',
        'product_base_unit_id',
        'product_secondary_unit_id',
        'conversion_rate'
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'product_base_unit_id' => 'integer',
        'product_secondary_unit_id' => 'integer',
        'conversion_rate' => 'integer',
        'is_active' => 'integer'

    ];

    public function unitConversion()
    {
        return $this->hasOne(Productunitconversion::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'productconversion_id', 'id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(ProductBaseUnit::class, 'product_base_unit_id');
    }

    public function secondaryUnit()
    {
        return $this->belongsTo(ProductBaseUnit::class, 'product_secondary_unit_id');
    }
    
}
