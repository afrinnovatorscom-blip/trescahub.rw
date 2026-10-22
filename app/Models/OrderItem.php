<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'shop_product_id', 'vendor_id', 'product_location_id', 'product_name', 'sku',
        'unit_price', 'quantity', 'line_total', 'fulfillment_status', 'assignment_status',
        'inventory_state', 'fulfillment_instructions', 'internal_notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProductLocation::class, 'product_location_id');
    }

    public function scopeOwnedByVendor($query, ?int $vendorId)
    {
        if (! $vendorId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('vendor_id', $vendorId);
    }
}
