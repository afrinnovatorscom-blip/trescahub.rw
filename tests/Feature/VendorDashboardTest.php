<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductRequest;
use App\Models\ShopProduct;
use App\Models\Shopcategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_vendor_scoped_summary_recent_activity_and_actions(): void
    {
        $category = Shopcategory::query()->create([
            'name' => 'Vendor Dashboard Category',
            'slug' => 'vendor-dashboard-category',
            'active' => true,
        ]);
        $vendor = $this->vendor('Dashboard Vendor');
        $otherVendor = $this->vendor('Other Dashboard Vendor');
        $user = User::factory()->create(['vendor_id' => $vendor->id]);
        $user->assignRole('vendor');

        $this->product($category, $vendor, 'Approved Vendor Item', 'approved');
        $this->product($category, $vendor, 'Pending Vendor Item', 'pending_approval');
        $this->product($category, $vendor, 'Rejected Vendor Item', 'rejected');
        $this->product($category, $otherVendor, 'Private Other Vendor Item', 'approved');

        $this->orderWithItem($vendor, 'DASHBOARD-TO-FULFILL', 'processing', 'paid', 'accepted');
        $this->orderWithItem($vendor, 'DASHBOARD-COMPLETED', 'completed', 'paid', 'accepted');
        $this->orderWithItem($otherVendor, 'OTHER-VENDOR-PRIVATE-ORDER', 'processing', 'paid', 'accepted');

        ProductRequest::query()->create([
            'reference' => 'DASHBOARD-REQUEST',
            'vendor_id' => $vendor->id,
            'customer_name' => 'Dashboard Customer',
            'phone' => '0788000000',
            'product_name' => 'Requested Vendor Item',
            'status' => 'awaiting_vendor_response',
        ]);

        $response = $this->actingAs($user)->get(route('vendor.dashboard'))->assertOk();

        $this->assertSame(3, $response->viewData('products'));
        $this->assertSame(1, $response->viewData('approved'));
        $this->assertSame(1, $response->viewData('pending'));
        $this->assertSame(1, $response->viewData('rejected'));
        $this->assertSame(2, $response->viewData('orders'));
        $this->assertSame(1, $response->viewData('ordersToFulfill'));
        $this->assertSame(1, $response->viewData('completedOrders'));

        $response->assertSee('Vendor Dashboard')
            ->assertSee('Recent Assigned Orders')
            ->assertSee('Recent Product Submissions')
            ->assertSee('Action Required')
            ->assertSee('DASHBOARD-TO-FULFILL')
            ->assertSee('Approved Vendor Item')
            ->assertSee('Respond to assigned product requests')
            ->assertSee(route('vendor.orders.index', ['tab' => 'paid']), false)
            ->assertDontSee('Private Other Vendor Item')
            ->assertDontSee('OTHER-VENDOR-PRIVATE-ORDER');
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

    private function product(Shopcategory $category, Vendor $vendor, string $name, string $approvalStatus): ShopProduct
    {
        return ShopProduct::query()->create([
            'product_name' => $name,
            'slug' => str()->slug($name),
            'status' => 'in_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'approval_status' => $approvalStatus,
            'publication_status' => $approvalStatus === 'approved' ? 'published' : 'unpublished',
        ]);
    }

    private function orderWithItem(Vendor $vendor, string $reference, string $fulfillmentStatus, string $paymentStatus, string $assignmentStatus): Order
    {
        $order = Order::query()->create([
            'reference' => $reference,
            'customer_name' => 'Dashboard Customer',
            'phone' => '0788000000',
            'delivery_address' => 'Kigali',
            'subtotal' => 20000,
            'total' => 20000,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => $fulfillmentStatus,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'vendor_id' => $vendor->id,
            'product_name' => 'Dashboard Assigned Product',
            'unit_price' => 10000,
            'quantity' => 2,
            'line_total' => 20000,
            'fulfillment_status' => $fulfillmentStatus,
            'assignment_status' => $assignmentStatus,
        ]);

        return $order;
    }
}
