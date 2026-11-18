<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorAvailabilityRequest extends Model
{
    public const PENDING = 'pending_confirmation';

    public const AVAILABLE = 'available';

    public const PARTIALLY_AVAILABLE = 'partially_available';

    public const UNAVAILABLE = 'unavailable';

    protected $fillable = [
        'order_id', 'vendor_id', 'requested_by', 'status', 'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorAvailabilityRequestItem::class);
    }
}
