<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'business_name',
        'legal_name',
        'reference_number',
        'email',
        'phone',
        'status',
        'district',
        'operating_location',
        'address_notes',
        'internal_notes',
        'public_branding_approved',
    ];

    protected $casts = [
        'public_branding_approved' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ProductRequest::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
