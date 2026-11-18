<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'working_day',
        'what_included',
        'how_service_works',
        'classification_name',
        'subcategory',
        'thumbnail',
        'other_images',
        'is_popular',
        'is_in_stock',
    ];

    protected $casts = [
        'what_included'    => 'array',
        'how_service_works' => 'array',
        'subcategory'      => 'array',
        'other_images'     => 'array',
        'price'            => 'decimal:2',
        'is_popular'       => 'boolean',
        'is_in_stock'      => 'boolean',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });

        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'classification_name', 'id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('is_in_stock', true);
    }
}