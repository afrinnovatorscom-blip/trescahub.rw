<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLocation extends Model
{
    protected $fillable = [
        'shop_product_id',
        'warehouse_name',
        'district',
        'description',
        'shelf_reference',
        'notes',
        'quantity',
        'reserved_quantity',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function availableQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) $this->reserved_quantity);
    }
}
