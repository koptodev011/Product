<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxGroup extends Model
{
    protected $casts = [
        'tenant_id' => 'integer',
    ];
}
