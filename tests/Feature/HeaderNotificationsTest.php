<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MarketplaceUserSeeder::class);
    }

    public function test_admin_header_shows_capped_unread_count_and_only_the_admins_notifications(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $otherUser = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            $admin->notify(new MarketplaceNotification(
                'Admin notice '.$index,
                'An update for this administrator.',
                route('marketplace.orders.index'),
            ));
        }

        $otherUser->notify(new MarketplaceNotification(
            'Private notification',
            'This belongs to another account.',
            route('marketplace.orders.index'),
        ));

        $response = $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('10+')
            ->assertSee('<i class="bi bi-bell"></i>', false)
            ->assertDontSee('Private notification')
            ->assertDontSee('title="Pending Testimonials"')
            ->assertSee(route('marketplace.notifications.index'))
            ->assertDontSee('data-bs-toggle="dropdown"');

        $header = substr((string) $response->getContent(), strpos((string) $response->getContent(), '<header id="topbar">'));
        $header = substr($header, 0, strpos($header, '</header>'));
        $this->assertStringNotContainsString('bi-chat-quote', $header);
        $this->assertStringNotContainsString('bi-inbox', $header);
        $this->assertStringContainsString('bi-person-circle', $header);
    }

    public function test_admin_header_hides_an_empty_badge_and_displays_small_counts_verbatim(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->assertMatchesRegularExpression(
            '/id="notificationCountBadge" class="notification-count-badge\s*"[^>]*\shidden\s*>/',
            (string) $response->getContent(),
        );

        $admin->notify(new MarketplaceNotification('One update', 'A single unread notification.'));
        $response = $this->get(route('dashboard'))->assertOk();
        $this->assertMatchesRegularExpression(
            '/id="notificationCountBadge" class="notification-count-badge(?:\s+is-overflow)?\s*"[^>]*>\s*1\s*<\/span>/',
            (string) $response->getContent(),
        );
    }

    public function test_notification_count_is_user_scoped_and_decreases_after_opening_a_notification(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $otherUser = User::factory()->create();

        $notification = new MarketplaceNotification(
            'Open this order',
            'Order details are ready.',
            route('marketplace.orders.index'),
        );
        $admin->notify($notification);
        $admin->notify(new MarketplaceNotification('Another update', 'Another update body.'));
        $otherUser->notify(new MarketplaceNotification('Other account update', 'Not for this user.'));

        $this->actingAs($admin)
            ->getJson(route('marketplace.notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 2]);

        $notificationRecord = $admin->notifications()->where('data->title', 'Open this order')->firstOrFail();
        $this->post(route('marketplace.notifications.read', $notificationRecord->id))
            ->assertRedirect(route('marketplace.orders.index'));

        $this->getJson(route('marketplace.notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 1]);
        $this->assertNotNull($notificationRecord->fresh()->read_at);
    }

    public function test_vendor_header_links_to_the_notification_centre_and_shows_only_its_unread_count(): void
    {
        $vendor = Vendor::query()->create([
            'business_name' => 'Header Test Vendor',
            'status' => 'active',
            'email' => 'header-vendor@example.com',
        ]);
        $vendorUser = User::factory()->create(['vendor_id' => $vendor->id]);
        $vendorUser->assignRole('vendor');
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $vendorUser->notify(new MarketplaceNotification(
            'Vendor update',
            'A relevant vendor notification.',
            route('vendor.orders.index'),
        ));
        $admin->notify(new MarketplaceNotification('Admin-only update', 'Private admin content.'));

        $response = $this->actingAs($vendorUser)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('Vendor Panel')
            ->assertSee('<i class="bi bi-bell"></i>', false)
            ->assertDontSee('Admin-only update')
            ->assertSee('href="'.route('marketplace.notifications.index').'"', false)
            ->assertSee('notification-count-badge', false)
            ->assertSee('1')
            ->assertDontSee('data-bs-toggle="dropdown"');

        $header = substr((string) $response->getContent(), strpos((string) $response->getContent(), '<header id="topbar">'));
        $header = substr($header, 0, strpos($header, '</header>'));
        $this->assertStringNotContainsString('dropdown-menu', $header);
        $this->assertStringContainsString('bi-person-circle', $header);
    }
}
