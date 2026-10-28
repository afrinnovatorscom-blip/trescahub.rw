<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'reference', 'source', 'submission_key', 'user_id', 'customer_id', 'customer_name', 'email', 'phone', 'delivery_address',
        'delivery_notes', 'subtotal', 'total', 'payment_status', 'payment_method',
        'fulfillment_status', 'customer_status_message', 'internal_notes', 'cancellation_reason',
        'assigned_officer_id', 'delivery_officer_id', 'delivery_estimate_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'delivery_estimate_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function deliveryOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_officer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliveryTask::class);
    }

    public function productRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductRequest::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
