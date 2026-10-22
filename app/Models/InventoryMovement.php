<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'shop_product_id', 'product_location_id', 'user_id', 'type', 'quantity_delta',
        'stock_before', 'stock_after', 'reserved_before', 'reserved_after',
        'reference_type', 'reference_id', 'note',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProductLocation::class, 'product_location_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
