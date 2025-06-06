<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Tenant extends Model
{
    
    protected $fillable = [
        'business_name',
        'business_types_id',
        'business_address',
        'phone_number',
        'business_categories_id',
        'TIN_number',
        'state_id',
        'business_email',
        'pin_code',
        'business_logo',
        'business_signature',
        'user_id',
        'isactive',
        'city_id',
        'isonlinestore'
    ];

    protected $casts = [
        'business_types_id' => 'integer',
        'business_categories_id' => 'integer',
        'state_id' => 'integer',
        'city_id' => 'integer',
        'pin_code' => 'integer',
        'user_id' => 'integer',
        'phone_number' => 'integer',
        'isactive' => 'boolean',
        'isonlinestore' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businesstype()
    {
        return $this->belongsTo(BusinessType::class, 'business_types_id');
    }

    // public function businesstype(){
    //     return $this->hasOne(BusinessType::class);
    // }
    public function businesscategory()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_categories_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id'); // Specify the foreign key if it’s not 'city_id'
    }

    public function userTenantUnits(): HasMany
{
    return $this->hasMany(UserTenantUnit::class, 'tenant_id');
}
  

public function tenantUnits(): HasMany
{
    return $this->hasMany(TenantUnit::class, 'tenant_id');
}

}
