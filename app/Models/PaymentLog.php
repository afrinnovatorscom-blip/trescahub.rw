<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PaymentLog extends Model
{
    public const TYPES = [
        'request' => 'Payment request',
        'callback' => 'Callback',
        'status_check' => 'Status check',
    ];

    protected $fillable = [
        'payment_transaction_id', 'request_transaction_id', 'type', 'response_code',
        'status', 'message', 'payload', 'response', 'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * Write a log entry. Never throws, so logging can't break a payment or callback.
     */
    public static function record(string $type, ?PaymentTransaction $transaction, array $attributes = []): ?self
    {
        try {
            if (isset($attributes['message'])) {
                $attributes['message'] = mb_substr((string) $attributes['message'], 0, 255);
            }

            return static::query()->create($attributes + [
                'type' => $type,
                'payment_transaction_id' => $transaction?->id,
                'request_transaction_id' => $transaction?->request_transaction_id,
            ]);
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Could not write payment log', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
