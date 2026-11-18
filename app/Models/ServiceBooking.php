<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceBooking extends Model
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const COMPLETED = 'completed';

    public const PAID = 'paid';

    public const PARTIALLY_PAID = 'partially_paid';

    public const NOT_PAID = 'not_paid';

    protected $fillable = [
        'reference',
        'service_id',
        'customer_id',
        'submission_key',
        'customer_name',
        'customer_phone',
        'customer_email',
        'preferred_date',
        'preferred_time',
        'appointment_date',
        'appointment_time',
        'requirements',
        'status',
        'payment_status',
        'payment_amount_received',
        'payment_recorded',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'appointment_date' => 'date',
            'payment_amount_received' => 'decimal:2',
            'payment_recorded' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
