<?php

namespace Tests\Feature;

use App\Exceptions\InventoryException;
use App\Models\OrderStatusHistory;
use App\Models\Permission;
use App\Models\ProductLocation;
use App\Models\ProductRequest;
use App\Models\Role;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Support\PermissionCatalog;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private Shopcategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Shopcategory::query()->create([
            'name' => 'General',
            'slug' => 'general',
            'active' => true,
        ]);
    }

    public function test_public_catalog_hides_vendors_locations_and_unapproved_products(): void
    {
        $vendor = $this->vendor('Secret Vendor Alpha');
        $visible = $this->product([
            'product_name' => 'Visible Drill',
            'slug' => 'visible-drill',
            'vendor_id' => $vendor->id,
            'internal_notes' => 'Do not tell the customer about supplier',
            'acquisition_cost' => 999,
        ]);
        ProductLocation::query()->create([
            'shop_product_id' => $visible->id,
            'warehouse_name' => 'Warehouse Kigali Private Bin A-19',
            'quantity' => 4,
        ]);
        $hidden = $this->product([
            'product_name' => 'Hidden Drill',
            'slug' => 'hidden-drill',
            'approval_status' => 'pending_approval',
            'publication_status' => 'unpublished',
            'vendor_id' => $vendor->id,
        ]);

        $this->get('/shop')->assertOk()->assertSee('Visible Drill')->assertDontSee('Hidden Drill')->assertDontSee('Secret Vendor Alpha')->assertDontSee('Warehouse Kigali Private Bin A-19');
        $this->get(route('shop-products.show', $visible))->assertOk()->assertDontSee('Secret Vendor Alpha')->assertDontSee('Warehouse Kigali Private Bin A-19')->assertDontSee('Do not tell the customer');
        $this->get(route('shop-products.show', $hidden))->assertNotFound();

        $this->getJson('/api/shop-products')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Visible Drill'])
            ->assertJsonMissingPath('0.vendor_id')
            ->assertDontSee('Secret Vendor Alpha')
            ->assertDontSee('acquisition_cost')
            ->assertDontSee('Warehouse Kigali Private Bin A-19');

        $this->getJson('/search?q=Visible')
            ->assertOk()
            ->assertDontSee('Secret Vendor Alpha');

        $loaded = $visible->fresh()->load('vendor', 'locations');
        $this->assertArrayNotHasKey('vendor_id', $loaded->toArray());
        $this->assertArrayNotHasKey('vendor', $loaded->toArray());
        $this->assertArrayNotHasKey('acquisition_cost', $loaded->toArray());
    }

    public function test_vendor_submission_cannot_publish_or_cross_into_another_vendor(): void
    {
        $vendorA = $this->vendor('Vendor A Private');
        $vendorB = $this->vendor('Vendor B Private');
        $vendorUser = $this->user('vendor', $vendorA);
        $otherUser = $this->user('vendor', $vendorB);
        $manager = $this->user('product-manager');
        $admin = $this->user('administrator');

        $this->actingAs($vendorUser)->post(route('vendor.products.store'), [
            'product_name' => 'Vendor Saw',
            'price' => 20000,
            'working_days' => 3,
            'status' => 'in_stock',
            'shop_category_id' => $this->category->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
            'submit_for_review' => 1,
            'vendor_id' => $vendorB->id,
        ])->assertRedirect();

        $product = ShopProduct::query()->where('product_name', 'Vendor Saw')->firstOrFail();
        $this->assertSame('pending_approval', $product->approval_status);
        $this->assertSame('unpublished', $product->publication_status);
        $this->assertSame($vendorA->id, $product->vendor_id);
        $this->get('/shop')->assertDontSee('Vendor Saw');

        $this->actingAs($vendorUser)->post(route('marketplace.products.approve', $product))->assertForbidden();
        $approve = Permission::query()->where('slug', 'products.approve')->firstOrFail();
        $vendorUser->directPermissions()->attach($approve->id);
        $vendorUser->forgetPermissionCache();
        $this->actingAs($vendorUser)->post(route('marketplace.products.approve', $product))->assertForbidden();

        $this->actingAs($manager)->post(route('marketplace.products.approve', $product))->assertForbidden();
        $this->actingAs($admin)->post(route('marketplace.products.approve', $product), ['note' => 'Looks good'])->assertRedirect();
        $this->assertSame('approved', $product->fresh()->approval_status);
        $this->assertNotSame('published', $product->fresh()->publication_status);

        $foreign = $this->product(['vendor_id' => $vendorB->id, 'slug' => 'foreign-item', 'product_name' => 'Foreign Item']);
        $this->actingAs($vendorUser)->get(route('vendor.products.edit', $foreign->slug))->assertNotFound();
    }

    public function test_allocation_tracking_inventory_and_existing_storefront(): void
    {
        $vendor = $this->vendor('Allocated Supplier');
        $officer = $this->user('request-officer');
        $vendorUser = $this->user('vendor', $vendor);
        $otherVendorUser = $this->user('vendor', $this->vendor('Other Supplier'));
        $admin = $this->user('administrator');
        $customer = $this->user('customer');

        $product = $this->product([
            'product_name' => 'Tracked Pump',
            'slug' => 'tracked-pump',
            'tracks_inventory' => true,
            'stock_quantity' => 1,
            'vendor_id' => $vendor->id,
        ]);
        $location = ProductLocation::query()->create([
            'shop_product_id' => $product->id,
            'warehouse_name' => 'Private Depot Gikondo',
            'quantity' => 1,
            'is_primary' => true,
        ]);

        $this->actingAs($customer)->post(route('product-requests.store'), [
            'product_slug' => $product->slug,
            'customer_name' => 'Aline',
            'phone' => '0788111222',
            'quantity' => 1,
            'delivery_location' => 'Kigali',
        ])->assertRedirect();

        $request = ProductRequest::query()->firstOrFail();
        $this->actingAs($officer)->get(route('marketplace.requests.show', $request))
            ->assertOk()
            ->assertSee('Private Depot Gikondo')
            ->assertSee('Allocated Supplier');

        $this->actingAs($officer)->post(route('marketplace.requests.allocate', $request), [
            'vendor_id' => $vendor->id,
            'product_location_id' => $location->id,
        ])->assertRedirect();

        $this->actingAs($vendorUser)->get(route('vendor.requests.show', $request->reference))->assertOk();
        $this->actingAs($otherVendorUser)->get(route('vendor.requests.show', $request->reference))->assertNotFound();

        $order = app(OrderService::class)->place([
            'name' => 'Aline',
            'email' => 'aline@example.com',
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'payment_method' => 'cash_on_delivery',
        ], [['product' => $product->fresh(), 'quantity' => 1]], $customer);

        $this->actingAs($vendorUser)->get(route('vendor.orders.show', $order->reference))->assertOk();
        $this->actingAs($otherVendorUser)->get(route('vendor.orders.show', $order->reference))->assertNotFound();
        $this->actingAs($otherVendorUser)->get(route('vendor.orders.index'))->assertOk()->assertDontSee('Tracked Pump');

        $this->expectException(InventoryException::class);
        app(InventoryService::class)->reserve($product->fresh(), 1, $order, $admin);
    }

    public function test_order_history_and_secure_tracking(): void
    {
        $admin = $this->user('administrator');
        $customer = $this->user('customer');
        $stranger = $this->user('customer');
        $product = $this->product(['slug' => 'hose', 'product_name' => 'Garden Hose']);
        ProductLocation::query()->create([
            'shop_product_id' => $product->id,
            'warehouse_name' => 'Private Depot Gikondo',
            'quantity' => 3,
        ]);

        $order = app(OrderService::class)->place([
            'name' => 'Aline',
            'email' => 'aline@example.com',
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'payment_method' => 'cash_on_delivery',
        ], [['product' => $product, 'quantity' => 1]], $customer);

        $this->actingAs($admin)->post(route('marketplace.orders.status', $order), [
            'fulfillment_status' => 'confirmed',
            'customer_message' => 'Order confirmed',
        ])->assertRedirect();

        $this->assertTrue(OrderStatusHistory::query()->where('order_id', $order->id)->where('to_status', 'confirmed')->exists());
        $this->assertStringNotContainsString('Private Depot', (string) $customer->notifications()->latest()->first()?->data['body']);

        $this->post(route('track-order.submit'), [
            'reference' => $order->reference,
            'verification' => '0700000000',
        ])->assertSessionHasErrors('reference');

        $this->post(route('track-order.submit'), [
            'reference' => $order->reference,
            'verification' => 'aline@example.com',
        ])->assertRedirect(route('track-order.result'));

        $this->get(route('track-order.result'))
            ->assertOk()
            ->assertSee('Order confirmed')
            ->assertDontSee('Private Depot Gikondo')
            ->assertDontSee('acquisition');

        $this->actingAs($stranger)->get(route('account.orders.show', $order->reference))->assertNotFound();
        $this->actingAs($customer)->get(route('account.orders.show', $order->reference))->assertOk()->assertDontSee('Private Depot Gikondo');
    }

    public function test_existing_public_routes_and_legacy_product_backfill(): void
    {
        $this->get('/')->assertOk();
        $this->get('/shop')->assertOk();
        $this->get('/services')->assertOk();
        $this->get('/request-service')->assertOk();
        $this->get('/for-partners')->assertOk();
        $this->get('/cart')->assertOk();

        $id = DB::table('shop_products')->insertGetId([
            'product_name' => 'Legacy Lamp',
            'slug' => 'legacy-lamp',
            'status' => 'in_stock',
            'price' => 5000,
            'working_days' => 1,
            'shop_category_id' => $this->category->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNull(ShopProduct::query()->find($id)->approval_status);
        PermissionCatalog::backfillProducts();
        $this->assertSame('approved', ShopProduct::query()->find($id)->approval_status);
        $this->get('/shop')->assertSee('Legacy Lamp');

        $customer = $this->user('customer');
        $this->actingAs($customer)->get('/admin/services')->assertForbidden();
        $this->actingAs($customer)->get('/dashboard')->assertRedirect(route('account.orders'));

        $admin = $this->user('administrator');
        $officer = $this->user('product-manager');
        $requestRole = Role::query()->where('slug', 'request-officer')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Pending approvals');
        $this->actingAs($admin)->get(route('marketplace.users.index'))->assertOk()->assertSee('Save access');
        $this->actingAs($admin)->put(route('marketplace.users.update', $officer), [
            'roles' => [$requestRole->id],
        ])->assertRedirect();

        $officer->refresh();
        $this->assertTrue($officer->hasRole('request-officer'));
        $this->assertFalse($officer->hasRole('product-manager'));
        $this->actingAs($officer)->post(route('marketplace.products.approve', $this->product()))->assertForbidden();
    }

    public function test_user_seeder_creates_every_marketplace_role(): void
    {
        $this->seed(MarketplaceUserSeeder::class);

        foreach (array_keys(PermissionCatalog::roles()) as $slug) {
            $this->assertTrue(
                User::query()->whereHas('roles', fn ($query) => $query->where('slug', $slug))->exists(),
                $slug
            );
        }

        $vendorUser = User::query()->where('email', 'vendor@example.com')->first();
        $this->assertNotNull($vendorUser?->vendor_id);
        $this->assertSame('active', $vendorUser->vendor->status);
        $this->assertTrue($vendorUser->hasRole('vendor'));
        $this->assertTrue(User::query()->where('email', 'super@example.com')->first()->hasRole('super-administrator'));
    }

    private function vendor(string $name): Vendor
    {
        return Vendor::query()->create([
            'business_name' => $name,
            'status' => 'active',
            'email' => strtolower(str_replace(' ', '', $name)).'@example.com',
            'phone' => '0788000000',
        ]);
    }

    private function user(string $role, ?Vendor $vendor = null): User
    {
        $user = User::factory()->create([
            'vendor_id' => $vendor?->id,
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function product(array $overrides = []): ShopProduct
    {
        return ShopProduct::query()->create(array_merge([
            'product_name' => 'Catalog Item',
            'slug' => 'catalog-item-'.str()->random(6),
            'status' => 'in_stock',
            'price' => 15000,
            'working_days' => 2,
            'shop_category_id' => $this->category->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ], $overrides));
    }
}
