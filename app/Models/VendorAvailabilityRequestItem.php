<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAvailabilityRequestItem extends Model
{
    protected $fillable = [
        'vendor_availability_request_id', 'order_item_id', 'ordered_quantity',
        'status', 'available_quantity', 'reason',
    ];

    protected $casts = [
        'ordered_quantity' => 'integer',
        'available_quantity' => 'integer',
    ];

    public function availabilityRequest(): BelongsTo
    {
        return $this->belongsTo(VendorAvailabilityRequest::class, 'vendor_availability_request_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
