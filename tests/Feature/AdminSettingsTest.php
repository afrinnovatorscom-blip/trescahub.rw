<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);
    }

    public function test_admin_settings_navigation_and_shopping_orders_page_are_reachable(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.settings.index'), false);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee(route('admin.settings.shopping-orders'), false);

        $this->actingAs($admin)
            ->get(route('admin.settings.shopping-orders'))
            ->assertOk()
            ->assertSee('Shopping & Orders')
            ->assertSee('Order Lifecycle Workflow')
            ->assertSee(route('admin.settings.index'), false);
    }

    public function test_existing_admin_settings_pages_open_successfully(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        foreach ([
            'admin.settings.profile',
            'admin.settings.shopping-orders',
            'admin.settings.payments-pricing',
            'admin.settings.delivery',
            'admin.settings.products-stock',
            'admin.settings.vendors-partners',
        ] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }
    }

    public function test_settings_pages_remain_restricted_to_admin_access_users(): void
    {
        $vendor = User::query()->where('email', 'vendor@example.com')->firstOrFail();

        $this->actingAs($vendor)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_unavailable_administration_settings_do_not_link_to_error_pages(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Team & Permissions', false)
            ->assertSee('Messages & Alerts', false)
            ->assertSee('Website Appearance')
            ->assertSee('Security & System', false)
            ->assertDontSee('Unavailable')
            ->assertDontSee('href="'.route('admin.settings.team-permissions').'"', false)
            ->assertDontSee('href="'.route('admin.settings.messages-alerts').'"', false)
            ->assertDontSee('href="'.route('admin.settings.website-appearance').'"', false)
            ->assertDontSee('href="'.route('admin.settings.security-system').'"', false);

        foreach ([
            'admin.settings.team-permissions' => 'Team & Permissions settings are not available yet.',
            'admin.settings.messages-alerts' => 'Messages & Alerts settings are not available yet.',
            'admin.settings.website-appearance' => 'Website Appearance settings are not available yet.',
            'admin.settings.security-system' => 'Security & System settings are not available yet.',
        ] as $routeName => $message) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertRedirect(route('admin.settings.index'))
                ->assertSessionHasErrors(['settings' => $message]);
        }
    }
}
