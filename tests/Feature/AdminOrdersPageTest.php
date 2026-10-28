<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrdersPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MarketplaceUserSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrator');
        $this->admin = $this->admin->fresh();
        $this->vendor = Vendor::query()->create([
            'business_name' => 'Orders Test Vendor',
            'status' => 'active',
            'email' => 'orders-vendor@example.com',
            'phone' => '0788000000',
        ]);
    }

    public function test_admin_delivery_tabs_search_and_vendor_filter_use_real_delivery_tasks(): void
    {
        $otherVendor = Vendor::query()->create([
            'business_name' => 'Second Delivery Vendor',
            'status' => 'active',
            'email' => 'second-delivery-vendor@example.com',
            'phone' => '0788000001',
        ]);
        $pendingOrder = $this->order('DELIVERY-PENDING', 'Pending Delivery Customer', 'processing', 'paid');
        $pendingItem = $this->item($pendingOrder, 'Pending Delivery Product', $this->vendor);
        $transitOrder = $this->order('DELIVERY-TRANSIT', 'Transit Customer', 'processing', 'paid');
        $transitItem = $this->item($transitOrder, 'Transit Product', $otherVendor);
        $deliveredOrder = $this->order('DELIVERY-DONE', 'Delivered Customer', 'delivered', 'paid');
        $deliveredItem = $this->item($deliveredOrder, 'Delivered Product', $this->vendor);

        foreach ([
            ['DELIVERY-TASK-PENDING', $pendingOrder, $pendingItem, $this->vendor, 'assigned'],
            ['DELIVERY-TASK-TRANSIT', $transitOrder, $transitItem, $otherVendor, 'in_transit'],
            ['DELIVERY-TASK-DONE', $deliveredOrder, $deliveredItem, $this->vendor, 'delivered'],
        ] as [$reference, $order, $item, $vendor, $status]) {
            DeliveryTask::query()->create([
                'reference' => $reference,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'vendor_id' => $vendor->id,
                'status' => $status,
                'delivery_method' => 'third_party_courier',
                'expected_delivery_at' => '2026-10-28',
                'tracking_reference' => 'TRACK-'.$status,
            ]);
        }

        $pendingActionLink = '<a class="btn btn-sm btn-primary" href="'.route('marketplace.deliveries.prepare.existing', 'DELIVERY-TASK-PENDING').'">Prepare Delivery</a>';
        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index'))
            ->assertOk()
            ->assertSee('DELIVERY-TASK-PENDING')
            ->assertSee('Orders Test Vendor')
            ->assertSee($pendingActionLink, false)
            ->assertDontSee(route('marketplace.deliveries.prepare.select', ['order_id' => $pendingOrder->id]), false)
            ->assertDontSee('View Details')
            ->assertDontSee('DELIVERY-TASK-TRANSIT')
            ->assertSee('Third-party Courier')
            ->assertSee('Oct 28, 2026')
            ->assertSee('Pending')
            ->assertSee('In Transit')
            ->assertSee('Delivered')
            ->assertSee('delivery-filter-grid');

        $transitResponse = $this->actingAs($this->admin)->get(route('marketplace.deliveries.index', [
            'status' => 'in_transit',
            'q' => 'TRACK-in_transit',
            'vendor_id' => $otherVendor->id,
            'delivery_method' => 'third_party_courier',
            'expected_date' => '2026-10-28',
        ]))
            ->assertOk()
            ->assertSee('DELIVERY-TASK-TRANSIT')
            ->assertSee('Transit Product × 1')
            ->assertSee('Third-party Courier')
            ->assertSee(str_replace('&', '&amp;', route('marketplace.deliveries.index', [
                'q' => 'TRACK-in_transit',
                'vendor_id' => $otherVendor->id,
                'delivery_method' => 'third_party_courier',
                'expected_date' => '2026-10-28',
                'status' => 'assigned',
            ])), false)
            ->assertDontSee('DELIVERY-TASK-PENDING')
            ->assertDontSee('DELIVERY-TASK-DONE');
        $this->assertStringNotContainsString('">Prepare Delivery</a>', $transitResponse->getContent());

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index', [
            'status' => 'delivered',
            'vendor_id' => $this->vendor->id,
        ]))
            ->assertOk()
            ->assertSee('DELIVERY-TASK-DONE')
            ->assertDontSee('DELIVERY-TASK-TRANSIT');
    }

    public function test_pending_delivery_prepare_button_opens_and_updates_the_existing_delivery(): void
    {
        Storage::fake('local');
        $order = $this->order('EXISTING-PREPARE-DELIVERY', 'Existing Delivery Customer', 'processing', 'paid');
        $item = $this->item($order, 'Existing Delivery Product', $this->vendor);
        $task = DeliveryTask::query()->create([
            'reference' => 'EXISTING-PREPARE-TASK',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'assigned',
            'courier_name' => 'Original Courier',
            'delivery_phone' => '0788000000',
            'vehicle_registration' => 'RAA 100 A',
            'expected_delivery_at' => now()->addDays(2)->toDateString(),
            'tracking_reference' => 'ORIGINAL-TRACK',
            'delivery_notes' => 'Original notes',
        ]);

        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized)->get(route('marketplace.deliveries.prepare.existing', $task))->assertForbidden();
        $this->actingAs($unauthorized)->post(route('marketplace.deliveries.prepare.update', $task), [
            'courier_name' => 'Unauthorized Change',
        ])->assertForbidden();
        $this->assertSame('Original Courier', $task->fresh()->courier_name);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index'))
            ->assertOk()
            ->assertSee(route('marketplace.deliveries.prepare.existing', $task), false);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.prepare.existing', $task))
            ->assertOk()
            ->assertSee('Existing Delivery Customer')
            ->assertSee('Existing Delivery Product')
            ->assertSee('value="Original Courier"', false)
            ->assertSee('value="ORIGINAL-TRACK"', false)
            ->assertSee(route('marketplace.deliveries.show', $task), false)
            ->assertSee('Save Changes')
            ->assertSee('Saving a Pending delivery moves it to In Transit.')
            ->assertDontSee('name="delivery_method"', false);

        $this->actingAs($this->admin)->post(route('marketplace.deliveries.prepare.update', $task), [
            'courier_name' => '',
            'delivery_phone' => 'not a phone',
            'tracking_reference' => 'INVALID-UPDATE',
        ])->assertSessionHasErrors(['courier_name', 'delivery_phone']);
        $this->assertSame('assigned', $task->fresh()->status);
        $this->assertSame('Original Courier', $task->fresh()->courier_name);
        $this->assertSame('ORIGINAL-TRACK', $task->fresh()->tracking_reference);

        $this->actingAs($this->admin)->post(route('marketplace.deliveries.prepare.update', $task), [
            'courier_name' => 'Updated Courier',
            'delivery_phone' => '+250 788 111 222',
            'vehicle_registration' => 'RAA 200 B',
            'expected_delivery_at' => now()->addDays(3)->toDateString(),
            'tracking_reference' => 'UPDATED-TRACK',
            'delivery_notes' => 'Updated dispatch notes',
            'dispatch_proof' => UploadedFile::fake()->create('updated-ticket.pdf', 24, 'application/pdf'),
        ])->assertRedirect(route('marketplace.deliveries.index', ['status' => 'in_transit']))
            ->assertSessionHas('success', 'Delivery information saved and delivery moved to In Transit.');

        $task->refresh();
        $this->assertSame(1, DeliveryTask::query()->where('order_id', $order->id)->count());
        $this->assertSame('in_transit', $task->status);
        $this->assertNotNull($task->dispatched_at);
        $this->assertSame($item->id, $task->order_item_id);
        $this->assertSame($this->vendor->id, $task->vendor_id);
        $this->assertSame('Updated Courier', $task->courier_name);
        $this->assertSame('UPDATED-TRACK', $task->tracking_reference);
        $this->assertSame('Updated dispatch notes', $task->delivery_notes);
        $this->assertNotNull($task->dispatch_proof_path);
        Storage::disk('local')->assertExists($task->dispatch_proof_path);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $task->getMorphClass(),
            'auditable_id' => $task->id,
            'action' => 'delivery.dispatched',
        ]);
        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index', ['status' => 'in_transit']))
            ->assertOk()
            ->assertSee($task->reference)
            ->assertDontSee('View Details');

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.show', $task))
            ->assertOk()
            ->assertSee('UPDATED-TRACK')
            ->assertSee('Updated dispatch notes');

        $deliveredOrder = $this->order('EXISTING-DELIVERED-ORDER', 'Delivered Status Customer', 'delivered', 'paid');
        $deliveredItem = $this->item($deliveredOrder, 'Delivered Status Product', $this->vendor);
        $deliveredTask = DeliveryTask::query()->create([
            'reference' => 'EXISTING-DELIVERED-TASK',
            'order_id' => $deliveredOrder->id,
            'order_item_id' => $deliveredItem->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'delivered',
            'courier_name' => 'Completed Courier',
            'delivery_phone' => '0788000000',
        ]);
        $this->actingAs($this->admin)->post(route('marketplace.deliveries.prepare.update', $deliveredTask), [
            'courier_name' => 'Updated Completed Courier',
            'delivery_phone' => '0788111222',
        ])->assertRedirect(route('marketplace.deliveries.index', ['status' => 'delivered']))
            ->assertSessionHas('success', 'Delivery information saved.');
        $this->assertSame('delivered', $deliveredTask->fresh()->status);
        $this->assertSame('Updated Completed Courier', $deliveredTask->fresh()->courier_name);
    }

    public function test_order_tabs_filter_real_orders_and_show_matching_counts(): void
    {
        $reviewOrder = $this->order('ORDER-REVIEW', 'Review Customer', 'pending_confirmation', 'pending');
        $this->item($reviewOrder, 'Review Product', $this->vendor);

        $cancelRequest = $this->order('ORDER-CANCEL-REQUEST', 'Cancel Customer', 'cancellation_requested', 'failed');

        $allocatedOrder = $this->order('ORDER-ALLOCATED', 'Allocated Customer', 'confirmed', 'paid');
        $this->item($allocatedOrder, 'Allocated Product', $this->vendor);
        $acceptedVendorOrder = $this->order('ORDER-ACCEPTED-VENDOR', 'Accepted Vendor Customer', 'processing', 'paid');
        $this->item($acceptedVendorOrder, 'Accepted Vendor Product', $this->vendor)->update([
            'assignment_status' => 'accepted',
        ]);

        $adminAllocatedOrder = $this->order('ORDER-ADMIN-ALLOCATED', 'Admin Assigned Customer', 'confirmed', 'paid');
        $adminAllocatedOrder->update(['assigned_officer_id' => $this->admin->id]);
        $this->item($adminAllocatedOrder, 'Admin Fulfillment Product', null)->update([
            'assignment_status' => 'admin_handled',
            'fulfillment_status' => 'processing',
        ]);

        $progressOrder = $this->order('ORDER-PROGRESS', 'Progress Customer', 'processing', 'paid');
        $this->item($progressOrder, 'Progress Product', null);
        DeliveryTask::query()->create([
            'reference' => 'DELIVERY-PROGRESS',
            'order_id' => $progressOrder->id,
            'status' => 'in_transit',
        ]);

        $completedOrder = $this->order('ORDER-COMPLETED', 'Completed Customer', 'completed', 'pending');
        $this->item($completedOrder, 'Completed Product', null);
        $deliveredAt = now();
        DeliveryTask::query()->create([
            'reference' => 'DELIVERY-COMPLETED',
            'order_id' => $completedOrder->id,
            'status' => 'delivered',
            'delivered_at' => $deliveredAt,
        ]);

        $cancelledOrder = $this->order('ORDER-CANCELLED', 'Cancelled Customer', 'cancelled', 'paid');
        $this->item($cancelledOrder, 'Cancelled Product', null);

        $reviewResponse = $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'pending']));
        $reviewResponse->assertOk()
            ->assertSee('ORDER-REVIEW')
            ->assertSee('Review Order')
            ->assertDontSee('Send to Delivery')
            ->assertSee('ORDER-CANCEL-REQUEST')
            ->assertDontSee('ORDER-CANCELLED')
            ->assertSee('Pending Orders')
            ->assertSee('Paid Orders')
            ->assertSee('flex-wrap: nowrap', false)
            ->assertSee('overflow-x: auto', false)
            ->assertSee('grid-template-columns: repeat(2, minmax(0, 1fr))', false)
            ->assertViewHas('tabCounts', function (array $counts): bool {
                $this->assertSame(['pending', 'paid'], array_keys($counts));
                $this->assertSame(2, $counts['pending']);
                $this->assertSame(1, $counts['paid']);

                return true;
            });
        $tabNavigation = substr(
            $reviewResponse->getContent(),
            strpos($reviewResponse->getContent(), '<nav class="orders-tabs"'),
        );
        $tabNavigation = substr($tabNavigation, 0, strpos($tabNavigation, '</nav>'));
        $this->assertStringNotContainsString('In Delivery', $tabNavigation);
        $this->assertStringNotContainsString('Completed', $tabNavigation);
        $this->assertStringNotContainsString('Cancelled', $tabNavigation);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee('ORDER-ADMIN-ALLOCATED')
            ->assertDontSee('ORDER-ALLOCATED')
            ->assertDontSee('ORDER-ACCEPTED-VENDOR')
            ->assertSee('Send to Delivery')
            ->assertSee(route('marketplace.orders.deliveries.store', $adminAllocatedOrder), false)
            ->assertDontSee('Review Order')
            ->assertDontSee('Manage Fulfillment')
            ->assertDontSee('orders-summary-card')
            ->assertDontSee('Total orders')
            ->assertDontSee('Outstanding payments');

        $paidResponse = $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'paid']));
        $paidResponse
            ->assertOk()
            ->assertSee('ORDER-ADMIN-ALLOCATED')
            ->assertSee('Send to Delivery')
            ->assertDontSee('Review Order')
            ->assertDontSee('Admin Fulfillment Product')
            ->assertDontSee('Allocated Product')
            ->assertDontSee('Accepted Vendor Product')
            ->assertDontSee('ORDER-PROGRESS')
            ->assertDontSee('ORDER-COMPLETED')
            ->assertDontSee('ORDER-CANCELLED');

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'in_delivery']))
            ->assertRedirect(route('marketplace.deliveries.index'));

        $completedResponse = $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'completed']));
        $completedResponse->assertRedirect(route('marketplace.reports.index', ['category' => 'orders']));

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'cancelled']))
            ->assertRedirect(route('marketplace.reports.index', ['category' => 'orders']));

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'cancelled', 'status' => 'cancelled']))
            ->assertRedirect(route('marketplace.reports.index', ['category' => 'orders']));

        $this->assertSame('completed', $completedOrder->fresh()->fulfillment_status);
        $this->assertSame('cancelled', $cancelledOrder->fresh()->fulfillment_status);
        $this->assertSame($deliveredAt->toDateTimeString(), $completedOrder->fresh()->deliveries()->firstOrFail()->delivered_at->toDateTimeString());
    }

    public function test_unpaid_cash_on_delivery_order_remains_in_pending_orders(): void
    {
        $order = $this->order('ORDER-COD-PENDING', 'COD Customer', 'confirmed', 'pending');
        $order->update(['payment_method' => 'cash_on_delivery']);
        $this->item($order, 'COD Product', null);

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('ORDER-COD-PENDING');

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee('ORDER-COD-PENDING');
    }

    public function test_admin_allocates_entire_order_before_accepting_or_recording_payment(): void
    {
        $order = $this->order('ORDER-ACCEPT-ADMIN', 'Accept Admin Customer', 'pending_confirmation', 'pending');
        $item = $this->item($order, 'Admin Acceptance Product', null);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('Review Order')
            ->assertSee(route('marketplace.orders.show', ['order' => $order, 'allocate' => 1]), false)
            ->assertDontSee('Accept Order');

        $this->actingAs($this->admin)->get(route('marketplace.orders.show', ['order' => $order, 'accept' => 1]))
            ->assertOk()
            ->assertSee('Who will handle this order?')
            ->assertDontSee('Confirm Accept Order')
            ->assertDontSee('Has the customer paid?')
            ->assertDontSee('Payment information');

        $this->actingAs($this->admin)->get(route('marketplace.orders.show', ['order' => $order, 'allocate' => 1]))
            ->assertOk()
            ->assertSee('Who will handle this order?')
            ->assertSee('name="handling_choice"', false)
            ->assertSee('name="order_allocation"', false)
            ->assertSee('name="allocation_vendor_id"', false)
            ->assertDontSee('name="allocations[', false)
            ->assertDontSee('Payment information');

        $this->actingAs($this->admin)->post(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => 'admin',
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order))
            ->assertSessionHas('success', 'Order allocated to Admin. Review the saved allocation to accept or reject it.');

        $this->assertSame('pending_confirmation', $order->fresh()->fulfillment_status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('admin_handled', $item->fresh()->assignment_status);
        $this->assertNull($item->fresh()->vendor_id);
        $this->assertDatabaseCount('order_payments', 0);

        $this->actingAs($this->admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Accept Order')
            ->assertSee('Cancel order')
            ->assertSee('Payment information')
            ->assertSee('Has the customer paid?');

        $this->actingAs($this->admin)->get(route('marketplace.orders.show', ['order' => $order, 'accept' => 1]))
            ->assertOk()
            ->assertSee('Accept Order')
            ->assertSee('Has the customer paid?')
            ->assertSee('No', false);

        $this->actingAs($this->admin)->post(route('marketplace.orders.save-changes', $order), [
            'accept_order' => 'yes',
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('marketplace.orders.show', $order))
            ->assertSessionHas('success', 'Order accepted. Payment is still pending.');

        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->assertDatabaseCount('order_payments', 0);
        $this->actingAs($this->admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Order Details');
    }

    public function test_admin_allocation_transfers_every_order_item_to_the_selected_vendor(): void
    {
        $order = $this->order('ORDER-TRANSFER-VENDOR', 'Transfer Customer', 'pending_confirmation', 'pending');
        $firstItem = $this->item($order, 'Transfer Product One', null);
        $secondItem = $this->item($order, 'Transfer Product Two', null);
        $vendorUser = User::factory()->create(['vendor_id' => $this->vendor->id]);
        $vendorUser->assignRole('vendor');
        $vendorUser = $vendorUser->fresh();

        $this->actingAs($this->admin)->postJson(route('marketplace.orders.save-changes', $order), [
            'allocate_order' => 'yes',
            'order_allocation' => (string) $this->vendor->id,
            'has_paid' => 'no',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('message', 'Order successfully allocated to '.$this->vendor->business_name.'.')
            ->assertJsonPath('redirect_url', route('marketplace.orders.index', ['tab' => 'pending']));

        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame($this->vendor->id, $firstItem->fresh()->vendor_id);
        $this->assertSame($this->vendor->id, $secondItem->fresh()->vendor_id);
        $this->assertSame('assigned', $firstItem->fresh()->assignment_status);
        $this->assertSame('assigned', $secondItem->fresh()->assignment_status);
        $this->assertDatabaseCount('order_payments', 0);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertDontSee('ORDER-TRANSFER-VENDOR');

        $this->actingAs($vendorUser)->get(route('vendor.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('ORDER-TRANSFER-VENDOR')
            ->assertSee('Review Order')
            ->assertDontSee('Transfer Product One');

        $this->actingAs($vendorUser)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Transfer Product One')
            ->assertSee('Transfer Product Two')
            ->assertSee('Accept Assignment')
            ->assertDontSee('Payment information')
            ->assertDontSee('Who will handle this order?');
    }

    public function test_send_to_delivery_scopes_mixed_order_to_admin_items_and_removes_it_after_preparation(): void
    {
        Storage::fake('local');
        $order = $this->order('ADMIN-VENDOR-MIXED', 'Mixed Customer', 'processing', 'paid');
        $adminItem = $this->item($order, 'Admin Handled Product', null);
        $adminItem->update([
            'assignment_status' => 'admin_handled',
            'fulfillment_status' => 'confirmed',
        ]);
        $vendorItem = $this->item($order, 'Vendor Allocated Product', $this->vendor);
        $vendorItem->update([
            'assignment_status' => 'accepted',
            'fulfillment_status' => 'ready_for_dispatch',
        ]);

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee('ADMIN-VENDOR-MIXED')
            ->assertSee('Send to Delivery')
            ->assertSee('12,000 RWF')
            ->assertDontSee('Admin Handled Product')
            ->assertDontSee('Vendor Allocated Product');

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Admin Handled Product')
            ->assertSee('Send to Delivery')
            ->assertSee(route('marketplace.orders.deliveries.store', $order), false)
            ->assertSee('Vendor Allocated Product');

        $this->actingAs($this->admin)->postJson(route('marketplace.orders.deliveries.store', $order))
            ->assertOk()
            ->assertJsonPath('message', 'Delivery created and moved to Admin Deliveries. It remains Pending until it is dispatched.')
            ->assertJsonPath('redirect_url', route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertJsonPath('deliveries_url', route('marketplace.deliveries.index', ['status' => 'assigned']))
            ->assertJsonCount(1, 'delivery_references');

        $task = DeliveryTask::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame($adminItem->id, $task->order_item_id);
        $this->assertNull($task->vendor_id);
        $this->assertSame('assigned', $task->status);
        $this->assertSame('ready_for_dispatch', $adminItem->fresh()->fulfillment_status);
        $this->assertSame('processing', $order->fresh()->fulfillment_status);

        $this->actingAs($this->admin)->postJson(route('marketplace.orders.deliveries.store', $order))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery');
        $this->assertDatabaseCount('delivery_tasks', 1);

        $legacyOrder = $this->order('LEGACY-ADMIN-DELIVERY', 'Legacy Admin Customer', 'processing', 'paid');
        $legacyItem = $this->item($legacyOrder, 'Legacy Admin Product', null);
        $legacyItem->update([
            'assignment_status' => 'admin_handled',
            'fulfillment_status' => 'confirmed',
        ]);
        DeliveryTask::query()->create([
            'reference' => 'LEGACY-ADMIN-TASK',
            'order_id' => $legacyOrder->id,
            'status' => 'assigned',
        ]);
        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee('LEGACY-ADMIN-DELIVERY');
        $this->actingAs($this->admin)->postJson(route('marketplace.orders.deliveries.store', $legacyOrder))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery');
        $this->assertDatabaseCount('delivery_tasks', 2);

        $this->actingAs($this->admin)
            ->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee('ADMIN-VENDOR-MIXED');

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index'))
            ->assertOk()
            ->assertSee($task->reference)
            ->assertSee('Admin Handled Product')
            ->assertDontSee('Vendor Allocated Product');
        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee('ADMIN-VENDOR-MIXED');

        $this->assertSame('ready_for_dispatch', $vendorItem->fresh()->fulfillment_status);
        $this->assertSame('accepted', $vendorItem->fresh()->assignment_status);
    }

    public function test_order_search_filters_and_reset_work_together_with_tab_selection(): void
    {
        $matching = $this->order('SEARCH-MATCH-1', 'Aline Search', 'pending_confirmation', 'pending');
        $this->item($matching, 'Search Product', $this->vendor);
        $this->order('SEARCH-OTHER-2', 'Other Customer', 'pending_confirmation', 'paid');

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', [
            'tab' => 'pending',
            'q' => 'Aline Search',
            'status' => 'pending_confirmation',
            'payment' => 'pending',
            'vendor' => $this->vendor->id,
            'responsible' => 'vendor',
        ]))
            ->assertOk()
            ->assertSee('SEARCH-MATCH-1')
            ->assertDontSee('SEARCH-OTHER-2')
            ->assertViewHas('tabCounts', fn (array $counts): bool => $counts['pending'] === 1);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', [
            'tab' => 'pending',
            'status' => 'pending_confirmation',
            'payment' => 'paid',
        ]))
            ->assertOk()
            ->assertSee('SEARCH-OTHER-2')
            ->assertSee('Pending Confirmation');

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', [
            'tab' => 'pending',
            'q' => 'Aline Search',
            'date_from' => now()->addDay()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('No orders found')
            ->assertSee(route('marketplace.orders.index', ['tab' => 'pending']), false);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertDontSee('orders-summary-card')
            ->assertDontSee('Total orders');
    }

    public function test_order_review_shows_linked_customer_and_checkout_address_snapshot(): void
    {
        $category = Shopcategory::query()->create([
            'name' => 'Checkout Test Category',
            'slug' => 'checkout-test-category',
            'active' => true,
        ]);
        $product = ShopProduct::query()->create([
            'product_name' => 'Checkout Test Product',
            'slug' => 'checkout-test-product',
            'status' => 'in_stock',
            'price' => 12000,
            'working_days' => 2,
            'shop_category_id' => $category->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);
        $customer = Customer::query()->create([
            'name' => 'Aline Real Customer',
            'email' => 'aline.real@example.com',
            'phone' => '+250780111222',
            'password' => 'valid-password',
        ]);
        $address = $customer->addresses()->create([
            'recipient_name' => 'Aline Address Recipient',
            'recipient_phone' => '+250780333444',
            'recipient_email' => 'recipient@example.com',
            'address_line' => 'Old Road 10',
            'city' => 'Kigali',
            'district' => 'Gasabo',
            'is_default' => true,
        ]);

        $this->actingAs($customer, 'customers')
            ->post(route('cart.add'), ['product' => $product->slug, 'quantity' => 1])
            ->assertRedirect(route('shop'));

        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee('value="Aline Address Recipient"', false)
            ->assertSee('value="+250780333444"', false)
            ->assertSee('value="recipient@example.com"', false)
            ->assertSee('Old Road 10, Gasabo, Kigali');

        $this->post(route('checkout.place'), [
            'customer_name' => 'Aline Real Customer',
            'email' => 'aline.real@example.com',
            'phone' => '+250780111222',
            'delivery_address' => 'Customer submitted address, Kicukiro District, Kigali City',
            'delivery_notes' => 'Call at the gate',
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect();

        $order = Order::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('Aline Real Customer', $order->customer_name);
        $this->assertSame('aline.real@example.com', $order->email);
        $this->assertSame('+250780111222', $order->phone);
        $this->assertSame('Customer submitted address, Kicukiro District, Kigali City', $order->delivery_address);

        $customer->update([
            'name' => 'Updated Account Name',
            'email' => 'updated.account@example.com',
            'phone' => '+250780999888',
        ]);
        $address->update([
            'address_line' => 'New Current Profile Road',
            'district' => 'Nyarugenge',
        ]);

        $this->actingAs($this->admin, 'web')->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Updated Account Name')
            ->assertSee('updated.account@example.com')
            ->assertSee('+250780999888')
            ->assertSee('Customer submitted address, Kicukiro District, Kigali City')
            ->assertSee($order->created_at->format('M j, Y g:i A'))
            ->assertSee($order->reference)
            ->assertDontSee('New Current Profile Road')
            ->assertDontSee('Aline Address Recipient');
    }

    public function test_order_review_handles_legacy_orders_without_customer_or_delivery_data(): void
    {
        $order = $this->order('ORDER-LEGACY-INCOMPLETE', 'Legacy Customer', 'pending_confirmation', 'pending');
        $order->update([
            'customer_name' => '',
            'phone' => '',
            'email' => null,
            'delivery_address' => '',
        ]);

        $this->actingAs($this->admin, 'web')->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Not provided')
            ->assertSee('ORDER-LEGACY-INCOMPLETE');
    }

    public function test_admin_can_mark_one_vendor_assignment_ready_using_the_shared_validated_transition(): void
    {
        $secondVendor = Vendor::query()->create([
            'business_name' => 'Second Orders Vendor',
            'status' => 'active',
            'email' => 'second-orders-vendor@example.com',
            'phone' => '0788000001',
        ]);
        $order = $this->order('ADMIN-READY-ORDER', 'Admin Ready Customer', 'processing', 'paid');
        $firstItem = $this->item($order, 'First Vendor Product', $this->vendor);
        $firstItem->update([
            'fulfillment_status' => 'processing',
            'assignment_status' => 'accepted',
            'sku' => 'FIRST-001',
            'quantity' => 3,
        ]);
        $secondItem = $this->item($order, 'Second Vendor Product', $secondVendor);
        $secondItem->update([
            'fulfillment_status' => 'processing',
            'assignment_status' => 'accepted',
        ]);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee('ADMIN-READY-ORDER');

        $this->actingAs($this->admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Ready for Delivery')
            ->assertSee('FIRST-001');

        $this->actingAs($this->admin)->post(route('marketplace.orders.status', $order), [
            'fulfillment_status' => 'ready_for_dispatch',
        ])->assertSessionHasErrors('fulfillment_status');
        $this->assertSame('processing', $order->fresh()->fulfillment_status);

        $this->actingAs($this->admin)->post(route('marketplace.orders.vendor-ready-for-delivery', [
            'order' => $order,
            'vendor' => $this->vendor,
        ]))->assertRedirect();

        $this->assertSame('ready_for_dispatch', $firstItem->fresh()->fulfillment_status);
        $this->assertSame('processing', $secondItem->fresh()->fulfillment_status);
        $this->assertSame('processing', $order->fresh()->fulfillment_status);
        $this->assertNotSame('delivered', $order->fresh()->fulfillment_status);
        $this->assertNotSame('completed', $order->fresh()->fulfillment_status);

        $this->actingAs($this->admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Ready for Delivery')
            ->assertSee('Vendor assignment history')
            ->assertSee('First Vendor Product');
    }

    public function test_admin_requests_delivery_and_vendor_completes_only_its_assignment(): void
    {
        $secondVendor = Vendor::query()->create([
            'business_name' => 'Admin Delivery Other Vendor',
            'status' => 'active',
            'email' => 'admin-delivery-other@example.com',
            'phone' => '0788000002',
        ]);
        $order = $this->order('ADMIN-CREATE-DELIVERY', 'Admin Delivery Customer', 'processing', 'paid');
        $order->update([
            'email' => 'admin-customer@example.com',
            'delivery_address' => '22 Delivery Road, Kigali',
        ]);
        $firstItem = $this->item($order, 'Admin Vendor Product', $this->vendor);
        $firstItem->update([
            'fulfillment_status' => 'ready_for_dispatch',
            'assignment_status' => 'accepted',
            'sku' => 'ADMIN-DELIVERY-001',
            'quantity' => 4,
        ]);
        $secondItem = $this->item($order, 'Admin Other Vendor Product', $secondVendor);
        $secondItem->update([
            'fulfillment_status' => 'ready_for_dispatch',
            'assignment_status' => 'accepted',
        ]);

        $vendorUser = User::factory()->create(['vendor_id' => $this->vendor->id]);
        $vendorUser->assignRole('vendor');
        $vendorUser = $vendorUser->fresh();

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee('Create Delivery')
            ->assertDontSee('ADMIN-CREATE-DELIVERY');

        $this->actingAs($this->admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Request Delivery')
            ->assertDontSee('Create Delivery')
            ->assertSee('Admin Delivery Customer')
            ->assertSee('22 Delivery Road, Kigali')
            ->assertSee('ADMIN-DELIVERY-001');

        $this->actingAs($this->admin)->post(route('marketplace.orders.delivery-request', $order), [
            'vendor_id' => $this->vendor->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('notifications', 1);
        $notification = $vendorUser->notifications()->firstOrFail();
        $this->assertSame('Delivery requested', $notification->data['title']);
        $this->assertSame(route('vendor.orders.show', $order->reference), $notification->data['url']);

        $this->actingAs($this->admin)->post(route('marketplace.orders.delivery-request', $order), [
            'vendor_id' => $this->vendor->id,
        ])->assertRedirect();
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($this->admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Delivery requested');

        $this->actingAs($vendorUser)->get(route('marketplace.notifications.index'))
            ->assertOk()
            ->assertSee('Delivery requested')
            ->assertSee($order->reference);
        $this->actingAs($vendorUser)->post(route('marketplace.notifications.read', $notification->id))
            ->assertRedirect(route('vendor.orders.show', $order->reference));
        $this->actingAs($vendorUser)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Prepare Delivery')
            ->assertSee('Admin Vendor Product')
            ->assertDontSee('Admin Other Vendor Product');
        $this->actingAs($vendorUser)->post(route('marketplace.orders.delivery-request', $order), [
            'vendor_id' => $this->vendor->id,
        ])->assertForbidden();

        $this->actingAs($vendorUser)->post(route('vendor.orders.deliveries.store', $order->reference), [
            'courier_name' => 'Local Courier',
            'delivery_phone' => '0788000003',
            'tracking_reference' => 'TRACK-ADMIN-1',
        ])->assertRedirect(route('vendor.deliveries.index', ['status' => 'assigned']));

        $this->assertSame('ready_for_dispatch', $firstItem->fresh()->fulfillment_status);
        $this->assertSame('ready_for_dispatch', $secondItem->fresh()->fulfillment_status);
        $this->assertSame('processing', $order->fresh()->fulfillment_status);
        $task = DeliveryTask::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame($firstItem->id, $task->order_item_id);
        $this->assertSame($this->vendor->id, $task->vendor_id);
        $this->assertSame('assigned', $task->status);
        $this->assertNull($task->delivery_method);
        $this->assertSame('Local Courier', $task->courier_name);
        $this->assertSame('TRACK-ADMIN-1', $task->tracking_reference);

        $this->actingAs($vendorUser)->get(route('vendor.deliveries.index'))
            ->assertOk()
            ->assertSee($task->reference);
        $this->actingAs($vendorUser)->post(route('vendor.orders.deliveries.store', $order->reference), [
            'courier_name' => 'Local Courier',
            'delivery_phone' => '0788000003',
        ])->assertSessionHasErrors('delivery');
        $this->assertDatabaseCount('delivery_tasks', 1);
        $this->assertSame('ready_for_dispatch', $firstItem->fresh()->fulfillment_status);
        $this->actingAs($this->admin)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee($task->reference)
            ->assertSee('Not dispatched');
    }

    public function test_admin_can_prepare_an_admin_handled_delivery_without_completing_the_order(): void
    {
        Storage::fake('local');
        $order = $this->order('ADMIN-PREPARE-DELIVERY', 'Admin Prepared Customer', 'processing', 'paid');
        $item = $this->item($order, 'Admin Prepared Product', null);
        $item->update([
            'assignment_status' => 'admin_handled',
            'fulfillment_status' => 'ready_for_dispatch',
        ]);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee('Send to Delivery')
            ->assertDontSee('Review Order')
            ->assertSee(route('marketplace.orders.show', $order), false);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index'))
            ->assertOk()
            ->assertSee('Admin Prepared Product × 1')
            ->assertSee($order->reference)
            ->assertSee(route('marketplace.deliveries.prepare.select', ['order_id' => $order->id]), false)
            ->assertSee('Prepare Delivery')
            ->assertDontSee('Eligible order')
            ->assertDontSee('Select an order to prepare');
        $this->actingAs($this->admin)->get(route('marketplace.deliveries.prepare.select', ['order_id' => $order->id]))
            ->assertRedirect(route('marketplace.orders.deliveries.prepare', $order));

        $this->actingAs($this->admin)->get(route('marketplace.orders.deliveries.prepare', $order))
            ->assertOk()
            ->assertSee('Admin Prepared Product')
            ->assertSee('Customer')
            ->assertSee('Customer phone')
            ->assertSee('Customer email')
            ->assertSee('Order number')
            ->assertSee('Product')
            ->assertSee('Qty 1')
            ->assertSee('Delivery address')
            ->assertSee('Delivery Assignment')
            ->assertSee('Dispatch Documents and Notes')
            ->assertSee('Driver/Courier Phone Number')
            ->assertDontSee('Delivery Method')
            ->assertDontSee('name="delivery_method"', false)
            ->assertSee('Save & Prepare Delivery');

        $this->actingAs($this->admin)->post(route('marketplace.orders.deliveries.store', $order), [
            'expected_delivery_at' => now()->subDay()->toDateString(),
            'delivery_phone' => 'not a phone',
        ])->assertSessionHasErrors(['expected_delivery_at', 'delivery_phone']);
        $this->assertDatabaseCount('delivery_tasks', 0);

        $this->actingAs($this->admin)->post(route('marketplace.orders.deliveries.store', $order), [
            'courier_name' => 'In-house Driver',
            'delivery_phone' => '0788111222',
            'vehicle_registration' => 'RAA 123 A',
            'expected_delivery_at' => now()->addDay()->toDateString(),
            'tracking_reference' => 'ADMIN-TRACK-1',
            'delivery_notes' => 'Handle with care',
            'dispatch_proof' => UploadedFile::fake()->create('courier-ticket.pdf', 24, 'application/pdf'),
        ])->assertRedirect(route('marketplace.deliveries.index', ['status' => 'assigned']));

        $task = DeliveryTask::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame($item->id, $task->order_item_id);
        $this->assertNull($task->vendor_id);
        $this->assertSame('assigned', $task->status);
        $this->assertNull($task->delivery_method);
        $this->assertSame('In-house Driver', $task->courier_name);
        $this->assertSame('0788111222', $task->delivery_phone);
        $this->assertSame('RAA 123 A', $task->vehicle_registration);
        $this->assertSame('ADMIN-TRACK-1', $task->tracking_reference);
        $this->assertNotNull($task->dispatch_proof_path);
        Storage::disk('local')->assertExists($task->dispatch_proof_path);
        $this->assertSame('ready_for_dispatch', $item->fresh()->fulfillment_status);
        $this->assertSame('processing', $order->fresh()->fulfillment_status);
        $this->assertSame('Admin Prepared Product', $item->fresh()->product_name);
        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame('Kigali', $order->fresh()->delivery_address);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.show', $task))
            ->assertOk()
            ->assertSee('Preparation details')
            ->assertSee('ADMIN-TRACK-1')
            ->assertSee('Handle with care')
            ->assertSee(route('marketplace.deliveries.dispatch-proof', $task), false);
        $this->actingAs($this->admin)->get(route('marketplace.deliveries.dispatch-proof', $task))
            ->assertOk();
        $this->actingAs($this->admin)->post(route('marketplace.orders.deliveries.store', $order), [
            'courier_name' => 'In-house Driver',
            'delivery_phone' => '0788111222',
            'vehicle_registration' => 'RAA 123 A',
        ])->assertSessionHasErrors('delivery');
        $this->assertDatabaseCount('delivery_tasks', 1);
    }

    public function test_admin_prepare_form_saves_only_remaining_admin_items_from_mixed_order(): void
    {
        Storage::fake('local');
        $order = $this->order('ADMIN-PREPARE-MIXED', 'Mixed Order Customer', 'processing', 'paid');
        $order->update([
            'phone' => '+250780111222',
            'email' => 'mixed.customer@example.com',
            'delivery_address' => 'Saved Customer Address, Kigali',
        ]);
        $alreadyPreparedItem = $this->item($order, 'Already Prepared Product', null);
        $alreadyPreparedItem->update([
            'assignment_status' => 'admin_handled',
            'fulfillment_status' => 'ready_for_dispatch',
        ]);
        $remainingItem = $this->item($order, 'Remaining Admin Product', null);
        $remainingItem->update([
            'assignment_status' => 'admin_handled',
            'fulfillment_status' => 'ready_for_dispatch',
            'quantity' => 3,
        ]);
        $category = Shopcategory::query()->create([
            'name' => 'Delivery Test Category',
            'slug' => 'delivery-test-category',
            'active' => true,
        ]);
        $product = ShopProduct::query()->create([
            'product_name' => 'Remaining Admin Product',
            'slug' => 'remaining-admin-product',
            'status' => 'in_stock',
            'price' => 12000,
            'working_days' => 2,
            'shop_category_id' => $category->id,
            'product_type' => 'physical',
            'stock_unit' => 'pieces',
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);
        $remainingItem->update(['shop_product_id' => $product->id]);
        $vendorItem = $this->item($order, 'Vendor Product', $this->vendor);
        $vendorItem->update([
            'assignment_status' => 'accepted',
            'fulfillment_status' => 'ready_for_dispatch',
            'quantity' => 2,
        ]);
        $existingTask = DeliveryTask::query()->create([
            'reference' => 'DL-PREPARED-BEFORE',
            'order_id' => $order->id,
            'order_item_id' => $alreadyPreparedItem->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($this->admin)->get(route('marketplace.orders.deliveries.prepare', $order))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee('Remaining Admin Product')
            ->assertSee('Qty 3 pieces')
            ->assertSee('Delivery Test Category')
            ->assertSee('Physical')
            ->assertSee('3 pieces')
            ->assertSee('Saved Customer Address, Kigali')
            ->assertSee('Mixed Order Customer')
            ->assertSee('+250780111222')
            ->assertSee('Processing')
            ->assertDontSee('Already Prepared Product')
            ->assertDontSee('Vendor Product')
            ->assertSee(route('marketplace.orders.show', $order), false)
            ->assertSee('Cancel')
            ->assertSee('Save & Prepare Delivery')
            ->assertDontSee('Delivery Method')
            ->assertDontSee('name="delivery_method"', false)
            ->assertSee('name="courier_name"', false)
            ->assertSee('name="delivery_phone"', false)
            ->assertSee('name="vehicle_registration"', false)
            ->assertSee('name="expected_delivery_at"', false)
            ->assertSee('name="tracking_reference"', false)
            ->assertSee('name="delivery_notes"', false)
            ->assertSee('name="dispatch_proof"', false);

        $this->actingAs($this->admin)->post(route('marketplace.orders.deliveries.store', $order), [
            'dispatch_proof' => UploadedFile::fake()->create('invalid-ticket.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('dispatch_proof');
        $this->assertDatabaseCount('delivery_tasks', 1);

        $this->actingAs($this->admin)->post(route('marketplace.orders.deliveries.store', $order), [
            'tracking_reference' => 'PICKUP-REFERENCE-1',
            'delivery_notes' => 'Customer will collect the shipment.',
        ])->assertRedirect(route('marketplace.deliveries.index', ['status' => 'assigned']));

        $newTask = DeliveryTask::query()->where('order_item_id', $remainingItem->id)->firstOrFail();
        $this->assertSame('assigned', $newTask->status);
        $this->assertNull($newTask->delivery_method);
        $this->assertSame('PICKUP-REFERENCE-1', $newTask->tracking_reference);
        $this->assertSame($product->id, $newTask->item->shop_product_id);
        $this->assertSame($this->vendor->id, $vendorItem->fresh()->vendor_id);
        $this->assertSame(2, $vendorItem->fresh()->quantity);
        $this->assertSame('ready_for_dispatch', $vendorItem->fresh()->fulfillment_status);
        $this->assertSame('Saved Customer Address, Kigali', $order->fresh()->delivery_address);
        $this->assertSame(12000.0, (float) $order->fresh()->total);
        $this->assertDatabaseCount('delivery_tasks', 2);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.show', $newTask))
            ->assertOk()
            ->assertSee('Remaining Admin Product')
            ->assertSee('Customer will collect the shipment.')
            ->assertSee('PICKUP-REFERENCE-1');

        $this->actingAs($this->admin)->post(route('marketplace.orders.deliveries.store', $order), [
        ])->assertSessionHasErrors('delivery');
        $this->assertDatabaseCount('delivery_tasks', 2);
        $this->assertSame($existingTask->id, $existingTask->fresh()->id);
    }

    public function test_delivery_detail_resolves_reference_and_displays_only_its_linked_shipment(): void
    {
        $order = $this->order('ORDER-DELIVERY-DETAIL', 'Delivery Detail Customer', 'ready_for_dispatch', 'paid');
        $order->update(['delivery_address' => '25 Saved Customer Road, Kigali']);
        $unrelatedItem = $this->item($order, 'Unrelated Order Product', $this->vendor);
        $selectedItem = $this->item($order, 'Selected Shipment Product', $this->vendor);
        $selectedItem->update(['quantity' => 4]);

        DeliveryTask::query()->create([
            'reference' => 'DL-DETAIL-OTHER',
            'order_id' => $order->id,
            'order_item_id' => $unrelatedItem->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'assigned',
        ]);
        $selectedTask = DeliveryTask::query()->create([
            'reference' => 'DL-DETAIL-SELECTED',
            'order_id' => $order->id,
            'order_item_id' => $selectedItem->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'assigned',
            'delivery_method' => 'third_party_courier',
            'courier_name' => 'Selected Courier',
            'delivery_phone' => '0788000123',
            'vehicle_registration' => 'RAA 456 B',
            'expected_delivery_at' => '2026-10-29',
            'tracking_reference' => 'TRACK-SELECTED-1',
            'delivery_notes' => 'Selected delivery instructions',
            'dispatch_proof_path' => 'dispatch-proofs/selected-ticket.pdf',
            'proof_path' => 'delivery-proofs/selected-proof.pdf',
        ]);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index'))
            ->assertOk()
            ->assertSee(route('marketplace.deliveries.show', $selectedTask), false)
            ->assertSee('Selected Shipment Product × 4');

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.show', $selectedTask->reference))
            ->assertOk()
            ->assertSee($selectedTask->reference)
            ->assertSee($order->reference)
            ->assertSee('Selected Shipment Product')
            ->assertSee('Quantity')
            ->assertSee('4')
            ->assertSee('25 Saved Customer Road, Kigali')
            ->assertSee('Third-party Courier')
            ->assertSee('Selected Courier')
            ->assertSee('0788000123')
            ->assertSee('RAA 456 B')
            ->assertSee('Oct 29, 2026')
            ->assertSee('TRACK-SELECTED-1')
            ->assertSee('Selected delivery instructions')
            ->assertSee('Pending')
            ->assertSee('Dispatched')
            ->assertSee(route('marketplace.deliveries.dispatch-proof', $selectedTask), false)
            ->assertSee(route('marketplace.deliveries.proof.download', $selectedTask), false)
            ->assertDontSee('Unrelated Order Product');

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.show', 'DL-DOES-NOT-EXIST'))
            ->assertNotFound();
    }

    public function test_delivery_milestone_attempt_proof_and_reassignment_target_the_selected_task(): void
    {
        Storage::fake('local');
        $order = $this->order('ORDER-DELIVERY-ACTIONS', 'Delivery Actions Customer', 'ready_for_dispatch', 'paid');
        $firstItem = $this->item($order, 'First Shipment Product', $this->vendor);
        $secondItem = $this->item($order, 'Second Shipment Product', $this->vendor);
        $firstItem->update(['fulfillment_status' => 'ready_for_dispatch']);
        $secondItem->update(['fulfillment_status' => 'ready_for_dispatch']);
        $firstTask = DeliveryTask::query()->create([
            'reference' => 'DL-ACTION-FIRST',
            'order_id' => $order->id,
            'order_item_id' => $firstItem->id,
            'status' => 'assigned',
        ]);
        $selectedTask = DeliveryTask::query()->create([
            'reference' => 'DL-ACTION-SELECTED',
            'order_id' => $order->id,
            'order_item_id' => $secondItem->id,
            'status' => 'assigned',
        ]);
        $officer = User::factory()->create();

        $this->actingAs($this->admin)->post(route('marketplace.deliveries.update', $selectedTask), [
            'order_status' => 'dispatched',
            'customer_message' => 'Selected shipment dispatched',
            'internal_notes' => 'Selected shipment internal note',
        ])->assertRedirect();

        $this->assertSame('in_transit', $selectedTask->fresh()->status);
        $this->assertSame('Selected shipment dispatched', $selectedTask->fresh()->customer_message);
        $this->assertSame('Selected shipment internal note', $selectedTask->fresh()->internal_notes);
        $this->assertSame('assigned', $firstTask->fresh()->status);
        $this->assertSame('dispatched', $order->fresh()->fulfillment_status);

        $this->actingAs($this->admin)->post(route('marketplace.deliveries.attempt', $selectedTask), [
            'result' => 'failed',
            'reason' => 'Customer unavailable',
        ])->assertRedirect();
        $this->assertDatabaseHas('delivery_attempts', [
            'delivery_task_id' => $selectedTask->id,
            'result' => 'failed',
            'reason' => 'Customer unavailable',
        ]);
        $this->assertDatabaseMissing('delivery_attempts', [
            'delivery_task_id' => $firstTask->id,
        ]);

        $this->actingAs($this->admin)->post(route('marketplace.deliveries.proof', $selectedTask), [
            'proof' => UploadedFile::fake()->create('delivery-proof.pdf', 24, 'application/pdf'),
        ])->assertRedirect();
        $this->assertNotNull($selectedTask->fresh()->proof_path);
        Storage::disk('local')->assertExists($selectedTask->fresh()->proof_path);
        $this->assertNull($firstTask->fresh()->proof_path);

        $this->actingAs($this->admin)->post(route('marketplace.deliveries.reassign', $selectedTask), [
            'delivery_officer_id' => $officer->id,
        ])->assertRedirect();
        $this->assertSame($officer->id, $selectedTask->fresh()->delivery_officer_id);
        $this->assertNull($firstTask->fresh()->delivery_officer_id);
        $this->assertNull($order->fresh()->delivery_officer_id);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.show', $selectedTask))
            ->assertOk()
            ->assertSee('In Transit')
            ->assertSee('Customer unavailable')
            ->assertSee('Selected shipment internal note')
            ->assertSee($officer->name)
            ->assertSee(route('marketplace.deliveries.proof.download', $selectedTask), false);

        $this->actingAs($this->admin)->get(route('marketplace.deliveries.index', ['status' => 'in_transit']))
            ->assertOk()
            ->assertSee($selectedTask->reference)
            ->assertDontSee($firstTask->reference);
    }

    public function test_order_pagination_preserves_the_search_and_selected_tab(): void
    {
        for ($number = 1; $number <= 21; $number++) {
            $this->order(sprintf('PAGED-ORDER-%02d', $number), 'Paged Customer', 'pending_confirmation', 'pending');
        }

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'pending', 'q' => 'PAGED']))
            ->assertOk()
            ->assertSee('PAGED-ORDER-21')
            ->assertDontSee('PAGED-ORDER-01')
            ->assertSee(str_replace('&', '&amp;', route('marketplace.orders.index', ['tab' => 'pending', 'q' => 'PAGED', 'page' => 2])), false);

        $this->actingAs($this->admin)->get(route('marketplace.orders.index', ['tab' => 'pending', 'q' => 'PAGED', 'page' => 2]))
            ->assertOk()
            ->assertSee('PAGED-ORDER-01')
            ->assertDontSee('PAGED-ORDER-21');
    }

    public function test_admin_can_mark_an_in_transit_delivery_delivered(): void
    {
        $order = $this->order('ADMIN-MARK-DELIVERED', 'Admin Completion Customer', 'processing', 'paid');
        $item = $this->item($order, 'Admin Completion Product', $this->vendor);
        $task = DeliveryTask::query()->create([
            'reference' => 'ADMIN-MARK-DELIVERED-TASK',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'in_transit',
        ]);

        $this->actingAs($this->admin)
            ->get(route('marketplace.deliveries.index', ['status' => 'in_transit']))
            ->assertOk()
            ->assertSee('Mark as Delivered')
            ->assertSee(route('marketplace.deliveries.delivered', $task), false);

        $this->actingAs($this->admin)
            ->postJson(route('marketplace.deliveries.delivered', $task))
            ->assertOk()
            ->assertJsonPath('status', 'delivered')
            ->assertJsonPath('message', 'Delivery marked as Delivered successfully.');
        $this->assertSame('delivered', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->delivered_at);
        $this->assertSame('delivered', $item->fresh()->fulfillment_status);

        $this->actingAs($this->admin)
            ->get(route('marketplace.deliveries.index', ['status' => 'in_transit']))
            ->assertOk()
            ->assertDontSee($task->reference);
        $this->actingAs($this->admin)
            ->postJson(route('marketplace.deliveries.delivered', $task))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('delivery');
    }

    private function order(string $reference, string $customerName, string $status, string $paymentStatus): Order
    {
        return Order::query()->create([
            'reference' => $reference,
            'customer_name' => $customerName,
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'total' => 12000,
            'subtotal' => 12000,
            'fulfillment_status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }

    private function item(Order $order, string $name, ?Vendor $vendor): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'vendor_id' => $vendor?->id,
            'product_name' => $name,
            'unit_price' => 12000,
            'quantity' => 1,
            'line_total' => 12000,
            'assignment_status' => $vendor ? 'assigned' : 'unassigned',
        ]);
    }
}
