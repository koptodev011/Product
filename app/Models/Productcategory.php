<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Productcategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_category',
        'is_delete'
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_delete' => 'integer'
    ];

    public function products()
{
    return $this->hasMany(Product::class, 'product_category_id');
}
}
