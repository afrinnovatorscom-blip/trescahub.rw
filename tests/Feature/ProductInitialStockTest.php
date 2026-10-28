<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductInitialStockTest extends TestCase
{
    use RefreshDatabase;

    private Shopcategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);

        $this->category = Shopcategory::query()->create([
            'name' => 'Initial Stock Test Products',
            'slug' => 'initial-stock-test-products',
            'active' => true,
        ]);
    }

    public function test_admin_product_creation_assigns_existing_vendor_and_records_initial_stock_once(): void
    {
        $admin = $this->admin();
        $vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.shop-products.create'))
            ->assertOk()
            ->assertSee('Additional Settings')
            ->assertSee('Assign to Vendor')
            ->assertDontSee('Acquisition Cost')
            ->assertDontSee('Internal Notes')
            ->assertDontSee('SKU (Automatically Assigned)')
            ->assertSee('Initial Stock Quantity');

        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Admin Stocked Product',
            'vendor_id' => $vendor->id,
            'tracks_inventory' => 1,
            'initial_stock_quantity' => 12,
            'stock_unit' => 'boxes',
        ]))->assertRedirect(route('admin.shop-products.index'));

        $product = ShopProduct::query()->where('product_name', 'Admin Stocked Product')->firstOrFail();
        $this->assertSame($vendor->id, $product->vendor_id);
        $this->assertSame('approved', $product->approval_status);
        $this->assertSame(12, $product->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'shop_product_id' => $product->id,
            'type' => 'initial_stock',
            'quantity_delta' => 12,
            'stock_before' => 0,
            'stock_after' => 12,
        ]);

        $this->actingAs($admin)->get(route('admin.shop-products.edit', $product))
            ->assertOk()
            ->assertDontSee('name="initial_stock_quantity"', false)
            ->assertSee('12 boxes');

        $this->actingAs($admin)->put(route('admin.shop-products.update', $product), $this->payload([
            'product_name' => $product->product_name,
            'vendor_id' => $vendor->id,
            'tracks_inventory' => 1,
            'stock_unit' => 'boxes',
        ]))->assertRedirect(route('admin.shop-products.index'));

        $this->assertSame(12, $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('shop_product_id', $product->id)->where('type', 'initial_stock')->count());
    }

    public function test_vendor_initial_stock_is_pending_until_product_approval_and_is_registered_once(): void
    {
        $vendorUser = User::query()->where('email', 'vendor@example.com')->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($vendorUser)->get(route('vendor.products.create'))
            ->assertOk()
            ->assertDontSee('Additional Settings')
            ->assertDontSee('Assign to Vendor')
            ->assertSee('Initial Stock Quantity');

        $this->actingAs($vendorUser)->post(route('vendor.products.store'), $this->payload([
            'product_name' => 'Vendor Pending Stock Product',
            'tracks_inventory' => 1,
            'initial_stock_quantity' => 8,
            'stock_unit' => 'cartons',
        ]))->assertRedirect(route('vendor.products.index'));

        $product = ShopProduct::query()->where('product_name', 'Vendor Pending Stock Product')->firstOrFail();
        $this->assertSame('pending_approval', $product->approval_status);
        $this->assertSame('unpublished', $product->publication_status);
        $this->assertSame(0, $product->stock_quantity);
        $this->assertSame(8, $product->pending_payload['initial_stock_quantity']);
        $this->assertSame(0, InventoryMovement::query()->where('shop_product_id', $product->id)->count());
        $this->assertFalse(ShopProduct::query()->publicCatalog()->whereKey($product->id)->exists());

        $this->flushSession();
        $approvalQueue = $this->actingAs($admin)->get(route('marketplace.approvals.index'));
        $this->assertSame(200, $approvalQueue->status(), (string) $approvalQueue->headers->get('Location'));
        $approvalQueue
            ->assertSee('8 cartons')
            ->assertSee(route('admin.shop-products.show', $product), false);
        $this->actingAs($admin)->get(route('admin.shop-products.show', $product))
            ->assertOk()
            ->assertSee('Unapproved initial stock')
            ->assertSee('8 cartons');

        $restrictedReviewer = User::factory()->create();
        $restrictedReviewer->assignRole('product-manager');
        $approvePermission = Permission::query()->where('slug', 'products.approve')->firstOrFail();
        $restrictedReviewer->directPermissions()->attach($approvePermission->id);
        $restrictedReviewer->forgetPermissionCache();
        $this->flushSession();
        $this->actingAs($restrictedReviewer)->post(route('marketplace.products.approve', $product))->assertForbidden();
        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->where('shop_product_id', $product->id)->count());

        $this->flushSession();
        $this->actingAs($admin)->post(route('marketplace.products.approve', $product))->assertRedirect();
        $this->actingAs($admin)->post(route('marketplace.products.approve', $product))->assertRedirect();

        $product->refresh();
        $this->assertSame('approved', $product->approval_status);
        $this->assertSame(8, $product->stock_quantity);
        $this->assertNull($product->pending_payload);
        $this->assertSame(1, InventoryMovement::query()->where('shop_product_id', $product->id)->where('type', 'initial_stock')->count());
        $this->assertFalse($product->isPubliclyListed());
    }

    public function test_rejection_and_digital_products_do_not_register_physical_initial_stock(): void
    {
        $vendorUser = User::query()->where('email', 'vendor@example.com')->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($vendorUser)->from(route('vendor.products.create'))
            ->post(route('vendor.products.store'), $this->payload([
                'product_name' => 'Tracked Product Missing Quantity',
                'tracks_inventory' => 1,
                'stock_unit' => 'units',
            ]))
            ->assertRedirect(route('vendor.products.create'))
            ->assertSessionHasErrors('initial_stock_quantity');

        $this->actingAs($vendorUser)->from(route('vendor.products.create'))
            ->post(route('vendor.products.store'), $this->payload([
                'product_name' => 'Tracked Product Negative Quantity',
                'tracks_inventory' => 1,
                'initial_stock_quantity' => -1,
                'stock_unit' => 'units',
            ]))
            ->assertRedirect(route('vendor.products.create'))
            ->assertSessionHasErrors('initial_stock_quantity');

        $this->actingAs($vendorUser)->post(route('vendor.products.store'), $this->payload([
            'product_name' => 'Digital Service Without Stock',
        ]))->assertRedirect(route('vendor.products.index'));
        $digital = ShopProduct::query()->where('product_name', 'Digital Service Without Stock')->firstOrFail();
        $this->assertFalse($digital->tracks_inventory);
        $this->assertSame(0, $digital->stock_quantity);
        $this->assertArrayNotHasKey('initial_stock_quantity', $digital->pending_payload ?? []);

        $this->actingAs($vendorUser)->post(route('vendor.products.store'), $this->payload([
            'product_name' => 'Rejected Product With Pending Stock',
            'tracks_inventory' => 1,
            'initial_stock_quantity' => 4,
            'stock_unit' => 'pieces',
        ]))->assertRedirect(route('vendor.products.index'));
        $rejected = ShopProduct::query()->where('product_name', 'Rejected Product With Pending Stock')->firstOrFail();

        $this->flushSession();
        $rejectResponse = $this->actingAs($admin)->post(route('marketplace.products.reject', $rejected), [
            'note' => 'Initial stock could not be verified.',
        ]);
        $rejectResponse->assertRedirect()->assertSessionHasNoErrors();

        $rejected->refresh();
        $this->assertSame('rejected', $rejected->approval_status);
        $this->assertSame('unpublished', $rejected->publication_status);
        $this->assertSame(0, $rejected->stock_quantity);
        $this->assertSame(4, $rejected->pending_payload['initial_stock_quantity']);
        $this->assertSame(0, InventoryMovement::query()->where('shop_product_id', $rejected->id)->count());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Initial Stock Test Product',
            'description' => 'A product for initial stock workflow testing.',
            'price' => 1500,
            'working_days' => 2,
            'status' => 'active',
            'shop_category_id' => $this->category->id,
        ], $overrides);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        return $user->fresh();
    }
}
