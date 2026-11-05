<?php

namespace Tests\Feature;

use App\Models\DeliveryTask;
use App\Models\AuditLog;
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

        $this->actingAs($plainUser)->get(route('marketplace.reports.orders-history.export'))
            ->assertForbidden();
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

    public function test_finance_report_includes_all_order_statuses_and_sums_matching_orders_once(): void
    {
        $inRange = Carbon::now()->subDays(2);
        $pending = $this->createOrder('FIN-PENDING', 1200, 'pending_confirmation', 'pending', $inRange);
        $this->createOrder('FIN-CANCELLED', 350, 'cancelled', 'pending', Carbon::now());
        $this->createOrder('FIN-OLD', 9000, 'completed', 'paid', Carbon::now()->subMonth());
        $this->createItem($pending, $this->product, 1, 600);
        $this->createItem($pending, $this->product, 1, 600);

        $allOrders = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'category' => 'finance',
        ]));
        $allOrders->assertOk()
            ->assertSee('FIN-PENDING')
            ->assertSee('FIN-CANCELLED')
            ->assertSee('FIN-OLD')
            ->assertViewHas('financeReport', fn (array $financeReport): bool => $financeReport['total_orders'] === 3
                && $financeReport['total_order_value'] === 10550.0);

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'category' => 'finance',
            'from' => Carbon::now()->subDays(3)->toDateString(),
            'to' => Carbon::now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertSee('Finance Report')
            ->assertSee('Total Orders')
            ->assertSee('Total Order Value')
            ->assertSee('FIN-PENDING')
            ->assertSee('FIN-CANCELLED')
            ->assertDontSee('FIN-OLD')
            ->assertSee('Order Date')
            ->assertSee('Delivery Address')
            ->assertViewHas('financeReport', function (array $financeReport): bool {
                $this->assertSame(2, $financeReport['total_orders']);
                $this->assertSame(1550.0, $financeReport['total_order_value']);
                $this->assertCount(2, $financeReport['orders']);

                return true;
            });

        $searched = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'category' => 'finance',
            'from' => Carbon::now()->subDays(3)->toDateString(),
            'to' => Carbon::now()->toDateString(),
            'q' => 'FIN-CANCELLED',
        ]));
        $searched->assertOk()
            ->assertSee('FIN-CANCELLED')
            ->assertDontSee('FIN-PENDING')
            ->assertViewHas('financeReport', fn (array $financeReport): bool => $financeReport['total_orders'] === 1
                && $financeReport['total_order_value'] === 350.0);
    }

    public function test_finance_report_has_compact_pagination_and_keeps_totals_and_filters(): void
    {
        $createdAt = Carbon::now();
        for ($index = 1; $index <= 27; $index++) {
            $this->createOrder('FIN-PAGE-'.$index, 100, 'processing', 'pending', $createdAt);
        }

        $filters = [
            'category' => 'finance',
            'from' => $createdAt->toDateString(),
            'to' => $createdAt->toDateString(),
            'q' => 'FIN-PAGE-',
        ];
        $firstPage = $this->actingAs($this->admin)->get(route('marketplace.reports.index', $filters));
        $firstPage->assertOk()
            ->assertSee('Next')
            ->assertSee('page=2')
            ->assertViewHas('financeReport', function (array $financeReport): bool {
                $this->assertSame(27, $financeReport['total_orders']);
                $this->assertSame(2700.0, $financeReport['total_order_value']);
                $this->assertCount(25, $financeReport['orders']);

                return true;
            });

        $secondPage = $this->actingAs($this->admin)->get(route('marketplace.reports.index', $filters + ['page' => 2]));
        $secondPage->assertOk()
            ->assertSee('Previous')
            ->assertSee('FIN-PAGE-2')
            ->assertSee('FIN-PAGE-1')
            ->assertDontSee('FIN-PAGE-27')
            ->assertViewHas('financeReport', fn (array $financeReport): bool => $financeReport['orders']->count() === 2
                && $financeReport['total_orders'] === 27);
    }

    public function test_finance_pdf_export_uses_date_and_search_filters(): void
    {
        $createdAt = Carbon::now()->subDays(2);
        $this->createOrder('FIN-PDF-MATCH', 2750, 'confirmed', 'pending', $createdAt);
        for ($index = 1; $index <= 60; $index++) {
            $this->createOrder('FIN-PDF-'.$index, 10, 'processing', 'pending', $createdAt);
        }
        $this->createOrder('FIN-PDF-OTHER', 9999, 'cancelled', 'pending', Carbon::now()->subMonth());
        $from = Carbon::now()->subDays(3)->toDateString();
        $to = Carbon::now()->toDateString();

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.export', [
            'category' => 'finance',
            'from' => $from,
            'to' => $to,
            'q' => 'FIN-PDF-',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $pdfContent = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $pdfContent);
        $this->assertGreaterThanOrEqual(2, preg_match_all('/\/Type\s*\/Page\b/', $pdfContent));
        $this->assertStringContainsString("tresca-hub-finance-report-{$from}-to-{$to}.pdf", $response->headers->get('content-disposition'));
    }

    public function test_orders_history_lists_all_statuses_and_combines_filters_with_pagination(): void
    {
        $inMonth = $this->createOrder('HISTORY-PENDING-001', 1200, 'pending_confirmation', 'pending', Carbon::now());
        $inMonth->update([
            'customer_name' => 'History Match Customer',
            'phone' => '0788111223',
            'delivery_address' => 'KG 10, Kigali',
        ]);
        $cancelled = $this->createOrder('HISTORY-CANCELLED-001', 400, 'cancelled', 'pending', Carbon::now()->subDays(2));
        $outsideMonth = $this->createOrder('HISTORY-OLD-001', 300, 'completed', 'paid', Carbon::now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.orders-history'));
        $response->assertOk()
            ->assertSee('HISTORY-PENDING-001')
            ->assertSee('HISTORY-CANCELLED-001')
            ->assertSee('HISTORY-OLD-001')
            ->assertDontSee('Total Orders Recorded')
            ->assertDontSee('Orders This Month')
            ->assertViewHas('totalOrders', 3)
            ->assertViewHas('ordersThisMonth', 2);

        $filtered = $this->actingAs($this->admin)->get(route('marketplace.reports.orders-history', [
            'customer' => 'History Match',
            'phone' => '0788111223',
            'reference' => 'PENDING-001',
            'from' => Carbon::now()->startOfMonth()->toDateString(),
            'to' => Carbon::now()->toDateString(),
            'status' => 'pending_confirmation',
        ]));
        $filtered->assertOk()
            ->assertSee('HISTORY-PENDING-001')
            ->assertDontSee('HISTORY-CANCELLED-001')
            ->assertDontSee('HISTORY-OLD-001')
            ->assertViewHas('orders', fn ($orders): bool => $orders->total() === 1 && $orders->first()->id === $inMonth->id);

        for ($index = 1; $index <= 26; $index++) {
            $this->createOrder('HISTORY-PAGE-'.$index, 100, 'processing', 'pending', Carbon::now()->subMinutes($index));
        }
        $page = $this->actingAs($this->admin)->get(route('marketplace.reports.orders-history'));
        $page->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->perPage() === 25 && $orders->total() === 29);
    }

    public function test_orders_history_pdf_exports_all_orders_matching_current_filters(): void
    {
        $createdAt = Carbon::now()->subDays(2);
        for ($index = 1; $index <= 28; $index++) {
            $order = $this->createOrder('EXPORT-HISTORY-'.$index, 100 + $index, 'processing', 'pending', $createdAt);
            $order->update([
                'customer_name' => 'Export Customer',
                'phone' => '0788000111',
                'delivery_address' => 'KG 10, Kigali',
            ]);
            $this->createItem($order, $this->product, 1, 100 + $index);
        }
        $this->createOrder('EXPORT-OUTSIDE-FILTER', 10000, 'cancelled', 'pending', Carbon::now()->subMonth());

        $filters = [
            'customer' => 'Export Customer',
            'phone' => '0788000111',
            'reference' => 'EXPORT-HISTORY-',
            'from' => Carbon::now()->subDays(3)->toDateString(),
            'to' => Carbon::now()->toDateString(),
            'status' => 'processing',
        ];
        $list = $this->actingAs($this->admin)->get(route('marketplace.reports.orders-history', $filters));
        $list->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->total() === 28 && $orders->count() === 25)
            ->assertSee('>Export PDF</span>', false)
            ->assertDontSee('Export filtered orders PDF')
            ->assertSee(str_replace('&', '&amp;', route('marketplace.reports.orders-history.export', $filters)), false);

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.orders-history.export', $filters));
        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('tresca-hub-orders-history-', $response->headers->get('content-disposition'));
        $pdfContent = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $pdfContent);
        $this->assertGreaterThanOrEqual(2, preg_match_all('/\/Type\s*\/Page\b/', $pdfContent));
    }

    public function test_order_history_detail_shows_saved_snapshots_and_existing_timeline_read_only(): void
    {
        $order = $this->createOrder('HISTORY-DETAIL-001', 2500, 'cancelled', 'pending', Carbon::now()->subDays(4));
        $order->update([
            'phone' => '0788999000',
            'delivery_address' => 'Complete saved customer address',
            'cancellation_reason' => 'Customer could not be reached',
        ]);
        $item = $this->createItem($order, $this->product, 2, 2500, $this->vendor->id, 'accepted');
        $item->update([
            'product_name' => 'Historical Product Name',
            'variation_label' => 'Color',
            'variation_value' => 'Blue',
        ]);
        $order->histories()->create([
            'from_status' => 'pending_confirmation',
            'to_status' => 'confirmed',
            'note' => 'Order accepted after allocation.',
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);
        $order->histories()->create([
            'from_status' => 'confirmed',
            'to_status' => 'cancelled',
            'note' => 'Customer could not be reached',
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);
        $delivery = DeliveryTask::query()->create([
            'reference' => 'HISTORY-DELIVERY-001',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'delivered',
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDay(),
            'delivered_at' => Carbon::now()->subDay(),
        ]);
        AuditLog::query()->create([
            'action' => 'order.item_allocated',
            'auditable_type' => $item->getMorphClass(),
            'auditable_id' => $item->id,
            'changes' => ['to_vendor' => $this->vendor->id],
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);
        AuditLog::query()->create([
            'action' => 'order.item_accepted',
            'auditable_type' => $item->getMorphClass(),
            'auditable_id' => $item->id,
            'changes' => [],
            'created_at' => Carbon::now()->subDays(3),
            'updated_at' => Carbon::now()->subDays(3),
        ]);
        AuditLog::query()->create([
            'action' => 'delivery.completed',
            'auditable_type' => $delivery->getMorphClass(),
            'auditable_id' => $delivery->id,
            'changes' => [],
            'meta' => ['actor_type' => 'customer', 'actor_id' => 999],
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.orders-history.show', $order));
        $response->assertOk()
            ->assertSee('HISTORY-DETAIL-001')
            ->assertSee('Complete saved customer address')
            ->assertSee('Historical Product Name')
            ->assertSee('Color · Blue')
            ->assertSee('Alpha Supplier')
            ->assertSee('HISTORY-DELIVERY-001')
            ->assertSee('Customer confirmed delivery')
            ->assertSee('Customer could not be reached')
            ->assertSee('Not recorded in order data')
            ->assertSee('Order placed')
            ->assertDontSee('Approve Order')
            ->assertDontSee('Reject Order')
            ->assertDontSee('Reallocate');
    }

    public function test_reports_use_real_order_and_delivery_records(): void
    {
        $now = Carbon::now();
        $order = $this->createOrder('REAL-DELIVERY-ORDER', 750000, 'dispatched', 'paid', $now);
        $item = $this->createItem($order, $this->product, 1, 750000, $this->vendor->id, 'accepted');

        DeliveryTask::query()->create([
            'reference' => 'DT-REAL-0001',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'vendor_id' => $this->vendor->id,
            'delivery_officer_id' => $this->admin->id,
            'status' => 'delivered',
            'delivery_method' => 'courier',
            'tracking_reference' => 'TRACK-REAL-001',
            'created_at' => $now->copy()->subDays(3),
            'updated_at' => $now,
            'delivered_at' => $now,
        ]);

        DeliveryTask::query()->create([
            'reference' => 'DT-PENDING-0001',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'vendor_id' => $this->vendor->id,
            'delivery_officer_id' => $this->admin->id,
            'status' => 'in_transit',
            'tracking_reference' => 'TRACK-PENDING-001',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->actingAs($this->admin)->get(route('marketplace.reports.index', [
            'from' => $now->format('Y-m-d'),
            'to' => $now->format('Y-m-d'),
            'category' => 'orders',
        ]));

        $response->assertOk()
            ->assertSee('REAL-DELIVERY-ORDER')
            ->assertSee('DT-REAL-0001')
            ->assertSee('Test Customer')
            ->assertSee('ThinkPad T14')
            ->assertSee('Alpha Supplier')
            ->assertSee('TRACK-REAL-001')
            ->assertDontSee('TRACK-PENDING-001')
            ->assertSee('1 completed deliveries in this period');
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
            ->assertSee('Requires finance permissions')
            ->assertDontSee(route('marketplace.reports.index', ['category' => 'finance']), false);

        $this->actingAs($operationsOfficer)->get(route('marketplace.reports.index', ['category' => 'finance']))
            ->assertForbidden();
        $this->actingAs($operationsOfficer)->get(route('marketplace.reports.export', ['category' => 'finance']))
            ->assertForbidden();
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
