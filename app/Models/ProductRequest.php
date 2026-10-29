<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductRequest extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'customer_id', 'shop_product_id', 'vendor_id', 'assigned_officer_id',
        'product_location_id', 'order_id', 'customer_name', 'email', 'phone', 'product_name',
        'product_variation_id', 'variation_label', 'variation_value', 'variation_price',
        'quantity', 'instructions', 'delivery_location', 'priority', 'status',
        'customer_message', 'internal_notes',
    ];

    protected $casts = [
        'variation_price' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProductLocation::class, 'product_location_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class);
    }

    public function scopeOwnedByVendor($query, ?int $vendorId)
    {
        if (! $vendorId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('vendor_id', $vendorId);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
