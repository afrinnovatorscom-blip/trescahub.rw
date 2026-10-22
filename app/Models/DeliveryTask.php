<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryTask extends Model
{
    protected $fillable = [
        'reference', 'order_id', 'order_item_id', 'vendor_id', 'product_location_id',
        'assigned_officer_id', 'delivery_officer_id', 'status', 'fulfillment_instructions',
        'internal_notes', 'customer_message', 'dispatched_at', 'delivered_at', 'proof_path',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProductLocation::class, 'product_location_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_officer_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
