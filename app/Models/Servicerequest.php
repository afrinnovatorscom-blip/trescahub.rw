<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'name',
        'phone',
        'email',
        'service_category',
        'description',
        'budget',
        'timeline',
        'files',
        'status',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ── Status badge helper ────────────────────────────────────
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'reviewed' => 'primary',
            'completed' => 'success',
            default => 'secondary',
        };
    }
}
