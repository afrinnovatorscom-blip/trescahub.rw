<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public static function log(?User $actor, string $action, ?Model $entity = null, array $changes = [], array $meta = []): void
    {
        AuditLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $entity ? $entity->getMorphClass() : null,
            'auditable_id' => $entity?->getKey(),
            'changes' => self::redact($changes),
            'meta' => self::redact($meta),
            'ip_address' => request()?->ip(),
        ]);
    }

    public static function redact(array $payload): array
    {
        $clean = [];

        foreach ($payload as $key => $value) {
            if (is_string($key) && preg_match('/password|token|secret|remember/i', $key)) {
                continue;
            }

            $clean[$key] = is_array($value) ? self::redact($value) : $value;
        }

        return $clean;
    }
}
