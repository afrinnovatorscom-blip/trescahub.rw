<?php

namespace Tests\Feature;

use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorOrdersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_orders_use_admin_pending_and_paid_classification_while_scoping_vendor_items(): void
    {
        $vendorA = $this->vendor('Vendor Orders A');
        $vendorB = $this->vendor('Vendor Orders B');
        $userA = $this->vendorUser($vendorA);
        $sameBusinessUser = $this->vendorUser($vendorA);
        $userB = $this->vendorUser($vendorB);

        $pendingOrder = $this->order('VENDOR-PENDING-ORDER', 'Pending Customer');
        $pendingOrder->update(['fulfillment_status' => 'pending_confirmation']);
        $this->item($pendingOrder, $vendorA, 'Assigned Tool', 'pending_confirmation', 'assigned');

        $mixedOrder = $this->order('VENDOR-MIXED-WORK', 'Mixed Customer');
        $mixedItem = $this->item($mixedOrder, $vendorA, 'Vendor A Drill', 'processing', 'accepted');
        $this->item($mixedOrder, $vendorB, 'Vendor B Saw', 'pending_confirmation', 'assigned');
        DeliveryTask::query()->create([
            'reference' => 'VENDOR-A-DELIVERY',
            'order_id' => $mixedOrder->id,
            'order_item_id' => $mixedItem->id,
            'vendor_id' => $vendorA->id,
            'status' => 'in_transit',
        ]);
        DeliveryTask::query()->create([
            'reference' => 'VENDOR-B-DELIVERY',
            'order_id' => $mixedOrder->id,
            'vendor_id' => $vendorB->id,
            'status' => 'assigned',
        ]);

        $pendingPaymentOrder = $this->order('VENDOR-PENDING-PAYMENT', 'Pending Payment Customer');
        $this->item($pendingPaymentOrder, $vendorA, 'Pending Payment Item', 'processing', 'accepted');

        $completedOrder = $this->order('VENDOR-COMPLETED', 'Completed Customer');
        $completedOrder->update(['fulfillment_status' => 'completed', 'payment_status' => 'paid']);
        $this->item($completedOrder, $vendorA, 'Delivered Item', 'delivered', 'accepted');

        $paidOrder = $this->order('VENDOR-PAID-ORDER', 'Paid Customer');
        $paidOrder->update(['fulfillment_status' => 'processing', 'payment_status' => 'paid']);
        $paidItem = $this->item($paidOrder, $vendorA, 'Paid Vendor Product', 'ready_for_dispatch', 'accepted');
        $this->item($paidOrder, $vendorB, 'Private Vendor B Product', 'processing', 'accepted');

        $otherOrder = $this->order('OTHER-VENDOR-ORDER', 'Other Vendor Customer');
        $otherOrder->update(['fulfillment_status' => 'processing', 'payment_status' => 'paid']);
        $this->item($otherOrder, $vendorB, 'Private Vendor B Item', 'processing', 'accepted');

        $pendingList = $this->actingAs($userA)->get(route('vendor.orders.index', ['tab' => 'pending']));
        $pendingList->assertOk()
            ->assertSee('Orders')
            ->assertSee('Pending Orders')
            ->assertSee('Paid Orders')
            ->assertSee('orders-table')
            ->assertSee('flex-wrap: nowrap', false)
            ->assertSee('overflow-x: auto', false)
            ->assertSee('Vendor subtotal')
            ->assertSee('VENDOR-PENDING-ORDER')
            ->assertDontSee('VENDOR-MIXED-WORK')
            ->assertSee('VENDOR-PENDING-PAYMENT')
            ->assertDontSee('VENDOR-PAID-ORDER')
            ->assertDontSee('Record Payment')
            ->assertDontSee('OTHER-VENDOR-ORDER')
            ->assertDontSee('Private Vendor B Item')
            ->assertDontSee('VENDOR-B-DELIVERY');
        $this->assertSame(['pending' => 2, 'paid' => 1], $pendingList->viewData('tabCounts'));

        $paidList = $this->actingAs($userA)->get(route('vendor.orders.index', ['tab' => 'paid']));
        $paidList->assertOk()
            ->assertSee('VENDOR-PAID-ORDER')
            ->assertSee('Paid Vendor Product')
            ->assertDontSee('Private Vendor B Product')
            ->assertDontSee('VENDOR-COMPLETED')
            ->assertDontSee('OTHER-VENDOR-ORDER')
            ->assertSee('Prepare Delivery')
            ->assertDontSee('Record Payment');
        $this->assertSame(['pending' => 2, 'paid' => 1], $paidList->viewData('tabCounts'));
        $this->assertSame(['pending', 'paid'], array_keys($paidList->viewData('tabCounts')));
        $this->actingAs($userA)->post(route('marketplace.orders.save-changes', $paidOrder), [])
            ->assertForbidden();

        $this->actingAs($sameBusinessUser)->get(route('vendor.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee('VENDOR-PAID-ORDER');

        $this->actingAs($userA)->get(route('vendor.orders.show', $mixedOrder->reference))
            ->assertOk()
            ->assertSee('Vendor A Drill')
            ->assertDontSee('Vendor B Saw')
            ->assertSee('VENDOR-A-DELIVERY')
            ->assertDontSee('VENDOR-B-DELIVERY');

        $this->actingAs($userA)->get(route('vendor.orders.show', $otherOrder->reference))->assertNotFound();
        $this->actingAs($userB)->get(route('vendor.orders.show', $mixedOrder->reference))
            ->assertOk()
            ->assertSee('Vendor B Saw')
            ->assertDontSee('Vendor A Drill');

        $this->assertSame($vendorA->id, $paidItem->fresh()->vendor_id);
    }

    public function test_vendor_search_status_filter_pagination_and_empty_state_are_scoped(): void
    {
        $vendor = $this->vendor('Search Vendor');
        $user = $this->vendorUser($vendor);
        $matchingOrder = $this->order('SEARCH-ORDER-01', 'Search Customer');
        $matchingOrder->update(['fulfillment_status' => 'processing']);
        $this->item($matchingOrder, $vendor, 'Search Drill', 'processing', 'accepted');
        $otherOrder = $this->order('SEARCH-ORDER-02', 'Other Search Customer');
        $this->item($otherOrder, $vendor, 'Other Product', 'pending_confirmation', 'assigned');

        $this->actingAs($user)->get(route('vendor.orders.index', [
            'tab' => 'pending',
            'q' => 'Search Drill',
            'status' => 'processing',
        ]))
            ->assertOk()
            ->assertSee('SEARCH-ORDER-01')
            ->assertDontSee('SEARCH-ORDER-02');

        $this->actingAs($user)->get(route('vendor.orders.index', [
            'tab' => 'pending',
            'status' => 'ready_for_dispatch',
        ]))
            ->assertOk()
            ->assertSee('No orders found')
            ->assertSee('Clear filters');

        for ($number = 1; $number <= 21; $number++) {
            $order = $this->order(sprintf('PAGE-VENDOR-%02d', $number), 'Pagination Customer');
            $this->item($order, $vendor, 'Pagination Item', 'processing', 'accepted');
        }

        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'pending', 'q' => 'PAGE-VENDOR']))
            ->assertOk()
            ->assertSee('PAGE-VENDOR-21')
            ->assertDontSee('PAGE-VENDOR-01');
        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'pending', 'q' => 'PAGE-VENDOR', 'page' => 2]))
            ->assertOk()
            ->assertSee('PAGE-VENDOR-01')
            ->assertDontSee('PAGE-VENDOR-21');

        $emptyVendor = $this->vendor('Empty Orders Vendor');
        $this->actingAs($this->vendorUser($emptyVendor))->get(route('vendor.orders.index'))
            ->assertOk()
            ->assertSee('No orders found')
            ->assertSee('data-tab-count="pending">0', false);
    }

    public function test_order_details_offer_only_allowed_vendor_transitions_and_preserve_server_validation(): void
    {
        $vendor = $this->vendor('Workflow Vendor');
        $user = $this->vendorUser($vendor);
        $order = $this->order('WORKFLOW-ORDER', 'Workflow Customer');
        $item = $this->item($order, $vendor, 'Workflow Product', 'pending_confirmation', 'assigned');

        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('Accept Order')
            ->assertSee('onclick="return confirm(', false);

        $this->actingAs($user)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Accept Assignment')
            ->assertSee('onsubmit="if (! confirm(', false)
            ->assertSee('Report a Problem')
            ->assertDontSee('Ready for Delivery');

        $this->actingAs($user)->post(route('vendor.orders.progress', $item), [
            'fulfillment_status' => 'processing',
        ])->assertSessionHasErrors('fulfillment_status');

        $this->actingAs($user)->post(route('vendor.orders.progress', $item), [
            'fulfillment_status' => 'delivered',
        ])->assertSessionHasErrors('fulfillment_status');
        $this->assertSame('pending_confirmation', $item->fresh()->fulfillment_status);

        $this->actingAs($this->vendorUser($this->vendor('Different Acceptance Vendor')))
            ->post(route('vendor.orders.respond', $item), [
                'decision' => 'accept',
            ])->assertNotFound();
        $this->assertSame('assigned', $item->fresh()->assignment_status);

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'accept',
        ])->assertRedirect(route('vendor.orders.index', ['tab' => 'pending']))
            ->assertSessionHas('success', 'Your assignment was accepted.');
        $this->assertSame('accepted', $item->fresh()->assignment_status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('WORKFLOW-ORDER')
            ->assertDontSee('Accept Order');

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'accept',
        ])->assertSessionHasErrors('decision');

        $this->actingAs($user)->post(route('vendor.orders.progress', $item), [
            'fulfillment_status' => 'processing',
        ])->assertRedirect();
        $this->assertSame('processing', $item->fresh()->fulfillment_status);

        $this->actingAs($user)->post(route('vendor.orders.progress', $item), [
            'fulfillment_status' => 'ready_for_dispatch',
        ])->assertSessionHasErrors('fulfillment_status');
        $this->assertSame('processing', $item->fresh()->fulfillment_status);

        $this->actingAs($user)->post(route('vendor.orders.ready-for-delivery', $order->reference))
            ->assertRedirect();
        $this->assertSame('ready_for_dispatch', $item->fresh()->fulfillment_status);
        $this->assertSame('processing', $order->fresh()->fulfillment_status);
        $this->assertNotSame('delivered', $order->fresh()->fulfillment_status);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $item->getMorphClass(),
            'auditable_id' => $item->id,
            'action' => 'order.item_progress',
        ]);

        $paidOrder = $this->order('PAID-ASSIGNMENT-ACCEPT', 'Paid Assignment Customer');
        $paidOrder->update(['payment_status' => 'paid']);
        $paidItem = $this->item($paidOrder, $vendor, 'Paid Assignment Product', 'processing', 'assigned');
        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee('PAID-ASSIGNMENT-ACCEPT')
            ->assertSee('Accept Order');

        $this->actingAs($user)->post(route('vendor.orders.respond', $paidItem), [
            'decision' => 'accept',
        ])->assertRedirect(route('vendor.orders.index', ['tab' => 'paid']));
        $this->assertSame('accepted', $paidItem->fresh()->assignment_status);
        $this->assertSame('paid', $paidOrder->fresh()->payment_status);
        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee('PAID-ASSIGNMENT-ACCEPT')
            ->assertDontSee('Accept Order');

        $cancelledWorkflowOrder = $this->order('CANCELLED-ASSIGNMENT-ACCEPT', 'Cancellation Customer');
        $cancelledWorkflowOrder->update(['fulfillment_status' => 'cancellation_requested']);
        $cancelledWorkflowItem = $this->item(
            $cancelledWorkflowOrder,
            $vendor,
            'Cancellation Product',
            'processing',
            'assigned'
        );
        $this->actingAs($user)->post(route('vendor.orders.respond', $cancelledWorkflowItem), [
            'decision' => 'accept',
        ])->assertSessionHasErrors('decision');
        $this->assertSame('assigned', $cancelledWorkflowItem->fresh()->assignment_status);
    }

    public function test_vendor_readiness_requires_every_assigned_product_prepared_and_does_not_change_other_vendor_items(): void
    {
        $vendorA = $this->vendor('Readiness Vendor A');
        $vendorB = $this->vendor('Readiness Vendor B');
        $userA = $this->vendorUser($vendorA);
        $userB = $this->vendorUser($vendorB);
        $order = $this->order('GROUP-READINESS-ORDER', 'Group Readiness Customer');
        $preparedA = $this->item($order, $vendorA, 'Prepared A', 'processing', 'accepted');
        $unpreparedA = $this->item($order, $vendorA, 'Unprepared A', 'confirmed', 'accepted');
        $otherVendorItem = $this->item($order, $vendorB, 'Prepared B', 'processing', 'accepted');

        $this->actingAs($userA)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertDontSee('Ready for Delivery');

        $this->actingAs($userA)->post(route('vendor.orders.ready-for-delivery', $order->reference))
            ->assertSessionHasErrors('fulfillment_status');
        $this->assertSame('processing', $preparedA->fresh()->fulfillment_status);
        $this->assertSame('confirmed', $unpreparedA->fresh()->fulfillment_status);

        $unpreparedA->update(['fulfillment_status' => 'processing']);
        $this->actingAs($userA)->post(route('vendor.orders.ready-for-delivery', $order->reference))
            ->assertRedirect();
        $this->assertSame('ready_for_dispatch', $preparedA->fresh()->fulfillment_status);
        $this->assertSame('ready_for_dispatch', $unpreparedA->fresh()->fulfillment_status);
        $this->assertSame('processing', $otherVendorItem->fresh()->fulfillment_status);
        $this->assertNotSame('delivered', $order->fresh()->fulfillment_status);
        $this->assertNotSame('completed', $order->fresh()->fulfillment_status);

        $this->actingAs($userB)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Prepared B')
            ->assertSee('Ready for Delivery')
            ->assertSee('Processing');
    }

    public function test_vendor_can_create_a_delivery_for_only_its_ready_assignment_and_repeat_safely(): void
    {
        Storage::fake('local');
        $vendor = $this->vendor('Create Delivery Vendor');
        $otherVendor = $this->vendor('Other Create Delivery Vendor');
        $user = $this->vendorUser($vendor);
        $order = $this->order('CREATE-VENDOR-DELIVERY', 'Delivery Confirmation Customer');
        $order->update([
            'email' => 'customer@example.com',
            'delivery_address' => '14 Main Street, Kigali',
            'delivery_notes' => 'Call on arrival',
            'payment_status' => 'paid',
        ]);
        $firstItem = $this->item($order, $vendor, 'Vendor Product One', 'ready_for_dispatch', 'accepted');
        $firstItem->update(['sku' => 'VD-001', 'quantity' => 3]);
        $secondItem = $this->item($order, $vendor, 'Vendor Product Two', 'ready_for_dispatch', 'accepted');
        $otherItem = $this->item($order, $otherVendor, 'Other Vendor Product', 'ready_for_dispatch', 'accepted');

        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertSee('Prepare Delivery')
            ->assertSee('CREATE-VENDOR-DELIVERY');

        $this->actingAs($user)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Prepare Delivery')
            ->assertSee('VD-001')
            ->assertSee('Vendor Product Two')
            ->assertDontSee('Other Vendor Product');

        $this->actingAs($user)->get(route('vendor.orders.deliveries.prepare', $order->reference))
            ->assertOk()
            ->assertSee('14 Main Street, Kigali')
            ->assertSee('Order number')
            ->assertSee('Customer')
            ->assertSee('Customer phone')
            ->assertSee('Customer email')
            ->assertSee('Products and quantities')
            ->assertSee('Vendor Product One')
            ->assertSee('Qty 3')
            ->assertSee('Delivery address')
            ->assertSee('Delivery Assignment')
            ->assertSee('Dispatch Documents and Notes')
            ->assertSee('dispatch_proof')
            ->assertDontSee('Delivery Method')
            ->assertDontSee('name="delivery_method"', false);

        $this->actingAs($user)->post(route('vendor.orders.deliveries.store', $order->reference), [
            'courier_name' => 'Test Courier',
            'delivery_phone' => 'not a phone',
            'expected_delivery_at' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors(['delivery_phone', 'expected_delivery_at']);
        $this->actingAs($user)->post(route('vendor.orders.deliveries.store', $order->reference), [
            'courier_name' => 'Test Courier',
            'delivery_phone' => '0788000004',
            'dispatch_proof' => UploadedFile::fake()->create('dispatch-script.exe', 24, 'application/octet-stream'),
        ])->assertSessionHasErrors('dispatch_proof');
        $this->assertDatabaseCount('delivery_tasks', 0);

        $this->actingAs($user)->post(route('vendor.orders.deliveries.store', $order->reference), [
            'courier_name' => 'Test Courier',
            'delivery_phone' => '0788000004',
            'tracking_reference' => 'VENDOR-TRACK-1',
            'vendor_id' => $otherVendor->id,
            'quantity' => 999,
            'product_name' => 'Forged Product',
            'dispatch_proof' => UploadedFile::fake()->create('dispatch-proof.pdf', 24, 'application/pdf'),
        ])->assertRedirect(route('vendor.deliveries.index', ['status' => 'assigned']));
        $this->assertSame('ready_for_dispatch', $firstItem->fresh()->fulfillment_status);

        $firstItem->refresh();
        $secondItem->refresh();
        $this->assertSame('ready_for_dispatch', $firstItem->fulfillment_status);
        $this->assertSame('ready_for_dispatch', $secondItem->fulfillment_status);
        $this->assertSame('ready_for_dispatch', $otherItem->fresh()->fulfillment_status);
        $this->assertSame('confirmed', $order->fresh()->fulfillment_status);
        $this->assertDatabaseCount('delivery_tasks', 2);

        $tasks = DeliveryTask::query()->where('order_id', $order->id)->where('vendor_id', $vendor->id)->get();
        $this->assertCount(2, $tasks);
        $this->assertSame(
            [$firstItem->id, $secondItem->id],
            $tasks->pluck('order_item_id')->sort()->values()->all()
        );
        $this->assertSame([null], $tasks->pluck('delivery_method')->unique()->values()->all());
        $this->assertSame(['Test Courier'], $tasks->pluck('courier_name')->unique()->values()->all());
        $this->assertSame(['VENDOR-TRACK-1'], $tasks->pluck('tracking_reference')->unique()->values()->all());
        $this->assertNotNull($tasks->first()->dispatch_proof_path);
        Storage::disk('local')->assertExists($tasks->first()->dispatch_proof_path);
        $task = $tasks->first();
        $this->actingAs($user)->get(route('vendor.deliveries.index'))
            ->assertOk()
            ->assertSee(route('vendor.deliveries.prepare.existing', $task->reference), false)
            ->assertSee('Prepare Delivery')
            ->assertDontSee('View Details');
        $this->actingAs($user)->get(route('vendor.deliveries.prepare.existing', $task->reference))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee('value="Test Courier"', false)
            ->assertSee('value="VENDOR-TRACK-1"', false)
            ->assertSee(route('vendor.deliveries.show', $task->reference), false)
            ->assertSee('Save Changes')
            ->assertSee('Saving a Pending delivery moves it to In Transit.')
            ->assertDontSee('name="delivery_method"', false);

        $this->actingAs($otherVendorUser = $this->vendorUser($otherVendor))
            ->get(route('vendor.deliveries.prepare.existing', $task->reference))
            ->assertNotFound();
        $this->actingAs($otherVendorUser)->post(route('vendor.deliveries.prepare.update', $task->reference), [
            'courier_name' => 'Unauthorized Courier',
            'delivery_phone' => '0788000005',
        ])->assertNotFound();

        $this->actingAs($user)->post(route('vendor.deliveries.prepare.update', $task->reference), [
            'courier_name' => 'Invalid Courier',
            'delivery_phone' => 'not a phone',
            'tracking_reference' => 'INVALID-UPDATE',
        ])->assertSessionHasErrors('delivery_phone');
        $this->assertSame('assigned', $task->fresh()->status);
        $this->assertSame('Test Courier', $task->fresh()->courier_name);
        $this->assertSame('VENDOR-TRACK-1', $task->fresh()->tracking_reference);

        $this->actingAs($user)->post(route('vendor.deliveries.prepare.update', $task->reference), [
            'courier_name' => 'Updated Vendor Courier',
            'delivery_phone' => '+250 788 111 222',
            'vehicle_registration' => 'RAA 222 C',
            'expected_delivery_at' => now()->addDays(4)->toDateString(),
            'tracking_reference' => 'VENDOR-TRACK-UPDATED',
            'delivery_notes' => 'Updated vendor dispatch notes',
        ])->assertRedirect(route('vendor.deliveries.index', ['status' => 'in_transit']))
            ->assertSessionHas('success', 'Delivery information saved and delivery moved to In Transit.');
        $task->refresh();
        $this->assertSame('in_transit', $task->status);
        $this->assertNotNull($task->dispatched_at);
        $this->assertSame($vendor->id, $task->vendor_id);
        $this->assertSame($firstItem->id, $task->order_item_id);
        $this->assertSame('Updated Vendor Courier', $task->courier_name);
        $this->assertSame('VENDOR-TRACK-UPDATED', $task->tracking_reference);
        $this->assertSame('Updated vendor dispatch notes', $task->delivery_notes);
        $this->assertDatabaseCount('delivery_tasks', 2);
        $this->actingAs($user)->get(route('vendor.deliveries.index', ['status' => 'in_transit']))
            ->assertOk()
            ->assertSee($task->reference)
            ->assertSee('Delivery information saved and delivery moved to In Transit.')
            ->assertDontSee('View Details');
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => $task->getMorphClass(),
            'auditable_id' => $task->id,
            'action' => 'delivery.dispatched',
        ]);

        $this->actingAs($user)->get(route('vendor.deliveries.show', $tasks->first()->reference))
            ->assertOk()
            ->assertSee('Preparation details')
            ->assertSee('VENDOR-TRACK-UPDATED');
        $this->actingAs($user)->get(route('vendor.deliveries.dispatch-proof', $tasks->first()->reference))
            ->assertOk();
        $this->actingAs($otherVendorUser)->get(route('vendor.deliveries.dispatch-proof', $tasks->first()->reference))
            ->assertNotFound();
        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'paid']))
            ->assertOk()
            ->assertDontSee('CREATE-VENDOR-DELIVERY');
        $this->actingAs($user)->get(route('vendor.deliveries.index'))
            ->assertOk()
            ->assertDontSee($tasks->first()->reference);
        $this->actingAs($user)->get(route('vendor.deliveries.index', ['status' => 'in_transit']))
            ->assertOk()
            ->assertSee($tasks->first()->reference)
            ->assertDontSee('View Details');

        $this->actingAs($user)->post(route('vendor.orders.deliveries.store', $order->reference), [
            'courier_name' => 'Test Courier',
            'delivery_phone' => '0788000004',
        ])->assertSessionHasErrors('delivery');
        $this->assertDatabaseCount('delivery_tasks', 2);

        $unassignedVendor = $this->vendor('Unassigned Delivery Vendor');
        $otherUser = $this->vendorUser($unassignedVendor);
        $this->actingAs($otherUser)->post(route('vendor.orders.deliveries.store', $order->reference), [
            'courier_name' => 'Other Vendor Courier',
            'delivery_phone' => '0788000005',
        ])->assertNotFound();

        $adminOnlyOrder = $this->order('ADMIN-ONLY-DELIVERY-ORDER', 'Admin Delivery Customer');
        OrderItem::query()->create([
            'order_id' => $adminOnlyOrder->id,
            'product_name' => 'Admin Managed Product',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
            'fulfillment_status' => 'ready_for_dispatch',
            'assignment_status' => 'admin_handled',
        ]);
        $this->actingAs($user)->get(route('vendor.orders.deliveries.prepare', $adminOnlyOrder->reference))
            ->assertNotFound();
        $this->actingAs($user)->post(route('vendor.orders.deliveries.store', $adminOnlyOrder->reference), [
            'courier_name' => 'Unauthorized Courier',
            'delivery_phone' => '0788000006',
        ])->assertNotFound();

        $adminOnlyItem = $adminOnlyOrder->items()->firstOrFail();
        $adminTask = DeliveryTask::query()->create([
            'reference' => 'ADMIN-OWNED-VENDOR-TEST',
            'order_id' => $adminOnlyOrder->id,
            'order_item_id' => $adminOnlyItem->id,
            'vendor_id' => null,
            'status' => 'assigned',
            'courier_name' => 'Admin Courier',
        ]);
        $this->actingAs($user)->get(route('vendor.deliveries.prepare.existing', $adminTask->reference))
            ->assertNotFound();
        $this->actingAs($user)->post(route('vendor.deliveries.prepare.update', $adminTask->reference), [
            'courier_name' => 'Attempted Vendor Change',
            'delivery_phone' => '0788000007',
        ])->assertNotFound();
        $this->assertSame('Admin Courier', $adminTask->fresh()->courier_name);

        $completedVendorItem = $this->item($adminOnlyOrder, $vendor, 'Completed Vendor Product', 'delivered', 'accepted');
        $completedVendorTask = DeliveryTask::query()->create([
            'reference' => 'VENDOR-COMPLETED-TEST',
            'order_id' => $adminOnlyOrder->id,
            'order_item_id' => $completedVendorItem->id,
            'vendor_id' => $vendor->id,
            'status' => 'delivered',
            'courier_name' => 'Completed Vendor Courier',
        ]);
        $this->actingAs($user)->post(route('vendor.deliveries.prepare.update', $completedVendorTask->reference), [
            'courier_name' => 'Attempted Completed Update',
            'delivery_phone' => '0788000008',
        ])->assertNotFound();
        $this->assertSame('delivered', $completedVendorTask->fresh()->status);
        $this->assertSame('Completed Vendor Courier', $completedVendorTask->fresh()->courier_name);
    }

    public function test_reported_problem_blocks_readiness_and_cannot_be_cleared_by_vendor_acceptance(): void
    {
        $vendor = $this->vendor('Readiness Problem Vendor');
        $user = $this->vendorUser($vendor);
        $order = $this->order('READINESS-PROBLEM-ORDER', 'Readiness Problem Customer');
        $item = $this->item($order, $vendor, 'Problem Item', 'processing', 'accepted');

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'problem',
            'note' => 'Stock count does not match.',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'accept',
        ])->assertSessionHasErrors('decision');
        $this->actingAs($user)->post(route('vendor.orders.ready-for-delivery', $order->reference))
            ->assertSessionHasErrors('fulfillment_status');

        $this->assertSame('clarification', $item->fresh()->assignment_status);
        $this->assertSame('processing', $item->fresh()->fulfillment_status);
    }

    public function test_vendor_problem_report_requires_a_reason_and_notifies_admins(): void
    {
        $vendor = $this->vendor('Problem Report Vendor');
        $user = $this->vendorUser($vendor);
        $order = $this->order('PROBLEM-REPORT-ORDER', 'Problem Report Customer');
        $item = $this->item($order, $vendor, 'Damaged Item', 'processing', 'accepted');
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');
        $administrator = $administrator->fresh();

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'problem',
        ])->assertSessionHasErrors('note');
        $this->assertSame('accepted', $item->fresh()->assignment_status);

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'problem',
            'note' => 'The item arrived damaged.',
        ])->assertRedirect();

        $this->assertSame('clarification', $item->fresh()->assignment_status);
        $this->assertStringContainsString('The item arrived damaged.', $item->fresh()->internal_notes);
        $this->assertSame(
            'Vendor reported an order problem',
            $administrator->notifications()->latest()->first()?->data['title']
        );

        $this->actingAs($user)->get(route('vendor.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee('PROBLEM-REPORT-ORDER');
    }

    public function test_vendor_cannot_accept_an_assignment_before_admin_accepts_the_customer_order(): void
    {
        $vendor = $this->vendor('Pending Review Vendor');
        $user = $this->vendorUser($vendor);
        $order = $this->order('PENDING-ADMIN-REVIEW', 'Pending Review Customer');
        $order->update(['fulfillment_status' => 'pending_confirmation']);
        $item = $this->item($order, $vendor, 'Pending Review Item', 'pending_confirmation', 'assigned');

        $this->actingAs($user)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('awaiting administrator review')
            ->assertDontSee('Accept Assignment')
            ->assertDontSee('Report a Problem');

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'accept',
        ])->assertSessionHasErrors('decision');

        $this->assertSame('pending_confirmation', $order->fresh()->fulfillment_status);
        $this->assertSame('assigned', $item->fresh()->assignment_status);
    }

    public function test_completed_vendor_assignments_are_read_only_in_the_ui_and_backend(): void
    {
        $vendor = $this->vendor('Completed Workflow Vendor');
        $user = $this->vendorUser($vendor);
        $order = $this->order('COMPLETED-WORKFLOW-ORDER', 'Completed Workflow Customer');
        $item = $this->item($order, $vendor, 'Completed Item', 'delivered', 'accepted');

        $this->actingAs($user)->get(route('vendor.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Completed:')
            ->assertSee('available for reference only')
            ->assertDontSee('Accept Assignment')
            ->assertDontSee('Report a Problem')
            ->assertDontSee('Ready for Delivery');

        $this->actingAs($user)->post(route('vendor.orders.respond', $item), [
            'decision' => 'problem',
            'note' => 'Late issue report.',
        ])->assertSessionHasErrors('decision');

        $this->actingAs($user)->post(route('vendor.orders.progress', $item), [
            'fulfillment_status' => 'completed',
        ])->assertSessionHasErrors('fulfillment_status');

        $this->assertSame('delivered', $item->fresh()->fulfillment_status);
        $this->assertSame('accepted', $item->fresh()->assignment_status);
        $this->assertNull($item->fresh()->internal_notes);
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

    private function order(string $reference, string $customerName): Order
    {
        return Order::query()->create([
            'reference' => $reference,
            'customer_name' => $customerName,
            'phone' => '0788111222',
            'delivery_address' => 'Kigali',
            'total' => 30000,
            'subtotal' => 30000,
            'fulfillment_status' => 'confirmed',
        ]);
    }

    private function item(Order $order, Vendor $vendor, string $name, string $status, string $assignment): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'product_name' => $name,
            'unit_price' => 10000,
            'quantity' => 2,
            'line_total' => 20000,
            'fulfillment_status' => $status,
            'assignment_status' => $assignment,
        ]);
    }
}
