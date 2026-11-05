<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MarketplaceUserSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrator');
        $this->admin = $this->admin->fresh();
    }

    public function test_admin_notification_centre_shows_real_counts_and_state_only_as_row_backgrounds(): void
    {
        $unread = new MarketplaceNotification(
            'Product waiting for review',
            'Review product SKU-101 before publishing.',
            route('marketplace.approvals.index'),
        );
        $this->admin->notify($unread);
        $this->admin->notify(new MarketplaceNotification('Order received', 'Order ORDER-202 is ready.'));
        $this->admin->notifications()->where('data->title', 'Order received')->firstOrFail()->markAsRead();

        $response = $this->actingAs($this->admin)->get(route('marketplace.notifications.index'))
            ->assertOk()
            ->assertSee('Stay updated on important activity across Tresca Hub.')
            ->assertDontSee('<div class="notification-summary" aria-label="Notification summary">', false)
            ->assertSee('Product waiting for review')
            ->assertSee('Order ORDER-202 is ready.')
            ->assertSee('notification-card-link is-unread', false)
            ->assertSee('notification-card-link is-read', false)
            ->assertSee('background: #eff6ff', false)
            ->assertSee('Search notifications')
            ->assertSee('Mark all as read');
        $this->assertSame(['all' => 2, 'unread' => 1, 'read' => 1], $response->viewData('notificationCounts'));

        $content = (string) $response->getContent();
        $cardsStart = strpos($content, '<div class="notification-list">');
        $cardsEnd = strpos($content, '</div>', $cardsStart);
        $this->assertNotFalse($cardsStart);
        $this->assertStringNotContainsString('Mark read and open', substr($content, $cardsStart, $cardsEnd - $cardsStart));
        $this->assertStringNotContainsString('Unread</span>', substr($content, $cardsStart, $cardsEnd - $cardsStart));
        $this->assertStringNotContainsString('Read</span>', substr($content, $cardsStart, $cardsEnd - $cardsStart));
    }

    public function test_admin_can_filter_and_search_notifications(): void
    {
        $this->admin->notify(new MarketplaceNotification('Product approval', 'Review SKU-501.'));
        $this->admin->notify(new MarketplaceNotification('Order update', 'Order ORDER-502 changed.'));
        $this->admin->notifications()->where('data->title', 'Order update')->firstOrFail()->markAsRead();

        $this->actingAs($this->admin)
            ->get(route('marketplace.notifications.index', ['filter' => 'unread', 'q' => 'SKU-501']))
            ->assertOk()
            ->assertSee('Product approval')
            ->assertDontSee('Order update')
            ->assertSee('name="filter" value="unread"', false)
            ->assertSee('value="SKU-501"', false);

        $this->get(route('marketplace.notifications.index', ['filter' => 'read']))
            ->assertOk()
            ->assertSee('Order update')
            ->assertDontSee('Product approval');
    }

    public function test_admin_pagination_is_compact_preserves_filters_and_shows_correct_result_ranges(): void
    {
        for ($index = 1; $index <= 45; $index++) {
            $this->admin->notify(new MarketplaceNotification(
                'Pagination update '.$index,
                'Searchable pagination result '.$index,
            ));
        }

        $pageOne = $this->actingAs($this->admin)
            ->get(route('marketplace.notifications.index', ['filter' => 'unread', 'q' => 'Pagination']))
            ->assertOk()
            ->assertSee('Showing 1–20 of 45 results')
            ->assertSee('notification-pagination-controls', false)
            ->assertSee('aria-label="Previous page"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('rel="next"', false)
            ->assertSee('Page 1', false)
            ->assertSee('Page 2', false)
            ->assertSee('filter=unread')
            ->assertSee('q=Pagination');

        $this->assertMatchesRegularExpression(
            '/<a class="notification-page-link is-direction" href="[^"]*filter=unread&amp;q=Pagination&amp;page=2" rel="next"/',
            (string) $pageOne->getContent(),
        );

        $pageTwo = $this->get(route('marketplace.notifications.index', [
            'filter' => 'unread',
            'q' => 'Pagination',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('Showing 21–40 of 45 results')
            ->assertSee('aria-current="page" aria-label="Page 2"', false)
            ->assertSee('rel="prev"', false)
            ->assertSee('rel="next"', false)
            ->assertSee('filter=unread')
            ->assertSee('q=Pagination');

        $this->assertMatchesRegularExpression(
            '/<a class="notification-page-link is-direction" href="[^"]*filter=unread&amp;q=Pagination&amp;page=3" rel="next"/',
            (string) $pageTwo->getContent(),
        );

        $this->get(route('marketplace.notifications.index', [
            'filter' => 'unread',
            'q' => 'Pagination',
            'page' => 3,
        ]))
            ->assertOk()
            ->assertSee('Showing 41–45 of 45 results')
            ->assertSee('aria-current="page" aria-label="Page 3"', false)
            ->assertSee('rel="prev"', false)
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('filter=unread')
            ->assertSee('q=Pagination');
    }

    public function test_clicking_a_notification_marks_it_read_and_redirects_to_its_saved_destination(): void
    {
        $this->admin->notify(new MarketplaceNotification(
            'Product review required',
            'Review product SKU-700.',
            route('marketplace.approvals.index'),
        ));
        $notification = $this->admin->notifications()->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('marketplace.notifications.read', $notification->id))
            ->assertRedirect(route('marketplace.approvals.index'));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->getJson(route('marketplace.notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 0]);
    }

    public function test_mark_all_reads_only_the_authenticated_admins_notifications_and_refreshes_page_state(): void
    {
        $otherUser = User::factory()->create();
        $this->admin->notify(new MarketplaceNotification('Admin notification one', 'First admin update.'));
        $this->admin->notify(new MarketplaceNotification('Admin notification two', 'Second admin update.'));
        $otherUser->notify(new MarketplaceNotification('Other user notification', 'Must stay unread.'));
        $otherNotification = $otherUser->notifications()->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('marketplace.notifications.read-all'))
            ->assertRedirect(route('marketplace.notifications.index'))
            ->assertSessionHas('success', 'All notifications have been marked as read.');

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
        $this->assertNull($otherNotification->fresh()->read_at);
        $response = $this->get(route('marketplace.notifications.index'))
            ->assertOk()
            ->assertSee('notification-card-link is-read', false)
            ->assertSee('Mark all as read')
            ->assertSee('disabled', false);
        $this->assertMatchesRegularExpression(
            '/id="notificationCountBadge" class="notification-count-badge[^"]*"[^>]*hidden/',
            (string) $response->getContent(),
        );
    }

    public function test_admin_bell_links_to_the_notification_centre_without_opening_a_dropdown(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('marketplace.notifications.index').'"', false)
            ->assertDontSee('data-bs-toggle="dropdown"');

        $header = substr((string) $response->getContent(), strpos((string) $response->getContent(), '<header id="topbar">'));
        $header = substr($header, 0, strpos($header, '</header>'));
        $this->assertStringContainsString('bi-bell', $header);
        $this->assertStringContainsString('bi-person-circle', $header);
        $this->assertStringNotContainsString('dropdown-menu', $header);
    }

    public function test_vendor_notification_page_uses_notification_centre_and_rejects_admin_access_to_other_users(): void
    {
        $vendor = Vendor::query()->create([
            'business_name' => 'Notification Vendor',
            'status' => 'active',
            'email' => 'notification-vendor@example.com',
        ]);
        $vendorUser = User::factory()->create(['vendor_id' => $vendor->id]);
        $vendorUser->assignRole('vendor');
        $vendorUser->notify(new MarketplaceNotification('Vendor update', 'A vendor-only notification.'));

        $this->actingAs($vendorUser)->get(route('marketplace.notifications.index'))
            ->assertOk()
            ->assertSee('Vendor update')
            ->assertSee('Stay updated on important activity in your vendor account.')
            ->assertSee('Mark all as read')
            ->assertSee('Search notifications')
            ->assertSee('notification-card-link is-unread', false);

        $this->actingAs($this->admin)->post(route('marketplace.notifications.read-all'))
            ->assertRedirect(route('marketplace.notifications.index'));

        $this->actingAs($vendorUser)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertDontSee('data-bs-toggle="dropdown"');
    }
}
