<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\ShopProduct;
use App\Models\Shopcategory;
use App\Notifications\MarketplaceNotification;
use App\Services\MarketplaceNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $vendorUser;

    private User $otherVendorUser;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MarketplaceUserSeeder::class);
        $this->vendorUser = $this->createVendorUser('Primary Notification Vendor');
        $this->otherVendorUser = $this->createVendorUser('Other Notification Vendor');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrator');
    }

    public function test_vendor_notification_centre_filters_searches_and_displays_only_the_signed_in_vendors_records(): void
    {
        $this->vendorUser->notify(new MarketplaceNotification(
            'Order allocated',
            'Order VENDOR-ORDER-100 includes your product.',
            route('vendor.orders.show', 'VENDOR-ORDER-100'),
        ));
        $this->vendorUser->notify(new MarketplaceNotification('Delivery update', 'Delivery VENDOR-DEL-200 was updated.'));
        $this->vendorUser->notifications()->where('data->title', 'Delivery update')->firstOrFail()->markAsRead();
        $this->otherVendorUser->notify(new MarketplaceNotification('Other vendor private update', 'Not this vendor.'));
        $this->admin->notify(new MarketplaceNotification('Admin private update', 'Not the vendor.'));

        $this->actingAs($this->vendorUser)
            ->get(route('marketplace.notifications.index'))
            ->assertOk()
            ->assertSee('Stay updated on important activity in your vendor account.')
            ->assertDontSee('<div class="notification-summary" aria-label="Notification summary">', false)
            ->assertSee('notification-card-link is-unread', false)
            ->assertSee('notification-card-link is-read', false)
            ->assertSee('Order allocated')
            ->assertSee('Delivery VENDOR-DEL-200 was updated.')
            ->assertDontSee('Other vendor private update')
            ->assertDontSee('Admin private update')
            ->assertDontSee('Read</span>')
            ->assertDontSee('Unread</span>');

        $this->get(route('marketplace.notifications.index', ['filter' => 'unread', 'q' => 'VENDOR-ORDER-100']))
            ->assertOk()
            ->assertSee('Order allocated')
            ->assertDontSee('Delivery update')
            ->assertDontSee('Other vendor private update');

        $this->get(route('marketplace.notifications.index', ['filter' => 'read']))
            ->assertOk()
            ->assertSee('Delivery update')
            ->assertDontSee('Order allocated');
    }

    public function test_vendor_opens_notification_to_mark_it_read_and_redirect_to_its_saved_destination(): void
    {
        $this->vendorUser->notify(new MarketplaceNotification(
            'Order allocated',
            'Order VENDOR-ORDER-300 needs review.',
            route('vendor.orders.show', 'VENDOR-ORDER-300'),
        ));
        $notification = $this->vendorUser->notifications()->firstOrFail();

        $this->actingAs($this->vendorUser)
            ->getJson(route('marketplace.notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 1]);

        $this->post(route('marketplace.notifications.read', $notification->id))
            ->assertRedirect(route('vendor.orders.show', 'VENDOR-ORDER-300'));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->getJson(route('marketplace.notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 0]);

        $response = $this->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('notificationCountBadge', false);
        $this->assertMatchesRegularExpression(
            '/id="notificationCountBadge" class="notification-count-badge[^"]*"[^>]*hidden/',
            (string) $response->getContent(),
        );
    }

    public function test_mark_all_reads_only_the_signed_in_vendors_records(): void
    {
        $this->vendorUser->notify(new MarketplaceNotification('Vendor update one', 'First vendor update.'));
        $this->vendorUser->notify(new MarketplaceNotification('Vendor update two', 'Second vendor update.'));
        $this->otherVendorUser->notify(new MarketplaceNotification('Other vendor update', 'Keep unread.'));
        $this->admin->notify(new MarketplaceNotification('Admin update', 'Keep unread.'));
        $otherVendorNotification = $this->otherVendorUser->notifications()->firstOrFail();
        $adminNotification = $this->admin->notifications()->firstOrFail();

        $this->actingAs($this->vendorUser)
            ->post(route('marketplace.notifications.read-all'))
            ->assertRedirect(route('marketplace.notifications.index'))
            ->assertSessionHas('success', 'All notifications have been marked as read.');

        $this->assertSame(0, $this->vendorUser->unreadNotifications()->count());
        $this->assertNull($otherVendorNotification->fresh()->read_at);
        $this->assertNull($adminNotification->fresh()->read_at);

        $this->get(route('marketplace.notifications.index'))
            ->assertOk()
            ->assertSee('notification-card-link is-read', false)
            ->assertSee('disabled', false);

        $this->getJson(route('marketplace.notifications.unread-count'))
            ->assertOk()
            ->assertExactJson(['count' => 0]);
    }

    public function test_vendor_header_shows_capped_count_and_hides_badge_when_empty(): void
    {
        $response = $this->actingAs($this->vendorUser)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('notificationCountBadge', false);
        $this->assertMatchesRegularExpression(
            '/id="notificationCountBadge" class="notification-count-badge[^"]*"[^>]*hidden/',
            (string) $response->getContent(),
        );

        for ($index = 1; $index <= 11; $index++) {
            $this->vendorUser->notify(new MarketplaceNotification('Vendor update '.$index, 'Unread vendor notice.'));
        }

        $response = $this->get(route('vendor.dashboard'))->assertOk()->assertSee('10+');
        $this->assertStringContainsString(
            'href="'.route('marketplace.notifications.index').'"',
            (string) $response->getContent(),
        );
    }

    public function test_low_stock_notifications_are_sent_to_admin_inventory_users_not_vendors(): void
    {
        $category = Shopcategory::query()->create([
            'name' => 'Low Stock Notification Category',
            'slug' => 'low-stock-notification-category',
            'active' => true,
        ]);
        $product = ShopProduct::query()->create([
            'product_name' => 'Vendor-owned low stock product',
            'slug' => 'vendor-owned-low-stock-product',
            'status' => 'out_of_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $category->id,
            'vendor_id' => $this->vendorUser->vendor_id,
            'tracks_inventory' => true,
            'stock_quantity' => 0,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
        ]);

        app(MarketplaceNotifier::class)->lowStock($product);

        $this->assertSame(1, $this->admin->notifications()->where('data->title', 'Low stock')->count());
        $this->assertSame(0, $this->vendorUser->notifications()->count());
        $this->assertSame(0, $this->otherVendorUser->notifications()->count());
    }

    public function test_product_decision_notifications_go_to_the_vendor_that_owns_a_staff_created_product(): void
    {
        $category = Shopcategory::query()->create([
            'name' => 'Product Decision Notification Category',
            'slug' => 'product-decision-notification-category',
            'active' => true,
        ]);
        $product = ShopProduct::query()->create([
            'product_name' => 'Staff-created vendor product',
            'slug' => 'staff-created-vendor-product',
            'status' => 'in_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $category->id,
            'vendor_id' => $this->vendorUser->vendor_id,
            'created_by' => $this->admin->id,
        ]);

        app(MarketplaceNotifier::class)->productDecision($product, 'approved', null);

        $this->assertSame(1, $this->vendorUser->notifications()->where('data->title', 'Product approved')->count());
        $this->assertSame(0, $this->otherVendorUser->notifications()->count());
        $this->assertSame(0, $this->admin->notifications()->count());
    }

    private function createVendorUser(string $businessName): User
    {
        $vendor = Vendor::query()->create([
            'business_name' => $businessName,
            'status' => 'active',
            'email' => str_replace(' ', '-', strtolower($businessName)).'@example.com',
        ]);
        $user = User::factory()->create(['vendor_id' => $vendor->id]);
        $user->assignRole('vendor');

        return $user->fresh();
    }
}
