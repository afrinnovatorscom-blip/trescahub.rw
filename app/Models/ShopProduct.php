<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShopProduct extends Model
{
    use HasFactory;

    protected $table = 'shop_products';

    protected $fillable = [
        'product_name',
        'slug',
        'sku',
        'status',
        'is_popular',
        'description',
        'price',
        'working_days',
        'features',
        'specifications',
        'thumbnail',
        'images',
        'shop_category_id',
        'subcategory_id',
        'approval_status',
        'publication_status',
        'revision_status',
        'pending_payload',
        'vendor_id',
        'acquisition_cost',
        'stock_quantity',
        'reserved_quantity',
        'tracks_inventory',
        'low_stock_threshold',
        'product_type',
        'condition',
        'internal_notes',
        'created_by',
        'submitted_by',
        'approved_by',
        'approved_at',
        'submitted_at',
    ];

    protected $hidden = [
        'vendor_id',
        'acquisition_cost',
        'internal_notes',
        'approved_by',
        'created_by',
        'submitted_by',
        'pending_payload',
        'revision_status',
        'vendor',
        'locations',
        'approvalHistories',
        'inventoryMovements',
    ];

    protected $casts = [
        'is_popular' => 'boolean',
        'features' => 'array',
        'specifications' => 'array',
        'images' => 'array',
        'pending_payload' => 'array',
        'price' => 'decimal:2',
        'acquisition_cost' => 'decimal:2',
        'shop_category_id' => 'integer',
        'subcategory_id' => 'integer',
        'stock_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'tracks_inventory' => 'boolean',
        'low_stock_threshold' => 'integer',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ShopProduct $model) {
            if (empty($model->slug) && ! empty($model->product_name)) {
                $model->slug = Str::slug($model->product_name);
            }

            if (empty($model->approval_status)) {
                $model->approval_status = 'pending_approval';
            }

            if (empty($model->publication_status)) {
                $model->publication_status = 'unpublished';
            }
        });

        static::updating(function (ShopProduct $model) {
            if (empty($model->slug) && ! empty($model->product_name)) {
                $model->slug = Str::slug($model->product_name);
            }
        });

        static::saving(function (ShopProduct $model) {
            if ($model->tracks_inventory) {
                $available = (int) $model->stock_quantity - (int) $model->reserved_quantity;
                $model->status = $available > 0 ? 'in_stock' : 'out_of_stock';
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Shopcategory::class, 'shop_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Shopcategory::class, 'subcategory_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProductLocation::class);
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ProductApprovalHistory::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePublicCatalog($query)
    {
        return $query->where('approval_status', 'approved')
            ->where('publication_status', 'published')
            ->where(function ($inner) {
                $inner->whereNull('vendor_id')
                    ->orWhereIn('vendor_id', function ($sub) {
                        $sub->select('id')->from('vendors')->where('status', 'active');
                    });
            });
    }

    public function scopeOwnedByVendor($query, ?int $vendorId)
    {
        if (! $vendorId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('vendor_id', $vendorId);
    }

    public function availableQuantity(): int
    {
        return max(0, (int) $this->stock_quantity - (int) $this->reserved_quantity);
    }

    public function customerAvailability(): string
    {
        if (! $this->isPubliclyVisible()) {
            return 'unavailable';
        }

        if ($this->tracks_inventory) {
            return $this->availableQuantity() > 0 ? 'in_stock' : 'out_of_stock';
        }

        return $this->status === 'in_stock' ? 'in_stock' : 'out_of_stock';
    }

    public function isPubliclyListed(): bool
    {
        return $this->approval_status === 'approved'
            && $this->publication_status === 'published';
    }

    public function vendorIsSellable(): bool
    {
        if (! $this->vendor_id) {
            return true;
        }

        $status = $this->relationLoaded('vendor')
            ? $this->vendor?->status
            : Vendor::query()->whereKey($this->vendor_id)->value('status');

        return $status === 'active';
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isPubliclyListed() && $this->vendorIsSellable();
    }

    public function canPurchase(int $quantity): bool
    {
        if (! $this->isPubliclyVisible() || $quantity < 1) {
            return false;
        }

        if ($this->customerAvailability() !== 'in_stock') {
            return false;
        }

        if ($this->tracks_inventory && $this->availableQuantity() < $quantity) {
            return false;
        }

        return true;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'RWF '.number_format((float) $this->price, 0, '.', ',');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->customerAvailability() === 'in_stock' ? 'In Stock' : 'Out of Stock';
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $count = 1;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$count++;
        }

        return $slug;
    }
}
