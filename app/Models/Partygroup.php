<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partygroup extends Model
{
    protected $fillable = ['group_name'];

    protected $casts = [
        'group_name' => 'string',
        'tenant_id' => 'integer',
        'is_delete' => 'integer'
    ];
}
