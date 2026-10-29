<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_page_displays_existing_event_details_in_a_responsive_table(): void
    {
        $this->seed(MarketplaceUserSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'product.updated',
            'auditable_type' => 'App\\Models\\ShopProduct',
            'auditable_id' => 42,
            'ip_address' => '192.0.2.10',
        ]);

        $this->actingAs($admin)->get(route('marketplace.audit.index'))
            ->assertOk()
            ->assertSee('Date &amp; time', false)
            ->assertSee('Affected record')
            ->assertSee('IP address')
            ->assertSee('product.updated')
            ->assertSee('ShopProduct')
            ->assertSee('#42')
            ->assertSee('192.0.2.10')
            ->assertSee('table-responsive', false);
    }
}
