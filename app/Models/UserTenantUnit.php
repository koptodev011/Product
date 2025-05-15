<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
class UserTenantUnit extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tenant_id', 'tenant_type'];

    protected $casts = [
        'user_id' => 'integer',
        'tenant_id' => 'integer',
        'tenant_type' => 'string',
    ];

    public function tenantable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'tenant_type', 'tenant_id');
    }
}
