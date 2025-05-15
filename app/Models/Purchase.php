<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
// app/Models/Purchase.php

protected $casts = [
    'tenant_unit_id' => 'integer',
    'party_id' => 'integer',
    'phone_number' => 'unsignedBigInteger',
    'po_number' => 'integer',
    'total_amount' => 'integer',
    'paid_amount' => 'integer',
    'payment_type_id' => 'integer',
    'invoice_number' => 'integer',
    'reference_number' => 'integer'
];
public function party()
{
    return $this->belongsTo(Party::class, 'party_id');
}


public function purchaseproducts()
{
    return $this->hasMany(PurchaseProduct::class, 'purchase_id');
}



}
