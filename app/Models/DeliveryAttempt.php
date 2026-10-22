<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttempt extends Model
{
    protected $fillable = [
        'delivery_task_id', 'user_id', 'attempted_at', 'result', 'reason', 'note',
    ];

    protected $casts = ['attempted_at' => 'datetime'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(DeliveryTask::class, 'delivery_task_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
