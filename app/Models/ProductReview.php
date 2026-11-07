<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    public const STATUSES = ['published', 'hidden'];

    protected $fillable = [
        'shop_product_id', 'customer_id', 'order_id', 'rating', 'title', 'body',
        'status', 'admin_reply', 'admin_replied_at', 'moderated_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'admin_replied_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Public display name: "Aline M." — never the full name or contact details. */
    public function getReviewerNameAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->customer?->name)) ?: [];
        $first = $parts[0] ?? '';
        if ($first === '') {
            return 'Customer';
        }

        $lastInitial = count($parts) > 1 ? ' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : '';

        return $first.$lastInitial;
    }
}
