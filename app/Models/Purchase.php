<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
// app/Models/Purchase.php
public function party()
{
    return $this->belongsTo(Party::class, 'party_id');
}


public function purchaseproducts()
{
    return $this->hasMany(PurchaseProduct::class, 'purchase_id');
}



}
