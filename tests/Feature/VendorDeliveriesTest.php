<?php

namespace Tests\Feature;

use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorDeliveriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_delivery_list_and_details_are_limited_to_its_allocated_items(): void
    {
        $vendorA = $this->vendor('Delivery Vendor A');
        $vendorB = $this->vendor('Delivery Vendor B');
        $userA = $this->vendorUser($vendorA);
        $userB = $this->vendorUser($vendorB);

        $orderA = $this->order('TH-DELIVERY-A', 'Customer A');
        $itemA = $this->item($orderA, $vendorA, 'Vendor A Item');
        $orderB = $this->order('TH-DELIVERY-B', 'Customer B');
        $itemB = $this->item($orderB, $vendorB, 'Vendor B Item');

        $assigned = $this->task('DLV-ASSIGNED-A', $orderA, $itemA, $vendorA, 'assigned');
        $inTransit = $this->task('DLV-TRANSIT-A', $orderA, $itemA, $vendorB, 'in_transit');
        $delivered = $this->task('DLV-DELIVERED-A', $orderA, null, $vendorA, 'delivered');
        $otherVendorTask = $this->task('DLV-PRIVATE-B', $orderB, $itemB, $vendorB, 'assigned');

        $this->actingAs($userA)->get(route('vendor.deliveries.index'))
            ->assertOk()
            ->assertSee('DLV-ASSIGNED-A')
            ->assertDontSee('DLV-TRANSIT-A')
            ->assertDontSee('DLV-DELIVERED-A')
            ->assertDontSee('DLV-PRIVATE-B')
            ->assertDontSee('Vendor B Item')
            ->assertDontSee('Customer A')
            ->assertDontSee('Private delivery address')
            ->assertDontSee('<th>Vendor</th>', false)
            ->assertSee('Pending')
            ->assertSee('In Transit')
            ->assertSee('Delivered');

        $this->actingAs($userA)->get(route('vendor.deliveries.index', ['status' => 'in_transit']))
            ->assertOk()
            ->assertSee('DLV-TRANSIT-A')
            ->assertDontSee('DLV-ASSIGNED-A')
            ->assertDontSee('DLV-DELIVERED-A');
        $this->actingAs($userA)->get(route('vendor.deliveries.index', [
            'status' => 'delivered',
            'q' => 'TRACK-DLV-DELIVERED-A',
            'delivery_method' => 'third_party_courier',
            'expected_date' => now()->addDay()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee('DLV-DELIVERED-A')
            ->assertSee('Vendor A Item × 1')
            ->assertDontSee('Customer A')
            ->assertDontSee('Private delivery address')
            ->assertSee(str_replace('&', '&amp;', route('vendor.deliveries.index', [
                'q' => 'TRACK-DLV-DELIVERED-A',
                'delivery_method' => 'third_party_courier',
                'expected_date' => now()->addDay()->toDateString(),
                'status' => 'assigned',
            ])), false);
        $this->actingAs($userA)->get(route('vendor.deliveries.index', [
            'status' => 'assigned',
            'q' => 'Customer A',
        ]))
            ->assertOk()
            ->assertSee('No delivery records match the selected filters.')
            ->assertDontSee('DLV-ASSIGNED-A');

        $this->actingAs($userA)->get(route('vendor.deliveries.show', $assigned->reference))
            ->assertOk()
            ->assertSee('Vendor A Item')
            ->assertSee('Order TH-DELIVERY-A')
            ->assertDontSee('Customer A')
            ->assertDontSee('Private delivery address')
            ->assertDontSee('Internal-only delivery note');

        $this->actingAs($userA)->get(route('vendor.deliveries.show', $delivered->reference))
            ->assertOk()
            ->assertSee('Delivered')
            ->assertSee('Pending');

        $this->actingAs($userA)->get(route('vendor.deliveries.show', $otherVendorTask->reference))
            ->assertNotFound();
        $this->actingAs($userB)->get(route('vendor.deliveries.show', $inTransit->reference))
            ->assertNotFound();
        $this->actingAs($userB)->get(route('vendor.deliveries.index'))
            ->assertOk()
            ->assertSee('DLV-PRIVATE-B')
            ->assertDontSee('DLV-ASSIGNED-A')
            ->assertDontSee('DLV-TRANSIT-A')
            ->assertDontSee('DLV-DELIVERED-A');
    }

    public function test_vendor_without_delivery_records_sees_a_clear_empty_state(): void
    {
        $vendor = $this->vendor('Empty Delivery Vendor');
        $user = $this->vendorUser($vendor);

        $this->actingAs($user)->get(route('vendor.deliveries.index'))
            ->assertOk()
            ->assertSee('No pending deliveries have been assigned to your business.')
            ->assertSee('In Transit')
            ->assertSee('Delivered')
            ->assertSee('delivery-filter-grid');
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
            'delivery_address' => 'Private delivery address',
            'internal_notes' => 'Internal-only order note',
        ]);
    }

    private function item(Order $order, Vendor $vendor, string $name): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'product_name' => $name,
            'unit_price' => 1000,
            'quantity' => 1,
            'line_total' => 1000,
        ]);
    }

    private function task(string $reference, Order $order, ?OrderItem $item, Vendor $vendor, string $status): DeliveryTask
    {
        return DeliveryTask::query()->create([
            'reference' => $reference,
            'order_id' => $order->id,
            'order_item_id' => $item?->id,
            'vendor_id' => $vendor->id,
            'status' => $status,
            'delivery_method' => 'third_party_courier',
            'expected_delivery_at' => now()->addDay()->toDateString(),
            'tracking_reference' => 'TRACK-'.$reference,
            'internal_notes' => 'Internal-only delivery note',
        ]);
    }
}
