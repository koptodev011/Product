<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producttaxgroup extends Model
{
    protected $casts = [
        'tenant_id' => 'integer',
    ];
}
