<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_type', 'party_id', 'billing_name','billing_address','phone_number',
        'po_number', 'po_date', 'tax_amount', 'received_amount',
        'payment_type', 'sale_description', 'sale_image',
        'user_id', 'status','invoice_no','invoice_date', 'due_date','reference_no'
    ];

    protected $casts = [
        'inoice_no' => 'integer',
        'tenant_unit_id' => 'integer',
        'sale_type' => 'integer',
        'party_id' => 'integer',
        'phone_number' => 'unsignedBigInteger',
        'po_number' => 'integer',
        'reference_no' => 'unsignedBigInteger',
        'received_amount' => 'integer',
        'payment_type_id' => 'integer',
        'total_amount' => 'integer',
        
    ];

    public function productSales()
    {
        return $this->hasMany(ProductSale::class, 'sale_id');
    }
}
