<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'label',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'address_line',
        'city',
        'district',
        'delivery_instructions',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function formattedAddress(): string
    {
        return collect([$this->address_line, $this->district, $this->city])
            ->filter()
            ->implode(', ');
    }
}
