<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustmentRequest extends Model
{
    protected $fillable = [
        'shop_product_id',
        'vendor_id',
        'requested_by',
        'reviewed_by',
        'change_type',
        'quantity',
        'stock_before',
        'quantity_delta',
        'status',
        'reason',
        'review_note',
        'reviewed_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'quantity_delta' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
