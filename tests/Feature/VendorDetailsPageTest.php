<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    private Shopcategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);
        $this->category = Shopcategory::query()->create([
            'name' => 'Vendor Detail Category',
            'slug' => 'vendor-detail-category',
            'active' => true,
        ]);
    }

    public function test_vendor_details_show_scoped_metrics_and_clickable_filtered_destinations(): void
    {
        $vendor = $this->vendor('Details Vendor');
        $otherVendor = $this->vendor('Other Details Vendor');
        $product = $this->product($vendor, 'Vendor Detail Product', 10, 2, true);
        $this->product($vendor, 'Vendor Digital Product', 0, 0, false);
        $otherProduct = $this->product($otherVendor, 'Other Vendor Product', 40, 0, true);
        $order = $this->order('VENDOR-DETAIL-ORDER', 'paid');
        $this->orderItem($order, $vendor, $product, 4000);
        $this->orderItem($order, $otherVendor, $otherProduct, 6000);
        $otherOrder = $this->order('OTHER-VENDOR-ORDER', 'paid');
        $this->orderItem($otherOrder, $otherVendor, $this->product($otherVendor, 'Other Order Product', 5, 0, true), 9000);

        $response = $this->actingAs($this->administrator())->get(route('marketplace.vendors.show', $vendor));
        $response->assertOk()
            ->assertSee('Details Vendor')
            ->assertSee('Products')
            ->assertSee('Orders')
            ->assertSee('Sales Total')
            ->assertSee('4,000 RWF')
            ->assertSee('Available Stock')
            ->assertSee('8')
            ->assertSee(route('admin.shop-products.index', ['vendor_id' => $vendor->id]), false)
            ->assertSee(route('marketplace.orders.index', ['vendor' => $vendor->id]), false)
            ->assertSee(str_replace('&', '&amp;', route('marketplace.orders.index', ['vendor' => $vendor->id, 'payment' => 'paid'])), false)
            ->assertSee(route('marketplace.inventory.index', ['vendor_id' => $vendor->id]), false)
            ->assertDontSee('Add a vendor user')
            ->assertDontSee('Team Members');

        $this->actingAs($this->administrator())
            ->get(route('admin.shop-products.index', ['vendor_id' => $vendor->id]))
            ->assertOk()
            ->assertSee('Vendor Detail Product')
            ->assertDontSee('Other Vendor Product');

        $this->actingAs($this->administrator())
            ->get(route('marketplace.orders.index', ['tab' => 'completed', 'vendor' => $vendor->id]))
            ->assertRedirect(route('marketplace.reports.index', ['category' => 'orders']));
        $this->assertSame('completed', $order->fresh()->fulfillment_status);

        $this->actingAs($this->administrator())
            ->get(route('marketplace.inventory.index', ['vendor_id' => $vendor->id]))
            ->assertOk()
            ->assertSee('Vendor Detail Product')
            ->assertDontSee('Other Vendor Product');
    }

    public function test_vendor_details_empty_state_and_missing_logo_fallback_render_safely(): void
    {
        $vendor = $this->vendor('No Catalog Vendor');
        $vendor->update(['business_logo_path' => 'vendors/logos/not-present.png']);

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('>N</span>', false)
            ->assertDontSee('not-present.png')
            ->assertSee('>0</span>', false)
            ->assertSee('No products are assigned to this vendor.')
            ->assertSee('No orders are associated with this vendor.');

        $vendor->update(['business_name' => '']);
        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('>A</span>', false);
    }

    public function test_vendor_header_badges_reflect_the_selected_vendors_saved_status_fields(): void
    {
        $activeVendor = $this->vendor('Published Partner');
        $activeVendor->update([
            'show_as_partner' => true,
            'partner_display_status' => 'published',
            'public_branding_approved' => true,
        ]);
        $suspendedVendor = $this->vendor('Suspended Partner');
        $suspendedVendor->update([
            'status' => 'suspended',
            'show_as_partner' => true,
            'partner_display_status' => 'published',
        ]);
        $inactiveVendor = $this->vendor('Inactive Vendor');
        $inactiveVendor->update(['status' => 'inactive']);
        $draftVendor = $this->vendor('Draft Vendor');
        $draftVendor->update([
            'show_as_partner' => true,
            'partner_display_status' => 'draft',
        ]);
        $legacyPartner = $this->vendor('Legacy Partner');
        $legacyPartner->update([
            'show_as_partner' => true,
            'partner_display_status' => 'legacy_publication',
            'public_branding_approved' => true,
        ]);
        $nonPartner = $this->vendor('Non Partner');

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $activeVendor))
            ->assertOk()
            ->assertSee('Vendor: Active')
            ->assertSee('Partner: Published')
            ->assertSee('Branding: Approved');

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $suspendedVendor))
            ->assertOk()
            ->assertSee('Vendor: Suspended')
            ->assertSee('Partner: Blocked by vendor status: Suspended')
            ->assertSee('Branding: Not approved')
            ->assertDontSee('Vendor: Active');

        $this->actingAs($this->administrator())
            ->patch(route('marketplace.vendors.status', $suspendedVendor), ['status' => 'pending'])
            ->assertRedirect();

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $suspendedVendor))
            ->assertOk()
            ->assertSee('Vendor: Pending Approval')
            ->assertSee('Partner: Blocked by vendor status: Pending Approval');

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $inactiveVendor))
            ->assertOk()
            ->assertSee('Vendor: Inactive')
            ->assertSee('Partner: Not a Partner');

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $draftVendor))
            ->assertOk()
            ->assertSee('Partner: Draft')
            ->assertSee('Branding: Not approved');

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $legacyPartner))
            ->assertOk()
            ->assertSee('Partner: Unknown (legacy_publication)')
            ->assertSee('Branding: Approved');

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $nonPartner))
            ->assertOk()
            ->assertSee('Partner: Not a Partner')
            ->assertSee('Branding: Not approved');
    }

    public function test_vendor_header_does_not_present_legacy_statuses_as_active_or_approved(): void
    {
        $vendor = $this->vendor('Legacy Status Vendor');
        $vendor->update([
            'status' => 'legacy_review',
            'show_as_partner' => true,
            'partner_display_status' => 'legacy_publication',
            'public_branding_approved' => false,
        ]);

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Vendor: Unknown (legacy_review)')
            ->assertSee('Partner: Blocked by vendor status: Unknown (legacy_review)')
            ->assertSee('Branding: Not approved')
            ->assertDontSee('Vendor: Active')
            ->assertDontSee('Branding: Pending approval');
    }

    public function test_vendor_edit_cannot_bypass_the_publication_action(): void
    {
        $vendor = $this->vendor('Partner Draft');

        $this->actingAs($this->administrator())
            ->put(route('marketplace.vendors.update', $vendor), [
                'business_name' => $vendor->business_name,
                'legal_name' => '',
                'reference_number' => '',
                'email' => $vendor->email,
                'phone' => '',
                'status' => 'active',
                'district' => '',
                'operating_location' => '',
                'website_url' => '',
                'address_notes' => '',
                'internal_notes' => '',
                'show_as_partner' => '1',
                'partner_description' => '',
                'display_order' => '0',
                'partner_display_status' => 'published',
                'public_branding_approved' => '1',
            ])->assertRedirect(route('marketplace.vendors.show', $vendor));

        $this->assertSame('draft', $vendor->fresh()->partner_display_status);

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Partner: Draft')
            ->assertSee('Branding: Approved')
            ->assertSee('Website: Not published')
            ->assertSee('Publish on Website');
    }

    public function test_termination_requires_permission_and_confirmation_and_suspends_without_deleting_history(): void
    {
        $vendor = $this->vendor('Suspend Me');
        $product = $this->product($vendor, 'Historical Product', 12, 3, true);
        $order = $this->order('HISTORICAL-ORDER', 'paid');
        $item = $this->orderItem($order, $vendor, $product, 2000);
        $vendorUser = User::factory()->create(['vendor_id' => $vendor->id]);
        $vendorUser->forceFill(['email_verified_at' => now()])->save();
        $vendorUser->assignRole('vendor');

        $this->actingAs($this->administrator())
            ->post(route('marketplace.vendors.terminate', $vendor))
            ->assertSessionHasErrors('confirmed');
        $this->assertSame('active', $vendor->fresh()->status);

        $this->actingAs($this->administrator())
            ->post(route('marketplace.vendors.terminate', $vendor), ['confirmed' => '1'])
            ->assertRedirect(route('marketplace.vendors.show', $vendor))
            ->assertSessionHasNoErrors();

        $this->assertSame('suspended', $vendor->fresh()->status);
        $this->assertDatabaseHas('shop_products', ['id' => $product->id]);
        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
        $this->assertDatabaseHas('users', ['id' => $vendorUser->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Vendor::class,
            'auditable_id' => $vendor->id,
            'action' => 'vendor.suspended',
        ]);
    }

    public function test_suspended_vendor_cannot_access_the_vendor_workspace(): void
    {
        $vendor = $this->vendor('Suspended Workspace Vendor');
        $vendor->update(['status' => 'suspended']);
        $vendorUser = User::factory()->create(['vendor_id' => $vendor->id]);
        $vendorUser->assignRole('vendor');

        $this->actingAs($vendorUser)->get(route('vendor.dashboard'))->assertForbidden();
    }

    public function test_vendor_filter_access_is_authorized(): void
    {
        $vendor = $this->vendor('Linked User Vendor');
        $unauthorized = User::factory()->create();

        $this->actingAs($unauthorized)
            ->get(route('admin.shop-products.index', ['vendor_id' => $vendor->id]))
            ->assertForbidden();
        $this->actingAs($unauthorized)
            ->post(route('marketplace.vendors.terminate', $vendor), ['confirmed' => '1'])
            ->assertForbidden();
    }

    public function test_dedicated_user_management_can_create_vendor_linked_users(): void
    {
        $vendor = $this->vendor('User Management Vendor');
        $admin = $this->administrator();
        $admin->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($admin)
            ->get(route('marketplace.users.index'))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('marketplace.users.create'))
            ->assertOk()
            ->assertSee('Vendor account');

        $vendorRole = Role::query()->where('slug', 'vendor')->firstOrFail();
        $this->actingAs($admin)->post(route('marketplace.users.store'), [
            'name' => 'Dedicated Vendor User',
            'email' => 'dedicated-vendor@example.com',
            'phone' => '0788001234',
            'password' => 'VendorPassword2026!',
            'password_confirmation' => 'VendorPassword2026!',
            'vendor_id' => $vendor->id,
            'roles' => [$vendorRole->id],
        ])->assertRedirect(route('marketplace.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'dedicated-vendor@example.com',
            'vendor_id' => $vendor->id,
        ]);
    }

    private function administrator(): User
    {
        return User::query()->where('email', 'admin@example.com')->firstOrFail();
    }

    private function vendor(string $name): Vendor
    {
        return Vendor::query()->create([
            'business_name' => $name,
            'status' => 'active',
            'email' => str_replace(' ', '', strtolower($name)).'@example.com',
            'partner_display_status' => 'draft',
        ]);
    }

    private function product(Vendor $vendor, string $name, int $stock, int $reserved, bool $tracksInventory): ShopProduct
    {
        return ShopProduct::query()->create([
            'product_name' => $name,
            'slug' => str()->slug($name).'-'.str()->random(5),
            'status' => $tracksInventory && $stock <= $reserved ? 'out_of_stock' : 'in_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $this->category->id,
            'vendor_id' => $vendor->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
            'stock_quantity' => $stock,
            'reserved_quantity' => $reserved,
            'tracks_inventory' => $tracksInventory,
        ]);
    }

    private function order(string $reference, string $paymentStatus): Order
    {
        return Order::query()->create([
            'reference' => $reference,
            'customer_name' => 'Vendor Detail Customer',
            'phone' => '0788000000',
            'delivery_address' => 'Kigali',
            'subtotal' => 10000,
            'total' => 10000,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => 'completed',
        ]);
    }

    private function orderItem(Order $order, Vendor $vendor, ShopProduct $product, int $lineTotal): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'shop_product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'product_name' => $product->product_name,
            'unit_price' => $lineTotal,
            'quantity' => 1,
            'line_total' => $lineTotal,
            'fulfillment_status' => 'completed',
            'assignment_status' => 'accepted',
        ]);
    }
}
