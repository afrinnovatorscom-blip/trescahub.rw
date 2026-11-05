<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\Shopcategory;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MarketplaceNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_vendor_can_view_and_update_owned_notification_preferences(): void
    {
        $vendor = $this->createVendor('Settings Vendor');
        $otherVendor = $this->createVendor('Other Settings Vendor');
        $user = $this->createVendorUser($vendor);

        $response = $this->actingAs($user)->get(route('vendor.settings'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Order Preferences')
            ->assertSee('Product Preferences')
            ->assertSee('Save Changes')
            ->assertSee('href="'.route('vendor.settings').'" class="nav-item-link active"', false)
            ->assertDontSee('Delivery Preferences')
            ->getContent();

        $this->assertMatchesRegularExpression('/id="order-notifications"[^>]*checked/', $response);
        $this->assertMatchesRegularExpression('/id="product-notifications"[^>]*checked/', $response);

        $this->put(route('vendor.settings.update'), [
            'preferences' => [
                'order_notifications' => '0',
                'product_notifications' => '1',
            ],
            'vendor_id' => $otherVendor->id,
        ])->assertRedirect(route('vendor.settings'))
            ->assertSessionHas('success', 'Notification preferences saved.');

        $this->assertSame([
            'order_notifications' => false,
            'product_notifications' => true,
        ], $vendor->fresh()->notification_preferences);
        $this->assertNull($otherVendor->fresh()->notification_preferences);

        $response = $this->get(route('vendor.settings'))
            ->assertOk()
            ->getContent();
        $this->assertDoesNotMatchRegularExpression('/id="order-notifications"[^>]*checked/', $response);
        $this->assertMatchesRegularExpression('/id="product-notifications"[^>]*checked/', $response);
    }

    public function test_invalid_notification_preference_values_are_rejected(): void
    {
        $vendor = $this->createVendor('Validation Settings Vendor');
        $user = $this->createVendorUser($vendor);

        $this->actingAs($user)->from(route('vendor.settings'))
            ->put(route('vendor.settings.update'), [
                'preferences' => [
                    'order_notifications' => 'sometimes',
                    'product_notifications' => '1',
                ],
            ])
            ->assertRedirect(route('vendor.settings'))
            ->assertSessionHasErrors('preferences.order_notifications');

        $this->assertNull($vendor->fresh()->notification_preferences);
    }

    public function test_notification_preferences_control_order_and_product_decision_notifications(): void
    {
        $vendor = $this->createVendor('Notification Preference Vendor');
        $user = $this->createVendorUser($vendor);
        $vendor->update(['notification_preferences' => [
            'order_notifications' => false,
            'product_notifications' => false,
        ]]);
        $order = Order::query()->create([
            'reference' => 'PREFERENCE-ORDER-001',
            'customer_name' => 'Settings Customer',
            'phone' => '0788000000',
            'delivery_address' => 'Kigali',
        ]);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'product_name' => 'Settings Product',
            'unit_price' => 1000,
            'quantity' => 1,
            'line_total' => 1000,
        ]);
        $category = Shopcategory::query()->create([
            'name' => 'Settings Product Category',
            'slug' => 'settings-product-category',
            'active' => true,
        ]);
        $product = ShopProduct::query()->create([
            'product_name' => 'Preference Product',
            'slug' => 'preference-product',
            'status' => 'in_stock',
            'price' => 1000,
            'working_days' => 1,
            'shop_category_id' => $category->id,
            'vendor_id' => $vendor->id,
        ]);
        $notifier = app(MarketplaceNotifier::class);

        $notifier->orderAllocated($item);
        $notifier->productDecision($product, 'approved', null);

        $this->assertSame(0, $user->notifications()->count());

        $vendor->update(['notification_preferences' => [
            'order_notifications' => true,
            'product_notifications' => true,
        ]]);
        $notifier->orderAllocated($item);
        $notifier->productDecision($product, 'approved', null);

        $this->assertSame(1, $user->notifications()->where('data->title', 'Order allocated')->count());
        $this->assertSame(1, $user->notifications()->where('data->title', 'Product approved')->count());
    }

    public function test_non_vendor_cannot_open_or_update_vendor_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        $this->actingAs($user)->get(route('vendor.settings'))
            ->assertForbidden();
        $this->put(route('vendor.settings.update'), [
            'preferences' => [
                'order_notifications' => '1',
                'product_notifications' => '1',
            ],
        ])->assertForbidden();
    }

    private function createVendor(string $businessName): Vendor
    {
        return Vendor::query()->create([
            'business_name' => $businessName,
            'status' => 'active',
        ]);
    }

    private function createVendorUser(Vendor $vendor): User
    {
        $user = User::factory()->create(['vendor_id' => $vendor->id]);
        $user->assignRole('vendor');

        return $user->fresh();
    }
}
