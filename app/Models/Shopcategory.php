<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShopCategory extends Model
{
    use HasFactory;

    protected $table = 'shop_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Slug is the source of truth — derive name from slug if name is empty
            if (empty($model->name) && ! empty($model->slug)) {
                $model->name = Str::title(str_replace('-', ' ', $model->slug));
            }

            // If slug is still empty, generate from name
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            // Slug is the source of truth — derive name from slug if name is empty
            if (empty($model->name) && ! empty($model->slug)) {
                $model->name = Str::title(str_replace('-', ' ', $model->slug));
            }

            // If slug is still empty, generate from name
            if (empty($model->slug) && ! empty($model->name)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * A category is a top-level parent (no parent_id).
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * A category is a child/subcategory (has parent_id).
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * A subcategory belongs to a parent category (unsignedBigInteger FK).
     */
    public function parent()
    {
        return $this->belongsTo(ShopCategory::class, 'parent_id');
    }

    /**
     * A parent category has many subcategories (unsignedBigInteger FK).
     */
    public function subcategories()
    {
        return $this->hasMany(ShopCategory::class, 'parent_id');
    }

    /**
     * Check if this category is a top-level parent.
     */
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if this category is a subcategory.
     */
    public function isChild(): bool
    {
        return ! is_null($this->parent_id);
    }
}
