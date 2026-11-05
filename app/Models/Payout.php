<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    public const STATUSES = ['pending', 'successful', 'failed'];

    protected $fillable = [
        'user_id', 'mobile_phone', 'amount', 'reason', 'status',
        'request_transaction_id', 'reference_id', 'response_code', 'message', 'response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
