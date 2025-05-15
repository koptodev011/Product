<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paymentin extends Model
{
    protected $casts = [
        'tenant_unit_id' => 'integer',
        'party_id' => 'integer',
        'reference_no' => 'integer',
        'payment_type_id' => 'integer',
        'received_amount' => 'integer'
    ];
    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    // Define the relationship with the TenantUnit model
    public function tenantUnit()
    {
        return $this->belongsTo(TenantUnit::class);
    }
}
