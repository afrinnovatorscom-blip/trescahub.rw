<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStatusHistory extends Model
{
    protected $fillable = [
        'product_request_id', 'user_id', 'from_status', 'to_status', 'note', 'customer_message', 'meta',
    ];

    protected $casts = ['meta' => 'array'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProductRequest::class, 'product_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
