<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorAvailabilityRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAvailabilityConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ShopCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MarketplaceUserSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrator');
        $this->admin = $this->admin->fresh();
        $this->category = ShopCategory::query()->create([
            'name' => 'Availability Products',
            'slug' => 'availability-products',
            'active' => true,
        ]);
    }

    public function test_admin_creates_one_scoped_request_per_vendor_and_prevents_pending_duplicates(): void
    {
        $vendorA = $this->vendor('Availability Vendor A');
        $vendorB = $this->vendor('Availability Vendor B');
        $vendorUserA = $this->vendorUser($vendorA);
        $vendorUserB = $this->vendorUser($vendorB);
        $order = $this->order('AVAILABILITY-MULTI-VENDOR');
        $itemA = $this->item($order, $vendorA, 'Vendor A Product');
        $itemASecond = $this->item($order, $vendorA, 'Vendor A Second Product');
        $this->item($order, $vendorB, 'Vendor B Product');

        $this->actingAs($this->admin)
            ->post(route('marketplace.orders.availability.request', $order))
            ->assertRedirect();
        $this->assertDatabaseCount('vendor_availability_requests', 2);
        $this->assertDatabaseCount('vendor_availability_request_items', 3);

        $requestA = VendorAvailabilityRequest::query()->where('vendor_id', $vendorA->id)->firstOrFail();
        $requestB = VendorAvailabilityRequest::query()->where('vendor_id', $vendorB->id)->firstOrFail();
        $this->assertTrue($vendorUserA->notifications()->where('data->dedupe_key', 'availability-request-'.$requestA->id.'-'.$vendorA->id)->exists());
        $this->assertTrue($vendorUserB->notifications()->where('data->dedupe_key', 'availability-request-'.$requestB->id.'-'.$vendorB->id)->exists());
        $this->assertSame([$itemA->id, $itemASecond->id], $requestA->items()->pluck('order_item_id')->all());
        $this->assertSame(1, $requestB->items()->count());

        $this->actingAs($vendorUserA)
            ->get(route('vendor.products.index', ['tab' => 'availability']))
            ->assertOk()
            ->assertSee('Availability Confirmation')
            ->assertSee('My Products')
            ->assertSee('>Available</button>', false)
            ->assertSee('>Not Available</button>', false)
            ->assertDontSee('Partially Available')
            ->assertSee('Vendor A Product')
            ->assertSee('Vendor A Second Product')
            ->assertDontSee('Vendor B Product');
        $this->actingAs($vendorUserB)
            ->get(route('vendor.products.index', ['tab' => 'availability']))
            ->assertOk()
            ->assertSee('Vendor B Product')
            ->assertDontSee('Vendor A Product');

        $this->actingAs($this->admin)
            ->post(route('marketplace.orders.availability.request', $order))
            ->assertRedirect();
        $this->assertDatabaseCount('vendor_availability_requests', 2);
    }

    public function test_vendor_responses_are_validated_scoped_and_aggregated_on_admin_order(): void
    {
        $vendorA = $this->vendor('Availability Response A');
        $vendorB = $this->vendor('Availability Response B');
        $vendorUserA = $this->vendorUser($vendorA);
        $vendorUserB = $this->vendorUser($vendorB);
        $order = $this->order('AVAILABILITY-RESPONSES');
        $itemA = $this->item($order, $vendorA, 'Vendor A Requested Product', 3);
        $itemB = $this->item($order, $vendorB, 'Fully Available Product', 2);

        $this->actingAs($this->admin)->post(route('marketplace.orders.availability.request', $order));
        $requestA = VendorAvailabilityRequest::query()->where('vendor_id', $vendorA->id)->firstOrFail();
        $requestB = VendorAvailabilityRequest::query()->where('vendor_id', $vendorB->id)->firstOrFail();
        $requestItemA = $requestA->items()->firstOrFail();
        $requestItemB = $requestB->items()->firstOrFail();

        $this->actingAs($vendorUserB)
            ->post(route('vendor.availability.respond', $requestA), [
                'availability_status' => 'available',
            ])
            ->assertNotFound();

        $this->actingAs($vendorUserA)
            ->from(route('vendor.products.index', ['tab' => 'availability']))
            ->post(route('vendor.availability.respond', $requestA), [
                'availability_status' => 'partially_available',
                'availability_quantities' => [$requestItemA->id => 1],
            ])
            ->assertSessionHasErrors('availability_status');
        $this->assertSame(VendorAvailabilityRequest::PENDING, $requestA->fresh()->status);

        $this->actingAs($vendorUserA)
            ->post(route('vendor.availability.respond', $requestA), [
                'availability_status' => 'available',
            ])
            ->assertRedirect(route('vendor.products.index', ['tab' => 'availability']));
        $this->assertSame(VendorAvailabilityRequest::AVAILABLE, $requestA->fresh()->status);
        $this->assertSame($itemA->quantity, $requestItemA->fresh()->available_quantity);
        $this->assertTrue($this->admin->notifications()->where('data->dedupe_key', 'availability-response-'.$requestA->id.'-available')->exists());

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Pending Availability Confirmation')
            ->assertSee('Vendor A Requested Product')
            ->assertSee('Available quantity: 3')
            ->assertSee('Available');

        $this->actingAs($vendorUserB)
            ->post(route('vendor.availability.respond', $requestB), [
                'availability_status' => 'available',
            ])
            ->assertRedirect();
        $this->assertSame($itemB->quantity, $requestItemB->fresh()->available_quantity);

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Availability Confirmed')
            ->assertSee('Fully Available Product')
            ->assertSee('Vendor A Requested Product');
    }

    public function test_availability_uses_product_vendor_owner_when_order_item_is_not_allocated(): void
    {
        $vendorA = $this->vendor('Product Owner Vendor A');
        $vendorB = $this->vendor('Product Owner Vendor B');
        $order = $this->order('PRODUCT-OWNER-AVAILABILITY');
        $this->item($order, $vendorA, 'Owned by Vendor A');
        $this->item($order, $vendorB, 'Owned by Vendor B');

        $response = $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Product Owner Vendor A')
            ->assertSee('Product Owner Vendor B')
            ->assertSee('Owned by Vendor A')
            ->assertSee('Owned by Vendor B');

        $groups = $response->viewData('availabilityGroups');
        $this->assertCount(2, $groups);
        $this->assertSame([$vendorA->id, $vendorB->id], $groups->pluck('vendor.id')->all());

        $this->actingAs($this->admin)
            ->post(route('marketplace.orders.availability.request', $order))
            ->assertRedirect();
        $this->assertDatabaseCount('vendor_availability_requests', 2);
        $this->assertSame(
            [null, null],
            $order->items()->orderBy('id')->pluck('vendor_id')->all(),
            'Availability discovery must not rewrite existing order-item allocation fields.'
        );
    }

    public function test_admin_created_products_are_not_requested_and_mixed_orders_only_group_vendor_owned_products(): void
    {
        $vendor = $this->vendor('Mixed Ownership Vendor');
        $order = $this->order('MIXED-PRODUCT-OWNERS');
        $vendorItem = $this->item($order, $vendor, 'Vendor-owned product');
        $adminItem = $this->adminItem($order, 'Admin-managed product');

        $response = $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Vendor-owned product')
            ->assertSee('Admin-managed product')
            ->assertSee('Mixed Ownership Vendor');
        $groups = $response->viewData('availabilityGroups');
        $this->assertCount(1, $groups);
        $this->assertSame([$vendorItem->id], $groups->first()['items']->modelKeys());

        $this->actingAs($this->admin)
            ->post(route('marketplace.orders.availability.request', $order))
            ->assertRedirect();
        $request = VendorAvailabilityRequest::query()->firstOrFail();
        $this->assertSame($vendor->id, $request->vendor_id);
        $this->assertSame([$vendorItem->id], $request->items()->pluck('order_item_id')->all());
        $this->assertNotContains($adminItem->id, $request->items()->pluck('order_item_id')->all());

        $adminOnlyOrder = $this->order('ADMIN-ONLY-PRODUCTS');
        $this->adminItem($adminOnlyOrder, 'Admin-only catalog product');
        $adminOnlyResponse = $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $adminOnlyOrder))
            ->assertOk()
            ->assertSee('No vendor-managed products require availability confirmation.');
        $this->assertCount(0, $adminOnlyResponse->viewData('availabilityGroups'));
    }

    public function test_unknown_product_ownership_is_visible_instead_of_being_treated_as_admin_owned(): void
    {
        $order = $this->order('UNKNOWN-PRODUCT-OWNER');
        OrderItem::query()->create([
            'order_id' => $order->id,
            'shop_product_id' => null,
            'vendor_id' => null,
            'product_name' => 'Unlinked historical product',
            'unit_price' => 1000,
            'quantity' => 1,
            'line_total' => 1000,
            'fulfillment_status' => 'pending_confirmation',
            'assignment_status' => 'unassigned',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Product ownership needs review.')
            ->assertSee('Unlinked historical product')
            ->assertDontSee('No vendor-managed products require availability confirmation.');

        $this->assertCount(1, $response->viewData('availabilityOwnershipIssues'));
    }

    public function test_admin_orders_tabs_follow_approval_status_independently_of_allocation_and_availability(): void
    {
        $adminAllocatedOrder = $this->order('ADMIN-ALLOCATED-REMAINS-PENDING');
        $adminAllocatedOrder->update(['fulfillment_status' => 'pending_confirmation']);
        $adminItem = $this->adminItem($adminAllocatedOrder, 'Admin-handled item');

        $this->actingAs($this->admin)->post(route('marketplace.orders.save-changes', $adminAllocatedOrder), [
            'allocate_order' => 'yes',
            'order_allocation' => 'admin',
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect();
        $this->assertSame('pending_confirmation', $adminAllocatedOrder->fresh()->fulfillment_status);
        $this->assertSame('admin_handled', $adminItem->fresh()->assignment_status);
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('ADMIN-ALLOCATED-REMAINS-PENDING')
            ->assertSee('Not Requested');

        $vendor = $this->vendor('Approval State Vendor');
        $vendorUser = $this->vendorUser($vendor);
        $vendorOrder = $this->order('VENDOR-ALLOCATED-REMAINS-PENDING');
        $vendorOrder->update(['fulfillment_status' => 'pending_confirmation']);
        $vendorItem = $this->item($vendorOrder, $vendor, 'Vendor approval state item');
        $vendorItem->update(['assignment_status' => 'unassigned']);

        $this->actingAs($this->admin)->post(route('marketplace.orders.save-changes', $vendorOrder), [
            'allocate_order' => 'yes',
            'order_allocation' => (string) $vendor->id,
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect();
        $this->assertSame('pending_confirmation', $vendorOrder->fresh()->fulfillment_status);
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('VENDOR-ALLOCATED-REMAINS-PENDING')
            ->assertSee('Not Requested');

        $this->actingAs($this->admin)->post(route('marketplace.orders.availability.request', $vendorOrder));
        $availabilityRequest = VendorAvailabilityRequest::query()
            ->where('order_id', $vendorOrder->id)
            ->firstOrFail();
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('Pending Confirmation');

        $this->actingAs($vendorUser)->post(route('vendor.availability.respond', $availabilityRequest), [
            'availability_status' => 'available',
        ])->assertRedirect();
        $this->assertSame('pending_confirmation', $vendorOrder->fresh()->fulfillment_status);
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('VENDOR-ALLOCATED-REMAINS-PENDING')
            ->assertSee('Availability Confirmed');

        $this->actingAs($this->admin)->post(route('marketplace.orders.approve', $vendorOrder))
            ->assertRedirect(route('marketplace.orders.index', ['tab' => 'approved']));
        $this->assertSame('confirmed', $vendorOrder->fresh()->fulfillment_status);
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertDontSee('VENDOR-ALLOCATED-REMAINS-PENDING');
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'approved']))
            ->assertOk()
            ->assertSee('VENDOR-ALLOCATED-REMAINS-PENDING')
            ->assertSee('Availability Confirmed');

        $availabilityRequest->update(['status' => VendorAvailabilityRequest::UNAVAILABLE]);
        $availabilityRequest->items()->update([
            'status' => VendorAvailabilityRequest::UNAVAILABLE,
            'available_quantity' => 0,
            'reason' => 'Vendor stock is unavailable.',
        ]);
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'approved']))
            ->assertOk()
            ->assertSee('VENDOR-ALLOCATED-REMAINS-PENDING')
            ->assertSee('Action Required');
    }

    public function test_unavailable_response_requires_reason_and_all_full_responses_confirm_order_availability(): void
    {
        $vendor = $this->vendor('Unavailable Product Vendor');
        $vendorUser = $this->vendorUser($vendor);
        $order = $this->order('AVAILABILITY-UNAVAILABLE');
        $this->item($order, $vendor, 'Unavailable Product');

        $this->actingAs($this->admin)->post(route('marketplace.orders.availability.request', $order));
        $availabilityRequest = VendorAvailabilityRequest::query()->firstOrFail();

        $this->actingAs($vendorUser)
            ->from(route('vendor.products.index', ['tab' => 'availability']))
            ->post(route('vendor.availability.respond', $availabilityRequest), [
                'availability_status' => 'unavailable',
            ])
            ->assertSessionHasErrors('reason');
        $this->actingAs($vendorUser)
            ->post(route('vendor.availability.respond', $availabilityRequest), [
                'availability_status' => 'unavailable',
                'reason' => 'Supplier stock is depleted.',
            ])
            ->assertRedirect();
        $this->assertSame(VendorAvailabilityRequest::UNAVAILABLE, $availabilityRequest->fresh()->status);
        $this->assertTrue($this->admin->notifications()->where(
            'data->dedupe_key',
            'availability-response-'.$availabilityRequest->id.'-unavailable'
        )->exists());

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Action Required')
            ->assertSee('Supplier stock is depleted.');

        $anotherOrder = $this->order('AVAILABILITY-CONFIRMED');
        $this->item($anotherOrder, $vendor, 'Available Product');
        $this->actingAs($this->admin)->post(route('marketplace.orders.availability.request', $anotherOrder));
        $anotherRequest = VendorAvailabilityRequest::query()->where('order_id', $anotherOrder->id)->firstOrFail();
        $this->actingAs($vendorUser)
            ->post(route('vendor.availability.respond', $anotherRequest), [
                'availability_status' => 'available',
            ])
            ->assertRedirect();
        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $anotherOrder))
            ->assertOk()
            ->assertSee('Availability Confirmed');
        $this->actingAs($this->admin)->post(route('marketplace.orders.availability.request', $anotherOrder));
        $this->assertSame(1, VendorAvailabilityRequest::query()->where('order_id', $anotherOrder->id)->count());
    }

    private function vendor(string $name): Vendor
    {
        return Vendor::query()->create([
            'business_name' => $name,
            'status' => 'active',
            'email' => str_replace(' ', '', strtolower($name)).'@example.com',
            'phone' => '0788000000',
        ]);
    }

    private function vendorUser(Vendor $vendor): User
    {
        $user = User::factory()->create(['vendor_id' => $vendor->id]);
        $user->assignRole('vendor');

        return $user->fresh();
    }

    private function order(string $reference): Order
    {
        return Order::query()->create([
            'reference' => $reference,
            'customer_name' => 'Availability Test Customer',
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'total' => 30000,
            'subtotal' => 30000,
            'fulfillment_status' => 'confirmed',
        ]);
    }

    private function item(Order $order, Vendor $vendor, string $name, int $quantity = 2): OrderItem
    {
        $product = ShopProduct::query()->create([
            'product_name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name.'-'.$vendor->id),
            'status' => 'in_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $this->category->id,
            'vendor_id' => $vendor->id,
            'created_by' => $this->vendorUser($vendor)->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'shop_product_id' => $product->id,
            'vendor_id' => null,
            'product_name' => $name,
            'unit_price' => 10000,
            'quantity' => $quantity,
            'line_total' => 10000 * $quantity,
            'fulfillment_status' => 'processing',
            'assignment_status' => 'assigned',
        ]);
    }

    private function adminItem(Order $order, string $name): OrderItem
    {
        $product = ShopProduct::query()->create([
            'product_name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'status' => 'in_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $this->category->id,
            'vendor_id' => null,
            'created_by' => $this->admin->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'shop_product_id' => $product->id,
            'vendor_id' => null,
            'product_name' => $name,
            'unit_price' => 10000,
            'quantity' => 1,
            'line_total' => 10000,
            'fulfillment_status' => 'processing',
            'assignment_status' => 'unassigned',
        ]);
    }
}
