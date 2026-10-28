<?php

namespace Tests\Feature;

use App\Models\InventoryAdjustmentRequest;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ProductRequest;
use App\Models\Servicerequest;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Vendor $vendor;
    private ShopProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrator');
        $this->admin = $this->admin->fresh();

        $this->vendor = Vendor::query()->create([
            'business_name' => 'Alpha Supplier',
            'status' => 'active',
            'email' => 'alpha@example.com',
            'phone' => '0788111222',
            'district' => 'Kicukiro',
        ]);

        $category = Shopcategory::query()->create([
            'name' => 'Laptops & Computers',
            'slug' => 'laptops-computers',
        ]);

        $this->product = ShopProduct::query()->create([
            'product_name' => 'ThinkPad T14',
            'slug' => 'thinkpad-t14',
            'sku' => 'TP-T14-001',
            'price' => 500000,
            'stock_quantity' => 20,
            'reserved_quantity' => 2,
            'tracks_inventory' => true,
            'low_stock_threshold' => 5,
            'approval_status' => 'approved',
            'publication_status' => 'published',
            'shop_category_id' => $category->id,
            'vendor_id' => $this->vendor->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $response = $this->get(route('marketplace.reports.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_reports_permission_is_forbidden(): void
    {
        $plainUser = User::factory()->create();
        $response = $this->actingAs($plainUser)->get(route('marketplace.reports.index'));
        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_reports_page_with_default_dates(): void
    {
        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index'));

        $response->assertOk()
            ->assertSee('Reports & Analytics')
            ->assertSee('Executive Summary')
            ->assertSee('Product & Sales Performance')
            ->assertSee('Service Request Performance')
            ->assertSee('Orders & Delivery Performance')
            ->assertSee('Inventory & Stock Movement Report')
            ->assertSee('Vendor Performance & Fulfillment');
    }

    public function test_reports_filter_by_date_range_accurately(): void
    {
        $pastDate = Carbon::now()->subMonths(2);
        $currentDate = Carbon::now();

        // Order in the past (outside filter)
        $pastOrder = $this->createOrder('OLD-ORD-1', 400000, 'confirmed', 'pending', $pastDate);
        $this->createItem($pastOrder, $this->product, 1, 400000);

        // Order in the current month (inside filter)
        $currentOrder = $this->createOrder('CURR-ORD-1', 500000, 'confirmed', 'paid', $currentDate);
        $this->createItem($currentOrder, $this->product, 1, 500000);

        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk()
            ->assertSee('CURR-ORD-1')
            ->assertDontSee('OLD-ORD-1');
    }

    public function test_invalid_date_range_where_to_is_before_from_fails_validation(): void
    {
        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => '2026-10-25',
            'to' => '2026-10-20',
        ]));

        $response->assertSessionHasErrors(['to']);
    }

    public function test_category_filter_renders_targeted_sections(): void
    {
        // When category is products, it should show products and executive summary
        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'category' => 'products',
        ]));

        $response->assertOk()
            ->assertSee('Product & Sales Performance')
            ->assertDontSee('id="section-services"', false)
            ->assertDontSee('id="section-orders"', false)
            ->assertDontSee('id="section-inventory"', false);

        // When category is services
        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'category' => 'services',
        ]));

        $response->assertOk()
            ->assertSee('Service Request Performance')
            ->assertDontSee('id="section-products"', false)
            ->assertDontSee('id="section-orders"', false);
    }

    public function test_executive_summary_distinguishes_order_value_and_collected_revenue(): void
    {
        $now = Carbon::now();

        // 1. Unpaid order of 300,000
        $unpaidOrder = $this->createOrder('UNPAID-001', 300000, 'confirmed', 'pending', $now);
        $this->createItem($unpaidOrder, $this->product, 1, 300000);

        // 2. Paid order of 500,000 with recorded payment
        $paidOrder = $this->createOrder('PAID-001', 500000, 'confirmed', 'paid', $now);
        $this->createItem($paidOrder, $this->product, 1, 500000);

        OrderPayment::query()->create([
            'order_id' => $paidOrder->id,
            'recorded_by' => $this->admin->id,
            'amount' => 500000,
            'type' => 'full',
            'method' => 'bank_transfer',
            'submission_key' => Str::uuid()->toString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => $now->format('Y-m-d'),
            'to' => $now->format('Y-m-d'),
        ]));

        $response->assertOk();
        // Gross order value should be 800,000 RWF
        $response->assertSee('800,000');
        // Collected revenue should be 500,000 RWF
        $response->assertSee('500,000');
    }

    public function test_service_requests_performance_groups_by_category_and_honors_dates(): void
    {
        $now = Carbon::now();

        Servicerequest::query()->create([
            'name' => 'Alice Client',
            'phone' => '0788000111',
            'email' => 'alice@example.com',
            'service_category' => 'Engineering & Technical',
            'description' => 'Electrical wiring evaluation',
            'status' => 'completed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Servicerequest::query()->create([
            'name' => 'Bob Client',
            'phone' => '0788000222',
            'email' => 'bob@example.com',
            'service_category' => 'Engineering & Technical',
            'description' => 'Solar installation survey',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => $now->format('Y-m-d'),
            'to' => $now->format('Y-m-d'),
            'category' => 'services',
        ]));

        $response->assertOk()
            ->assertSee('Engineering & Technical')
            ->assertSee('Fulfillment Disclosure')
            ->assertSee('automated checkout ledger');
    }

    public function test_inventory_report_tracks_movements_and_isolates_pending_requests(): void
    {
        $now = Carbon::now();

        // Approved movement
        InventoryMovement::query()->create([
            'shop_product_id' => $this->product->id,
            'user_id' => $this->admin->id,
            'type' => 'addition',
            'quantity_delta' => 15,
            'stock_before' => 5,
            'stock_after' => 20,
            'note' => 'Restock from supplier',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Pending vendor adjustment request
        InventoryAdjustmentRequest::query()->create([
            'shop_product_id' => $this->product->id,
            'vendor_id' => $this->vendor->id,
            'requested_by' => $this->admin->id,
            'change_type' => 'addition',
            'quantity' => 10,
            'stock_before' => 20,
            'quantity_delta' => 10,
            'status' => 'pending',
            'reason' => 'Vendor inbound batch',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => $now->format('Y-m-d'),
            'to' => $now->format('Y-m-d'),
            'category' => 'inventory',
        ]));

        $response->assertOk()
            ->assertSee('+15')
            ->assertSee('Restock from supplier')
            ->assertSee('pending vendor inventory adjustment requests awaiting administrative review');
    }

    public function test_vendor_performance_separates_admin_handled_from_vendor_fulfillment(): void
    {
        $now = Carbon::now();

        // Admin-handled order item
        $inHouseOrder = $this->createOrder('INHOUSE-ORD', 100000, 'confirmed', 'paid', $now);
        $this->createItem($inHouseOrder, $this->product, 1, 100000, vendorId: null, assignmentStatus: 'admin_handled');

        // Vendor-assigned order item
        $vendorOrder = $this->createOrder('VENDOR-ORD', 200000, 'confirmed', 'paid', $now);
        $this->createItem($vendorOrder, $this->product, 1, 200000, vendorId: $this->vendor->id, assignmentStatus: 'accepted');

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => $now->format('Y-m-d'),
            'to' => $now->format('Y-m-d'),
            'category' => 'vendors',
        ]));

        $response->assertOk()
            ->assertSee('Admin / In-House Fulfillment')
            ->assertSee('Vendor-Allocated Fulfillment')
            ->assertSee('Alpha Supplier')
            ->assertSee('100,000')
            ->assertSee('200,000');
    }

    public function test_finance_restricted_user_cannot_see_revenue(): void
    {
        // Create user with reports.view but without finance.view
        $operationsOfficer = User::factory()->create();
        $operationsOfficer->assignRole('operations-manager');
        $operationsOfficer = $operationsOfficer->fresh();

        $response = $this->actingAs($operationsOfficer)->get(route('marketplace.reports.index'));

        $response->assertOk()
            ->assertSee('Restricted')
            ->assertSee('Requires finance permissions');
    }

    public function test_pdf_export_downloads_valid_pdf_with_matching_filters(): void
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.export', [
            'from' => $from,
            'to' => $to,
            'category' => 'products',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString("tresca-hub-report-{$from}-to-{$to}.pdf", $response->headers->get('content-disposition'));
    }

    public function test_reports_empty_date_range_renders_clean_empty_states(): void
    {
        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => '2020-01-01',
            'to' => '2020-01-02',
        ]));

        $response->assertOk()
            ->assertSee('No product order records found for this period')
            ->assertSee('No service requests recorded during this period')
            ->assertSee('No orders placed within this date range')
            ->assertSee('No stock movements recorded for this date range');
    }

    // ── Helper methods ──
    private function createOrder(string $reference, float $total, string $fulfillmentStatus, string $paymentStatus, Carbon $createdAt): Order
    {
        $order = new Order([
            'reference' => $reference,
            'customer_name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '0788000999',
            'delivery_address' => 'KG 123 St, Kigali',
            'subtotal' => $total,
            'total' => $total,
            'payment_status' => $paymentStatus,
            'fulfillment_status' => $fulfillmentStatus,
        ]);
        $order->timestamps = false;
        $order->created_at = $createdAt;
        $order->updated_at = $createdAt;
        $order->save();

        return $order;
    }

    private function createItem(Order $order, ShopProduct $product, int $qty, float $lineTotal, ?int $vendorId = null, string $assignmentStatus = 'unassigned'): OrderItem
    {
        $item = new OrderItem([
            'order_id' => $order->id,
            'shop_product_id' => $product->id,
            'vendor_id' => $vendorId,
            'product_name' => $product->product_name,
            'sku' => $product->sku,
            'unit_price' => $lineTotal / $qty,
            'quantity' => $qty,
            'line_total' => $lineTotal,
            'fulfillment_status' => $order->fulfillment_status,
            'assignment_status' => $assignmentStatus,
        ]);
        $item->timestamps = false;
        $item->created_at = $order->created_at;
        $item->updated_at = $order->created_at;
        $item->save();

        return $item;
    }
}
