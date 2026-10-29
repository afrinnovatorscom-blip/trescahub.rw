<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_and_model_timestamps_use_rwanda_time_without_changing_their_instant(): void
    {
        $this->assertSame('Africa/Kigali', config('app.timezone'));
        $this->assertSame('Africa/Kigali', date_default_timezone_get());
        $this->assertSame('Africa/Kigali', now()->timezoneName);
        $this->assertSame('+02:00', now()->format('P'));
        $this->assertSame(now('UTC')->timestamp, now('Africa/Kigali')->timestamp);
        $this->assertSame('+02:00', config('database.connections.mysql.timezone'));

        $beforeCreate = now()->timestamp;
        $log = AuditLog::query()->create(['action' => 'timezone.test']);
        $log->refresh();

        $this->assertGreaterThanOrEqual($beforeCreate, $log->created_at->timestamp);
        $this->assertLessThanOrEqual(now()->timestamp, $log->created_at->timestamp);
        $this->assertSame('Africa/Kigali', $log->created_at->timezoneName);
        $this->assertSame('+02:00', $log->created_at->format('P'));
    }
}
