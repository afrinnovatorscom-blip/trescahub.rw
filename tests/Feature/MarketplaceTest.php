<?php

namespace Tests\Feature;

use App\Exceptions\InventoryException;
use App\Models\Customer;
use App\Models\InventoryAdjustmentRequest;
use App\Models\InventoryMovement;
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
use Illuminate\Http\UploadedFile;
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

    public function test_shop_header_cart_badge_tracks_session_quantities_for_guests_and_customers(): void
    {
        $first = $this->product(['product_name' => 'Cart Item One', 'slug' => 'cart-item-one']);
        $second = $this->product(['product_name' => 'Cart Item Two', 'slug' => 'cart-item-two']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('aria-label="Shopping cart"', false);

        $this->get(route('shop'))
            ->assertOk()
            ->assertSee('aria-label="Shopping cart"', false)
            ->assertSee('bi-cart3', false)
            ->assertDontSee('>0</span>', false);

        $this->get(route('shop-products.show', $first))
            ->assertOk()
            ->assertSee('aria-label="Shopping cart"', false);

        $this->post(route('cart.add'), ['product' => $first->slug, 'quantity' => 1])
            ->assertRedirect(route('shop'));
        foreach ([
            ['category' => 'physical', 'subcategory' => 'all'],
            ['category' => 'digital', 'subcategory' => 'all'],
            ['category' => 'digital', 'subcategory' => 'software'],
        ] as $filters) {
            $this->get(route('shop', $filters))
                ->assertOk()
                ->assertSee('>1</span>', false)
                ->assertSee('style="top:2px;right:1px;', false);
        }

        $this->post(route('cart.add'), ['product' => $first->slug, 'quantity' => 2])
            ->assertRedirect(route('shop'));
        $this->assertSame(3, array_sum(session('marketplace.cart')));
        $this->get(route('shop'))
            ->assertOk()
            ->assertSee('>1</span>', false);

        $this->post(route('cart.add'), ['product' => $second->slug, 'quantity' => 1])
            ->assertRedirect(route('shop'));
        $this->assertSame(4, array_sum(session('marketplace.cart')));
        $this->get(route('shop'))
            ->assertOk()
            ->assertSee('>2</span>', false);

        $this->patch(route('cart.update'), [
            'quantities' => [$first->slug => 5, $second->slug => 2],
        ])->assertRedirect();
        $this->assertSame(7, array_sum(session('marketplace.cart')));
        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('aria-label="Shopping cart, 2 products"', false)
            ->assertSee('>2</span>', false);
        $customer = $this->customerAccountFor(User::factory()->create());
        $this->actingAs($customer, 'customers')->get(route('checkout'))
            ->assertOk()
            ->assertSee('aria-label="Shopping cart, 2 products"', false);

        $this->actingAs($customer, 'customers')->get(route('shop'))
            ->assertOk()
            ->assertSee('>2</span>', false);
        $this->get(route('services'))->assertOk()->assertDontSee('bi-cart3', false);
        $this->get(route('home'))->assertOk()->assertDontSee('bi-cart3', false);
    }

    public function test_cart_shows_line_and_order_totals_and_supports_quantity_updates_removal_and_checkout(): void
    {
        $first = $this->product([
            'product_name' => 'Cart Layout Item One',
            'slug' => 'cart-layout-item-one',
            'price' => 1000,
        ]);
        $second = $this->product([
            'product_name' => 'Cart Layout Item Two',
            'slug' => 'cart-layout-item-two',
            'price' => 2500,
        ]);

        $this->post(route('cart.add'), ['product' => $first->slug, 'quantity' => 2])
            ->assertRedirect(route('shop'));
        $this->post(route('cart.add'), ['product' => $second->slug, 'quantity' => 1])
            ->assertRedirect(route('shop'));

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Cart Layout Item One')
            ->assertSee('Cart Layout Item Two')
            ->assertSee('Item total')
            ->assertSee('RWF 2,000')
            ->assertSee('RWF 2,500')
            ->assertSee('RWF 4,500')
            ->assertSee('Proceed to Checkout')
            ->assertSee('data-cart-remove="cart-layout-item-one"', false)
            ->assertSee('data-cart-step="-1"', false)
            ->assertSee('data-cart-step="1"', false)
            ->assertSee('class="cart-checkout btn btn-success', false)
            ->assertDontSee('Update cart');

        $this->patchJson(route('cart.update'), [
            'quantities' => [$first->slug => 3, $second->slug => 1],
        ])->assertOk()
            ->assertJsonPath('subtotal', 5500)
            ->assertJsonPath('item_count', 2)
            ->assertJsonPath('lines.'.$first->slug, 3000);
        $this->assertSame([$first->slug => 3, $second->slug => 1], session('marketplace.cart'));

        $this->patch(route('cart.update'), [
            'quantities' => [$first->slug => 3, $second->slug => 0],
        ])->assertRedirect();
        $this->assertSame([$first->slug => 3], session('marketplace.cart'));

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Cart Layout Item One')
            ->assertDontSee('Cart Layout Item Two')
            ->assertSee('RWF 3,000')
            ->assertSee('Proceed to Checkout');
        $customer = $this->customerAccountFor(User::factory()->create());
        $this->actingAs($customer, 'customers')->get(route('checkout'))->assertOk();

        $this->patch(route('cart.update'), [
            'quantities' => [$first->slug => 0],
        ])->assertRedirect();
        $this->assertSame([], session('marketplace.cart'));
        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Your cart is empty')
            ->assertSee('Continue shopping');
        $this->get(route('checkout'))->assertRedirect(route('cart'));
    }

    public function test_vendor_submission_cannot_publish_or_cross_into_another_vendor(): void
    {
        $vendorA = $this->vendor('Vendor A Private');
        $vendorB = $this->vendor('Vendor B Private');
        $vendorUser = $this->user('vendor', $vendorA);
        $otherUser = $this->user('vendor', $vendorB);
        $manager = $this->user('product-manager');
        $admin = $this->user('administrator');

        $this->actingAs($vendorUser)->get(route('vendor.products.create'))
            ->assertOk()
            ->assertSee('Product Information')
            ->assertSee('Pricing')
            ->assertSee('Price Variations')
            ->assertSee('Stock')
            ->assertSee('Main Product Image (Thumbnail)')
            ->assertSee('Additional Product Images (Optional)')
            ->assertSee('Product Specifications and Features')
            ->assertSee('Add Specification')
            ->assertSee('Add Feature')
            ->assertSee('Submit Product')
            ->assertDontSee('name="sku"', false)
            ->assertDontSee('Additional Settings')
            ->assertDontSee('Assign to Vendor')
            ->assertDontSee('Track physical stock')
            ->assertDontSee('Enable Discount')
            ->assertDontSee('Delivery Time (working days)')
            ->assertSee('name="working_days" value="1"', false)
            ->assertSee('This quantity stays pending until an administrator approves the product.')
            ->assertDontSee('Approve Now')
            ->assertDontSee('Publish Now')
            ->assertDontSee('Publish after saving');

        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII=');
        $thumbnail = UploadedFile::fake()->createWithContent('vendor-main.png', $imageContent);
        $galleryImage = UploadedFile::fake()->createWithContent('vendor-side.png', $imageContent.'side');
        $this->actingAs($vendorUser)->post(route('vendor.products.store'), [
            'product_name' => 'Vendor Saw',
            'price' => 20000,
            'working_days' => 3,
            'status' => 'in_stock',
            'shop_category_id' => $this->category->id,
            'is_popular' => 1,
            'discount_enabled' => 1,
            'original_price' => 25000,
            'approval_status' => 'approved',
            'publication_status' => 'published',
            'submit_for_review' => 0,
            'vendor_id' => $vendorB->id,
            'sku' => 'VENDOR-CHOSEN-SKU',
            'approve_now' => 1,
            'publish_now' => 1,
            'thumbnail' => $thumbnail,
            'images' => [$galleryImage],
            'gallery_order_present' => 1,
            'gallery_order' => ['upload:0'],
        ])->assertRedirect();

        $product = ShopProduct::query()->where('product_name', 'Vendor Saw')->firstOrFail();
        $this->assertSame('pending_approval', $product->approval_status);
        $this->assertSame('unpublished', $product->publication_status);
        $this->assertSame($vendorA->id, $product->vendor_id);
        $this->assertSame('TH-PROD-'.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT), $product->sku);
        $this->assertFalse($product->is_popular);
        $thumbnailPath = $product->thumbnail;
        $galleryPath = $product->images[0];
        $this->assertFileExists(public_path('assets/'.$thumbnailPath));
        $this->assertFileExists(public_path('assets/'.$galleryPath));
        $this->assertTrue($product->hasValidDiscount());
        $this->get('/shop')->assertDontSee('Vendor Saw');
        $this->actingAs($vendorUser)->get(route('vendor.products.edit', $product->slug))
            ->assertOk()
            ->assertSee('Product Information')
            ->assertSee('Additional Product Images (Optional)')
            ->assertSee('Save Changes')
            ->assertDontSee('name="sku"', false)
            ->assertDontSee('Approve Now')
            ->assertDontSee('Publish Now');

        $this->actingAs($vendorUser)->put(route('vendor.products.update', $product->slug), [
            'product_name' => $product->product_name,
            'price' => $product->price,
            'working_days' => $product->working_days,
            'status' => 'in_stock',
            'shop_category_id' => $this->category->id,
            'discount_enabled' => 1,
            'original_price' => $product->original_price,
            'remove_thumbnail' => 1,
            'gallery_order_present' => 1,
            'gallery_order' => ['existing:'.base64_encode($galleryPath)],
        ])->assertRedirect(route('vendor.products.index'));
        $this->assertNull($product->fresh()->thumbnail);
        $this->assertSame([$galleryPath], $product->fresh()->images);
        $this->assertFileExists(public_path('assets/'.$galleryPath));
        $this->assertFileDoesNotExist(public_path('assets/'.$thumbnailPath));

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

        \App\Support\PublicAsset::delete($galleryPath);
    }

    public function test_vendor_product_submission_supports_images_features_specifications_and_variations(): void
    {
        $vendor = $this->vendor('Full Product Vendor');
        $vendorUser = $this->user('vendor', $vendor);
        $admin = $this->user('administrator');
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII=');
        $thumbnail = UploadedFile::fake()->createWithContent('vendor-product.png', $imageContent);
        $galleryImage = UploadedFile::fake()->createWithContent('vendor-product-side.png', $imageContent.'side');

        $this->actingAs($vendorUser)->post(route('vendor.products.store'), [
            'product_name' => 'Vendor Product With Options',
            'description' => 'A vendor submitted product with selectable options.',
            'status' => 'active',
            'price_variations_enabled' => 1,
            'price_variations' => [
                [
                    'feature_label' => 'Package',
                    'variation_value' => 'Standard',
                    'price' => 20000,
                    'is_default' => 1,
                ],
                [
                    'feature_label' => 'Package',
                    'variation_value' => 'Premium',
                    'price' => 25000,
                    'is_default' => 0,
                ],
            ],
            'working_days' => 1,
            'shop_category_id' => $this->category->id,
            'tracks_inventory' => 0,
            'thumbnail' => $thumbnail,
            'images' => [$galleryImage],
            'gallery_order_present' => 1,
            'gallery_order' => ['upload:0'],
            'specifications' => [
                ['label' => 'Brand', 'value' => 'Vendor Brand'],
            ],
            'features' => ['Durable', 'Easy to install'],
            'publish_now' => 1,
        ])->assertRedirect(route('vendor.products.index'));

        $product = ShopProduct::query()->where('product_name', 'Vendor Product With Options')->firstOrFail();
        $this->assertSame($vendor->id, $product->vendor_id);
        $this->assertSame('pending_approval', $product->approval_status);
        $this->assertSame('unpublished', $product->publication_status);
        $this->assertSame(1, (int) $product->working_days);
        $this->assertSame('Vendor Brand', $product->specifications[0]['value']);
        $this->assertSame(['Durable', 'Easy to install'], $product->features);
        $this->assertSame(20000.0, (float) $product->price);
        $this->assertCount(2, $product->variations);
        $this->assertSame('Standard', $product->variations->firstWhere('is_default', true)->variation_value);
        $this->assertFileExists(public_path('assets/'.$product->thumbnail));
        $this->assertFileExists(public_path('assets/'.$product->images[0]));

        $this->actingAs($admin)->post(route('marketplace.products.approve', $product), [
            'note' => 'Product submission reviewed.',
        ])->assertRedirect();
        $this->assertSame('approved', $product->fresh()->approval_status);
        $this->assertSame('unpublished', $product->fresh()->publication_status);
        $this->assertCount(2, $product->fresh()->variations);

        foreach (array_filter([$product->thumbnail, ...($product->images ?? [])]) as $path) {
            \App\Support\PublicAsset::delete($path);
        }
    }

    public function test_vendor_price_variation_edits_stay_pending_until_product_approval(): void
    {
        $vendor = $this->vendor('Variation Supplier');
        $vendorUser = $this->user('vendor', $vendor);
        $admin = $this->user('administrator');
        $product = $this->product([
            'product_name' => 'Vendor option product',
            'price' => 10000,
            'vendor_id' => $vendor->id,
        ]);
        $standard = $product->variations()->create([
            'feature_label' => 'Package',
            'variation_value' => 'Standard',
            'price' => 10000,
            'is_default' => true,
        ]);
        $premium = $product->variations()->create([
            'feature_label' => 'Package',
            'variation_value' => 'Premium',
            'price' => 15000,
            'is_default' => false,
        ]);

        $this->actingAs($vendorUser)->put(route('vendor.products.update', $product->slug), [
            'product_name' => $product->product_name,
            'description' => $product->description,
            'working_days' => $product->working_days,
            'status' => 'in_stock',
            'shop_category_id' => $this->category->id,
            'price_variations_enabled' => 1,
            'price_variations' => [
                [
                    'id' => $standard->id,
                    'feature_label' => 'Package',
                    'variation_value' => 'Standard',
                    'price' => 10000,
                    'is_default' => false,
                ],
                [
                    'id' => $premium->id,
                    'feature_label' => 'Package',
                    'variation_value' => 'Premium',
                    'price' => 18000,
                    'is_default' => true,
                ],
            ],
        ])->assertRedirect(route('vendor.products.index'));

        $product->refresh();
        $this->assertSame('pending_approval', $product->revision_status);
        $this->assertSame(10000.0, (float) $product->price);
        $this->assertSame(18000.0, (float) $product->pending_payload['price']);
        $this->assertSame(15000.0, (float) $premium->fresh()->price);

        $this->actingAs($admin)->get(route('shop-products.show', $product))
            ->assertOk()
            ->assertSee('RWF 15,000');

        $this->actingAs($admin)->post(route('marketplace.products.approve', $product), [
            'note' => 'Approved price changes.',
        ])->assertRedirect();

        $this->assertNull($product->fresh()->revision_status);
        $this->assertSame(18000.0, (float) $product->fresh()->price);
        $this->assertSame(18000.0, (float) $premium->fresh()->price);
        $this->assertTrue($premium->fresh()->is_default);
    }

    public function test_vendor_inventory_requests_are_pending_until_an_admin_approves_once(): void
    {
        $vendor = $this->vendor('Inventory Vendor');
        $vendorUser = $this->user('vendor', $vendor);
        $admin = $this->user('administrator');
        $otherVendorUser = $this->user('vendor', $this->vendor('Other Inventory Vendor'));
        $product = $this->product([
            'product_name' => 'Vendor Inventory Item',
            'slug' => 'vendor-inventory-item',
            'vendor_id' => $vendor->id,
            'tracks_inventory' => true,
            'stock_quantity' => 12,
            'reserved_quantity' => 2,
            'low_stock_threshold' => 4,
            'stock_unit' => 'kg',
        ]);

        $this->actingAs($admin)->get(route('marketplace.inventory.index'))
            ->assertOk()
            ->assertSee('Inventory Management')
            ->assertSee('Vendor Inventory Item')
            ->assertSee('12 kg')
            ->assertSee('Inventory Approval Requests');
        $this->actingAs($vendorUser)->get(route('marketplace.inventory.index'))->assertForbidden();

        $this->actingAs($vendorUser)->post(route('vendor.inventory.adjust', $product->slug), [
            'change_type' => 'addition',
            'quantity' => 5,
            'reason' => 'Received supplier delivery',
        ])->assertRedirect();

        $inventoryRequest = InventoryAdjustmentRequest::query()->firstOrFail();
        $this->assertSame('pending', $inventoryRequest->status);
        $this->assertSame(12, $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->count());

        $this->actingAs($otherVendorUser)->post(route('vendor.inventory.adjust', $product->slug), [
            'change_type' => 'addition',
            'quantity' => 5,
            'reason' => 'Attempt to update other vendor stock',
        ])->assertNotFound();
        $this->actingAs($this->user('product-manager'))
            ->post(route('marketplace.inventory-requests.approve', $inventoryRequest))
            ->assertForbidden();

        $this->actingAs($admin)->post(route('marketplace.inventory-requests.approve', $inventoryRequest))->assertRedirect();
        $this->assertSame(17, $product->fresh()->stock_quantity);
        $this->assertSame('approved', $inventoryRequest->fresh()->status);
        $this->assertSame(5, $inventoryRequest->fresh()->quantity_delta);
        $this->assertDatabaseHas('inventory_movements', [
            'shop_product_id' => $product->id,
            'type' => 'adjustment',
            'quantity_delta' => 5,
            'stock_before' => 12,
            'stock_after' => 17,
            'reference_type' => InventoryAdjustmentRequest::class,
            'reference_id' => $inventoryRequest->id,
        ]);
        $this->assertSame(1, InventoryMovement::query()->where('reference_type', InventoryAdjustmentRequest::class)->where('reference_id', $inventoryRequest->id)->count());

        $this->from(route('marketplace.inventory.index'))
            ->actingAs($admin)
            ->post(route('marketplace.inventory-requests.approve', $inventoryRequest))
            ->assertRedirect(route('marketplace.inventory.index'))
            ->assertSessionHasErrors('request');
        $this->assertSame(17, $product->fresh()->stock_quantity);
    }

    public function test_rejected_vendor_inventory_requests_leave_stock_unchanged_and_reductions_respect_reservations(): void
    {
        $vendor = $this->vendor('Inventory Review Vendor');
        $vendorUser = $this->user('vendor', $vendor);
        $admin = $this->user('administrator');
        $product = $this->product([
            'product_name' => 'Reserved Stock Item',
            'slug' => 'reserved-stock-item',
            'vendor_id' => $vendor->id,
            'tracks_inventory' => true,
            'stock_quantity' => 10,
            'reserved_quantity' => 3,
            'stock_unit' => 'box',
        ]);

        $this->actingAs($vendorUser)->from(route('vendor.inventory'))->post(route('vendor.inventory.adjust', $product->slug), [
            'change_type' => 'reduction',
            'quantity' => 8,
            'reason' => 'Attempt to reduce more than unreserved quantity',
        ])->assertRedirect(route('vendor.inventory'))->assertSessionHasErrors('quantity');
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryAdjustmentRequest::query()->count());

        $this->actingAs($vendorUser)->post(route('vendor.inventory.adjust', $product->slug), [
            'change_type' => 'addition',
            'quantity' => 4,
            'reason' => 'Incorrect count was recorded',
        ])->assertRedirect();
        $inventoryRequest = InventoryAdjustmentRequest::query()->firstOrFail();

        $this->actingAs($admin)->from(route('marketplace.inventory.index'))
            ->post(route('marketplace.inventory-requests.reject', $inventoryRequest), ['review_note' => 'Please provide a verified delivery receipt.'])
            ->assertRedirect(route('marketplace.inventory.index'));

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame('rejected', $inventoryRequest->fresh()->status);
        $this->assertSame('Please provide a verified delivery receipt.', $inventoryRequest->fresh()->review_note);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_inventory_dashboard_filters_by_stock_status_category_vendor_and_reports_units_separately(): void
    {
        $admin = $this->user('administrator');
        $vendor = $this->vendor('Dashboard Filter Vendor');
        $lowStock = $this->product([
            'product_name' => 'Low inventory filter item',
            'slug' => 'low-inventory-filter-item',
            'vendor_id' => $vendor->id,
            'tracks_inventory' => true,
            'stock_quantity' => 4,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
            'stock_unit' => 'kg',
        ]);
        $normalStock = $this->product([
            'product_name' => 'Regular inventory filter item',
            'slug' => 'regular-inventory-filter-item',
            'tracks_inventory' => true,
            'stock_quantity' => 8,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 2,
            'stock_unit' => 'box',
        ]);

        $response = $this->actingAs($admin)->get(route('marketplace.inventory.index', [
            'stock_status' => 'low_stock',
            'shop_category_id' => $this->category->id,
            'vendor_id' => $vendor->id,
        ]))
            ->assertOk()
            ->assertSee('Low inventory filter item')
            ->assertSee('Low stock')
            ->assertSee('Available stock by unit')
            ->assertSee('kg: 4')
            ->assertDontSee('box: 8');

        preg_match('/<tbody>(.*?)<\/tbody>/s', $response->getContent(), $tableRows);
        $this->assertStringContainsString('Low inventory filter item', $tableRows[1] ?? '');
        $this->assertStringNotContainsString('Regular inventory filter item', $tableRows[1] ?? '');
    }

    public function test_category_product_endpoint_includes_existing_untracked_products_and_reports_empty_categories(): void
    {
        $admin = $this->user('administrator');
        $product = $this->product([
            'product_name' => 'Existing Untracked Category Product',
            'slug' => 'existing-untracked-category-product',
            'shop_category_id' => $this->category->id,
            'tracks_inventory' => false,
            'stock_quantity' => 0,
            'stock_unit' => 'box',
        ]);
        $emptyCategory = Shopcategory::query()->create([
            'name' => 'Empty inventory category',
            'slug' => 'empty-inventory-category',
            'active' => true,
        ]);
        $otherCategory = Shopcategory::query()->create([
            'name' => 'Separate inventory category',
            'slug' => 'separate-inventory-category',
            'active' => true,
        ]);
        $otherProduct = $this->product([
            'product_name' => 'Product from separate category',
            'slug' => 'product-from-separate-category',
            'shop_category_id' => $otherCategory->id,
            'tracks_inventory' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('marketplace.inventory.categories.products', $this->category->id))
            ->assertOk()
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.name', 'Existing Untracked Category Product')
            ->assertJsonPath('products.0.current_stock', 0)
            ->assertJsonPath('products.0.tracks_inventory', false);

        $this->actingAs($admin)
            ->getJson(route('marketplace.inventory.categories.products', $otherCategory->id))
            ->assertOk()
            ->assertJsonPath('products.0.id', $otherProduct->id)
            ->assertJsonMissing(['name' => 'Existing Untracked Category Product']);

        $this->actingAs($admin)
            ->getJson(route('marketplace.inventory.categories.products', $emptyCategory->id))
            ->assertOk()
            ->assertExactJson(['products' => []]);

        $this->actingAs($this->user('vendor', $this->vendor('Inventory API Unauthorized Vendor')))
            ->getJson(route('marketplace.inventory.categories.products', $this->category->id))
            ->assertForbidden();
    }

    public function test_admin_can_add_inventory_once_and_the_saved_row_shows_current_stock(): void
    {
        $admin = $this->user('administrator');
        $product = $this->product([
            'product_name' => 'Inventory Add Form Product',
            'slug' => 'inventory-add-form-product',
            'tracks_inventory' => false,
            'stock_quantity' => 10,
            'reserved_quantity' => 2,
            'stock_unit' => 'box',
        ]);
        $location = ProductLocation::query()->create([
            'shop_product_id' => $product->id,
            'warehouse_name' => 'Primary stock room',
            'quantity' => 10,
            'reserved_quantity' => 2,
            'is_primary' => true,
        ]);
        $submissionToken = (string) \Illuminate\Support\Str::uuid();
        $payload = [
            'inventory_form' => 'add',
            'shop_category_id' => $this->category->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'reason' => 'Supplier delivery',
            'notes' => 'Delivery reference INV-25',
            'submission_token' => $submissionToken,
        ];

        $this->actingAs($admin)->get(route('marketplace.inventory.index'))
            ->assertOk()
            ->assertSee('Add Inventory')
            ->assertSee('Quantity to add')
            ->assertSee('Current approved stock');

        $this->actingAs($admin)->post(route('marketplace.inventory.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'Inventory saved and stock movement recorded.');

        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertTrue($product->fresh()->tracks_inventory);
        $this->assertSame(15, $location->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'shop_product_id' => $product->id,
            'type' => 'addition',
            'quantity_delta' => 5,
            'stock_before' => 10,
            'stock_after' => 15,
            'note' => "Supplier delivery\nDelivery reference INV-25",
        ]);
        $this->actingAs($admin)->get(route('marketplace.inventory.index'))
            ->assertOk()
            ->assertSee('Inventory Add Form Product')
            ->assertSee('15 box')
            ->assertSee('id="editInventoryModal"', false);

        $this->actingAs($admin)->post(route('marketplace.inventory.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'This inventory submission was already saved; stock was not changed again.');

        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('shop_product_id', $product->id)->count());
        $this->assertDatabaseHas('inventory_adjustment_submissions', [
            'token' => $submissionToken,
            'shop_product_id' => $product->id,
        ]);
    }

    public function test_admin_can_remove_available_stock_and_over_removal_is_rejected_without_a_movement(): void
    {
        $admin = $this->user('administrator');
        $product = $this->product([
            'product_name' => 'Inventory Removal Product',
            'slug' => 'inventory-removal-product',
            'tracks_inventory' => true,
            'stock_quantity' => 100,
            'reserved_quantity' => 20,
            'stock_unit' => 'units',
        ]);
        $payload = [
            'inventory_form' => 'remove',
            'shop_category_id' => $this->category->id,
            'product_id' => $product->id,
            'quantity' => 15,
            'reason' => 'Damaged goods',
            'notes' => 'Package damaged on arrival',
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
        ];

        $this->actingAs($admin)->get(route('marketplace.inventory.index'))
            ->assertOk()
            ->assertSee('Remove Inventory')
            ->assertSee('Quantity to remove')
            ->assertSee('Reason for removal');

        $this->actingAs($admin)->post(route('marketplace.inventory.remove'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'Stock removed and inventory movement recorded.');

        $this->assertSame(85, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'shop_product_id' => $product->id,
            'type' => 'removal',
            'quantity_delta' => -15,
            'stock_before' => 100,
            'stock_after' => 85,
            'note' => "Damaged goods\nPackage damaged on arrival",
        ]);

        $this->actingAs($admin)->from(route('marketplace.inventory.index'))
            ->post(route('marketplace.inventory.remove'), array_merge($payload, [
                'quantity' => 66,
                'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            ]))
            ->assertRedirect(route('marketplace.inventory.index'))
            ->assertSessionHasErrors('quantity');
        $this->assertSame(85, $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryMovement::query()->where('shop_product_id', $product->id)->count());
        $this->actingAs($admin)->get(route('marketplace.inventory.index', ['movement_type' => 'removal']))
            ->assertOk()
            ->assertSee('Inventory Removal Product');

        $this->actingAs($admin)->post(route('marketplace.inventory.remove'), $payload)
            ->assertSessionHas('success', 'This inventory submission was already processed; stock was not changed again.');
        $this->assertSame(85, $product->fresh()->stock_quantity);

        $vendorUser = $this->user('vendor', $this->vendor('Inventory Removal Unauthorized Vendor'));
        $this->actingAs($vendorUser)->post(route('marketplace.inventory.remove'), array_merge($payload, [
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
        ]))->assertForbidden();
    }

    public function test_inventory_details_can_be_edited_without_overwriting_stock_and_vendor_is_forbidden(): void
    {
        $admin = $this->user('administrator');
        $vendorUser = $this->user('vendor', $this->vendor('Inventory Detail Vendor'));
        $newCategory = Shopcategory::query()->create([
            'name' => 'Inventory Category Update',
            'slug' => 'inventory-category-update',
            'active' => true,
        ]);
        $product = $this->product([
            'product_name' => 'Inventory Details Product',
            'slug' => 'inventory-details-product',
            'tracks_inventory' => true,
            'stock_quantity' => 22,
            'inventory_notes' => 'Previous note',
        ]);

        $this->actingAs($vendorUser)->patch(route('marketplace.inventory.update', $product), [
            'edit_product_id' => $product->id,
            'shop_category_id' => $newCategory->id,
            'inventory_notes' => 'Unauthorized change',
        ])->assertForbidden();

        $this->actingAs($admin)->patch(route('marketplace.inventory.update', $product), [
            'edit_product_id' => $product->id,
            'shop_category_id' => $newCategory->id,
            'inventory_notes' => 'Counted and verified on site.',
        ])->assertRedirect()
            ->assertSessionHas('success', 'Inventory details saved. Stock was not changed.');

        $this->assertSame($newCategory->id, $product->fresh()->shop_category_id);
        $this->assertSame('Counted and verified on site.', $product->fresh()->inventory_notes);
        $this->assertSame(22, $product->fresh()->stock_quantity);
        $this->assertSame(0, InventoryMovement::query()->where('shop_product_id', $product->id)->count());
    }

    public function test_admin_sidebar_uses_direct_management_links_and_pages_keep_creation_actions(): void
    {
        $admin = $this->user('administrator');

        $sidebar = $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Main')
            ->assertSee('Marketplace')
            ->assertSee('System')
            ->assertSee('Shop Products')
            ->assertSee('Shop Categories')
            ->assertSee('Inventory')
            ->assertDontSee('> Catalog</a>', false)
            ->assertSee('Approvals')
            ->assertSee(route('marketplace.approvals.index'), false)
            ->assertSee(route('admin.shop-products.index'), false)
            ->assertSee(route('shop-categories.index'), false)
            ->assertSee(route('services.index'), false)
            ->assertSee(route('backgrounds.index'), false)
            ->assertSee(route('categories.index'), false)
            ->assertSee(route('partners.index'), false)
            ->assertSee(route('works.index'), false)
            ->assertSee(route('cta-categories.index'), false)
            ->assertSee(route('ctas.index'), false)
            ->assertDontSee('nav-submenu')
            ->assertDontSee('nav-sub-link')
            ->assertDontSee('toggleDropdown')
            ->assertDontSee('dropdown-parent')
            ->assertDontSee('nav-arrow');

        $this->assertStringContainsString(
            'href="'.route('admin.shop-products.index').'" class="nav-item-link active"',
            $this->get(route('admin.shop-products.index'))->assertOk()->getContent()
        );
        $this->assertStringContainsString(
            'href="'.route('marketplace.approvals.index').'" class="nav-item-link active"',
            $this->get(route('marketplace.approvals.index'))->assertOk()->getContent()
        );
        $this->get(route('admin.shop-products.index'))
            ->assertSee('New Product')
            ->assertSee(route('admin.shop-products.create'), false);

        $this->assertStringContainsString(
            'href="'.route('shop-categories.index').'" class="nav-item-link active"',
            $this->get(route('shop-categories.index'))->assertOk()->getContent()
        );
        $this->get(route('shop-categories.index'))
            ->assertSee('New Category')
            ->assertSee(route('shop-categories.create'), false);

        $this->get(route('services.index'))->assertOk()->assertSee('New Service');
        $this->get(route('backgrounds.index'))->assertOk()->assertSee('Add Background');
        $this->get(route('categories.index'))->assertOk()->assertSee('New Category');
        $this->get(route('partners.index'))->assertOk()->assertSee('Add Partner');
        $this->get(route('works.index'))->assertOk()->assertSee('Add Work');
        $this->get(route('cta-categories.index'))->assertOk()->assertSee('New Category');
        $this->get(route('ctas.index'))->assertOk()->assertSee('New CTA');
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
            'request_type' => 'quote',
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

        $orderItem = $order->items->firstOrFail();
        $this->assertNull($orderItem->vendor_id);
        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => (string) $vendor->id,
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.index', ['tab' => 'pending']));

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
        $customerAccount = $this->customerAccountFor($customer);
        $strangerAccount = $this->customerAccountFor($stranger);
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
        ], [['product' => $product, 'quantity' => 1]], $customer, $customerAccount);

        $this->acceptOrderThroughForm($admin, $order);

        $this->assertTrue(OrderStatusHistory::query()->where('order_id', $order->id)->where('to_status', 'confirmed')->exists());
        $this->assertStringNotContainsString('Private Depot', (string) $customerAccount->notifications()->latest()->first()?->data['body']);

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

        $this->actingAs($strangerAccount, 'customers')->get(route('account.orders.show', $order->reference))->assertNotFound();
        $this->actingAs($customerAccount, 'customers')->get(route('account.orders.show', $order->reference))->assertOk()->assertDontSee('Private Depot Gikondo');
    }

    public function test_admin_order_review_accepts_only_after_inspection_and_blocks_bypass_actions(): void
    {
        $admin = $this->user('administrator');
        $customer = $this->user('customer');
        $vendor = $this->vendor('Review Vendor');
        $product = $this->product(['product_name' => 'Review Product']);
        $order = app(OrderService::class)->place([
            'name' => 'Aline',
            'email' => 'aline@example.com',
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'payment_method' => 'cash_on_delivery',
        ], [['product' => $product, 'quantity' => 1]], $customer);
        $item = $order->items->firstOrFail();

        $this->actingAs($admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Review Order')
            ->assertSee('Who will handle this order?')
            ->assertDontSee('Accept Order')
            ->assertDontSee('Reject Order')
            ->assertDontSee('name="allocations[', false)
            ->assertDontSee('Payment information')
            ->assertDontSee('Payment status')
            ->assertDontSee('name="fulfillment_status"', false)
            ->assertDontSee('name="payment_status"', false);

        $this->actingAs($admin)->post(route('marketplace.orders.status', $order), [
            'fulfillment_status' => 'confirmed',
        ])->assertSessionHasErrors('decision');
        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('order');
        $this->assertNull($item->fresh()->vendor_id);

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'has_paid' => 'no',
            'accept_order' => 'yes',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('accept_order');
        $this->assertSame('pending_confirmation', $order->fresh()->fulfillment_status);
        $this->assertSame(0, $order->payments()->count());

        $this->actingAs($admin)->get(route('marketplace.orders.show', ['order' => $order, 'allocate' => 1]))
            ->assertOk()
            ->assertSee('Who will handle this order?')
            ->assertDontSee('Confirm Accept Order')
            ->assertDontSee('Has the customer paid?');
        $this->assertSame('pending_confirmation', $order->fresh()->fulfillment_status);
        $this->assertNull($item->fresh()->vendor_id);
        $this->assertFalse(OrderStatusHistory::query()->where('order_id', $order->id)->where('to_status', 'confirmed')->exists());

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => 'admin',
            'has_paid' => 'yes',
            'payment_type' => 'partial',
            'payment_method' => 'bank_transfer',
            'partial_amount' => '999999.00',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('has_paid');
        $this->assertSame('pending_confirmation', $order->fresh()->fulfillment_status);
        $this->assertSame('unassigned', $item->fresh()->assignment_status);
        $this->assertSame(0, $order->payments()->count());

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => 'admin',
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order));

        $this->assertSame('pending_confirmation', $order->fresh()->fulfillment_status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('admin_handled', $item->fresh()->assignment_status);
        $this->actingAs($admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Accept Order')
            ->assertSee('Cancel order')
            ->assertSee('Payment information');

        $paymentSubmissionKey = (string) \Illuminate\Support\Str::uuid();
        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'accept_order' => 'yes',
            'has_paid' => 'yes',
            'payment_type' => 'partial',
            'payment_method' => 'bank_transfer',
            'partial_amount' => '100.00',
            'submission_key' => $paymentSubmissionKey,
        ])
            ->assertRedirect(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertSessionHas('success', 'Order processed successfully and moved to Paid Orders.');
        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->assertSame('partially_paid', $order->fresh()->payment_status);
        $this->assertSame(100.0, (float) $order->payments()->sum('amount'));

        $history = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('to_status', 'confirmed')
            ->firstOrFail();
        $this->assertSame($admin->id, $history->user_id);
        $this->assertNull($history->note);

        $this->actingAs($admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Order Details')
            ->assertSee('Handled by Admin')
            ->assertSee('Edit')
            ->assertDontSee('Save Changes');
        $this->actingAs($admin)->get(route('marketplace.orders.show', ['order' => $order, 'edit' => 1]))
            ->assertOk()
            ->assertSee('Has the customer paid?');

        $this->actingAs($admin)->post(route('marketplace.orders.review', $order), [
            'decision' => 'accept',
        ])->assertSessionHasErrors('decision');
        $this->assertSame(1, OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('to_status', 'confirmed')
            ->count());
    }

    public function test_admin_rejection_requires_reason_records_actor_and_does_not_notify_customer_again(): void
    {
        $admin = $this->user('administrator');
        $customer = $this->user('customer');
        $product = $this->product(['product_name' => 'Unavailable Product']);
        $order = app(OrderService::class)->place([
            'name' => 'Aline',
            'email' => 'aline@example.com',
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'payment_method' => 'cash_on_delivery',
        ], [['product' => $product, 'quantity' => 1]], $customer);
        $notificationCount = $customer->notifications()->count();

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => 'admin',
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order));

        $this->actingAs($admin)->post(route('marketplace.orders.review', $order), [
            'decision' => 'reject',
        ])->assertSessionHasErrors('reason');
        $this->assertSame('pending_confirmation', $order->fresh()->fulfillment_status);

        $this->actingAs($admin)->postJson(route('marketplace.orders.review', $order), [
            'decision' => 'reject',
            'reason' => 'The requested item is no longer available.',
        ])->assertOk()
            ->assertJsonPath('message', 'Order rejected and the reason recorded.')
            ->assertJsonPath('redirect_url', route('marketplace.orders.index', ['tab' => 'pending']));

        $order->refresh();
        $this->assertSame('cancelled', $order->fulfillment_status);
        $this->assertSame('Admin rejection: The requested item is no longer available.', $order->cancellation_reason);
        $history = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('to_status', 'cancelled')
            ->firstOrFail();
        $this->assertSame($admin->id, $history->user_id);
        $this->assertSame('Admin rejection: The requested item is no longer available.', $history->note);
        $this->assertSame('Your order was rejected.', $history->customer_message);
        $this->actingAs($admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Rejected')
            ->assertSee('Rejection reason:')
            ->assertSee('The requested item is no longer available.')
            ->assertSee('Order history');
        $this->assertSame($notificationCount, $customer->notifications()->count());
    }

    public function test_vendor_allocation_transfers_entire_order_out_of_admin_review(): void
    {
        $admin = $this->user('administrator');
        $customer = $this->user('customer');
        $vendor = $this->vendor('Payment Workflow Vendor');
        $vendorUser = $this->user('vendor', $vendor);
        $product = $this->product(['product_name' => 'Vendor-handled Product', 'price' => 12000]);
        $secondProduct = $this->product(['product_name' => 'Second Vendor Product', 'price' => 8000]);
        $order = app(OrderService::class)->place([
            'name' => 'Aline',
            'email' => 'aline@example.com',
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'payment_method' => 'cash_on_delivery',
        ], [
            ['product' => $product, 'quantity' => 1],
            ['product' => $secondProduct, 'quantity' => 1],
        ], $customer);
        $items = $order->items;

        $this->actingAs($admin)->get(route('marketplace.orders.show', ['order' => $order, 'allocate' => 1]))
            ->assertOk()
            ->assertDontSee('Payment information')
            ->assertSee('Who will handle this order?')
            ->assertSee('Vendor-handled Product')
            ->assertSee('Second Vendor Product')
            ->assertDontSee('name="allocations[', false);

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => (string) $vendor->id,
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertSessionHas('success', 'Order successfully allocated to '.$vendor->business_name.'.');

        $this->assertSame(1, \App\Models\Order::query()->whereKey($order->id)->count());
        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->assertSame([$vendor->id], $items->map(fn ($item) => $item->fresh()->vendor_id)->unique()->values()->all());
        $this->assertSame(['assigned'], $items->map(fn ($item) => $item->fresh()->assignment_status)->unique()->values()->all());

        $this->actingAs($admin)->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertDontSee($order->reference);
        $this->actingAs($admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Accept Order')
            ->assertDontSee('Reject Order')
            ->assertDontSee('Payment information');

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'has_paid' => 'yes',
            'payment_type' => 'full',
            'payment_method' => 'bank_transfer',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertSessionHasErrors('has_paid');

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(0, $order->payments()->count());

        $this->actingAs($vendorUser)->get(route('vendor.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee($order->reference);
        $this->actingAs($vendorUser)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Vendor-handled Product')
            ->assertSee('Second Vendor Product');
    }

    public function test_accepted_order_changes_save_allocation_and_payment_history_together(): void
    {
        $admin = $this->user('administrator');
        $customer = $this->user('customer');
        $vendor = $this->vendor('Processing Vendor');
        $product = $this->product(['product_name' => 'Orderable Product', 'price' => 15000]);
        $order = app(OrderService::class)->place([
            'name' => 'Aline',
            'email' => 'aline@example.com',
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'payment_method' => 'cash_on_delivery',
        ], [['product' => $product, 'quantity' => 1]], $customer);
        $item = $order->items->firstOrFail();

        $this->acceptOrderThroughForm($admin, $order, [$item->id => 'admin']);

        $this->actingAs($admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Edit')
            ->assertDontSee('Save Changes')
            ->assertDontSee('Next steps');
        $this->actingAs($admin)->get(route('marketplace.orders.show', ['order' => $order, 'edit' => 1]))
            ->assertOk()
            ->assertSee('Save Changes')
            ->assertSee('Has the customer paid?')
            ->assertSee('Yes — Partially Paid')
            ->assertSee('Handled by Admin');

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocations' => [$item->id => 'admin'],
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order));
        $this->assertNull($item->fresh()->vendor_id);
        $this->assertSame('admin_handled', $item->fresh()->assignment_status);
        $this->actingAs($admin)->get(route('marketplace.orders.show', ['order' => $order, 'edit' => 1]))
            ->assertOk()
            ->assertSee('Handled by Admin');
        $this->actingAs($admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee($order->reference)
            ->assertDontSee('Handled by Admin');

        $submissionKey = (string) \Illuminate\Support\Str::uuid();
        $changes = [
            'allocations' => [$item->id => 'admin'],
            'has_paid' => 'yes',
            'payment_type' => 'partial',
            'payment_method' => 'bank_transfer',
            'partial_amount' => '4000.00',
            'submission_key' => $submissionKey,
        ];
        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            ...$changes,
            'partial_amount' => '20000.00',
        ])->assertSessionHasErrors('partial_amount');
        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            ...$changes,
            'partial_amount' => '15000.00',
        ])->assertSessionHasErrors('partial_amount');
        $this->assertNull($item->fresh()->vendor_id);
        $this->assertSame(0, $order->payments()->count());

        $this->actingAs($admin)->postJson(route('marketplace.orders.save-changes', $order), $changes)
            ->assertOk()
            ->assertJsonPath('message', 'Order changes saved.')
            ->assertJsonPath('redirect_url', route('marketplace.orders.show', $order));
        $this->assertNull($item->fresh()->vendor_id);
        $this->assertSame('admin_handled', $item->fresh()->assignment_status);
        $this->assertSame('partially_paid', $order->fresh()->payment_status);
        $this->assertSame(4000.0, (float) $order->payments()->sum('amount'));
        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->actingAs($admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee($order->reference)
            ->assertSee('Paid Orders');

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), $changes)
            ->assertRedirect(route('marketplace.orders.show', $order));
        $this->assertSame(1, $order->payments()->count());

        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocations' => [$item->id => 'admin'],
            'has_paid' => 'yes',
            'payment_type' => 'full',
            'payment_method' => 'cash_on_delivery',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame((float) $order->total, (float) $order->payments()->sum('amount'));
        $this->assertSame(2, $order->payments()->count());
        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->assertSame(0, $order->deliveries()->count());

        $this->actingAs($admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Payment history')
            ->assertSee('4,000.00 RWF')
            ->assertSee('11,000.00 RWF');
        $this->actingAs($admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee($order->reference);
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
        $this->actingAs($customer)->get('/dashboard')->assertRedirect(route('home'));

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

    private function acceptOrderThroughForm(User $admin, \App\Models\Order $order, ?array $allocations = null): void
    {
        $items = $order->items()->get();
        $allocations ??= $items->mapWithKeys(fn ($item) => [$item->id => 'admin'])->all();
        $selectedAllocation = (string) reset($allocations);
        $this->assertSame(
            1,
            count(array_unique(array_map('strval', array_values($allocations)))),
            'The Admin review workflow allocates the entire order to one party.'
        );

        $this->actingAs($admin)->get(route('marketplace.orders.show', ['order' => $order, 'allocate' => 1]))
            ->assertOk()
            ->assertSee('Who will handle this order?')
            ->assertDontSee('Payment information');
        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => $selectedAllocation,
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order));
        $this->actingAs($admin)->post(route('marketplace.orders.save-changes', $order), [
            'accept_order' => 'yes',
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order));
    }

    private function user(string $role, ?Vendor $vendor = null): User
    {
        $user = User::factory()->create([
            'vendor_id' => $vendor?->id,
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function customerAccountFor(User $user): Customer
    {
        return Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => 'customer-password',
        ]);
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
