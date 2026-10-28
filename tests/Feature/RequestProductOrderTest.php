<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductRequest;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequestProductOrderTest extends TestCase
{
    use RefreshDatabase;

    private Shopcategory $category;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Shopcategory::query()->create([
            'name' => 'Request Product Tests',
            'slug' => 'request-product-tests',
            'active' => true,
        ]);
        $this->seed(MarketplaceUserSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrator');
        $this->admin = $this->admin->fresh();
    }

    public function test_catalog_request_creates_an_owned_order_and_repeated_submission_reuses_it(): void
    {
        $customer = $this->customer();
        $product = $this->product(['price' => 12500]);
        $submissionKey = (string) Str::uuid();
        $payload = [
            'product_slug' => $product->slug,
            'customer_name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'quantity' => 2,
            'delivery_location' => 'KG 12 Ave, Gasabo, Kigali',
            'instructions' => 'Call before delivery.',
            'submission_key' => $submissionKey,
        ];

        $this->actingAs($customer, 'customers')
            ->get(route('product-requests.create', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee($product->product_name)
            ->assertSee('RWF 12,500');

        $this->actingAs($customer, 'customers')
            ->post(route('product-requests.store'), $payload)
            ->assertRedirect(route('checkout.done', Order::query()->firstOrFail()->reference));

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertNull($order->user_id);
        $this->assertSame('request_product', $order->source);
        $this->assertSame('pending_confirmation', $order->fulfillment_status);
        $this->assertSame('KG 12 Ave, Gasabo, Kigali', $order->delivery_address);
        $this->assertSame('Call before delivery.', $order->delivery_notes);
        $this->assertSame(25000.0, (float) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame($product->product_name, $order->items[0]->product_name);
        $this->assertSame(2, $order->items[0]->quantity);
        $this->assertSame(12500.0, (float) $order->items[0]->unit_price);
        $this->assertSame(25000.0, (float) $order->items[0]->line_total);
        $this->assertDatabaseCount('product_requests', 0);

        $this->post(route('product-requests.store'), $payload)
            ->assertRedirect(route('checkout.done', $order->reference));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        $this->get(route('account.orders'))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee($product->product_name)
            ->assertSee('Order received');
        $this->get(route('account.orders.show', $order->reference))
            ->assertOk()
            ->assertSee($product->product_name)
            ->assertSee('KG 12 Ave, Gasabo, Kigali');

        $otherCustomer = $this->customer();
        $this->actingAs($otherCustomer, 'customers')
            ->get(route('account.orders.show', $order->reference))
            ->assertNotFound();

        $this->actingAs($this->admin, 'web')->get(route('marketplace.orders.index'))
            ->assertOk()
            ->assertSee('Request Product')
            ->assertSee($order->reference);
        $this->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('Request Product')
            ->assertSee('KG 12 Ave, Gasabo, Kigali')
            ->assertSee($product->product_name)
            ->assertSee('12,500');
    }

    public function test_cart_checkout_creates_one_multi_product_order_with_server_calculated_totals(): void
    {
        $customer = $this->customer();
        $first = $this->product(['price' => 3000]);
        $second = $this->product(['price' => 4500]);
        $submissionKey = (string) Str::uuid();

        $this->actingAs($customer, 'customers')
            ->withSession(['marketplace.cart' => [$first->slug => 2, $second->slug => 3]])
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('name="submission_key"', false);

        $this->post(route('checkout.place'), [
            'customer_name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'delivery_address' => 'Kigali',
            'payment_method' => 'cash_on_delivery',
            'submission_key' => $submissionKey,
            'total' => 1,
        ])->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame('cart_checkout', $order->source);
        $this->assertSame(19500.0, (float) $order->total);
        $this->assertCount(2, $order->items);
        $this->assertSame(2, $order->items->firstWhere('shop_product_id', $first->id)->quantity);
        $this->assertSame(3, $order->items->firstWhere('shop_product_id', $second->id)->quantity);
        $this->assertSame($customer->id, $order->customer_id);

        $this->actingAs($this->admin, 'web')->get(route('marketplace.orders.index'))
            ->assertOk()
            ->assertSee('Cart Checkout')
            ->assertSee($order->reference);
    }

    public function test_quote_and_free_text_product_requests_remain_in_the_request_workflow(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->actingAs($customer, 'customers')->post(route('product-requests.store'), [
            'product_slug' => $product->slug,
            'request_type' => 'quote',
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'quantity' => 1,
        ])->assertRedirect();

        $this->post(route('product-requests.store'), [
            'product_name' => 'Custom sourced equipment',
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'quantity' => 1,
            'instructions' => 'Please provide a quote.',
        ])->assertRedirect();

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(2, ProductRequest::query()->count());
        $this->assertDatabaseHas('product_requests', [
            'customer_id' => $customer->id,
            'product_name' => $product->product_name,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('product_requests', [
            'customer_id' => $customer->id,
            'product_name' => 'Custom sourced equipment',
            'instructions' => 'Please provide a quote.',
            'status' => 'submitted',
        ]);
    }

    public function test_unavailable_catalog_product_is_rejected_without_creating_an_order(): void
    {
        $product = $this->product([
            'tracks_inventory' => true,
            'stock_quantity' => 1,
        ]);

        $this->actingAs($this->customer(), 'customers')
            ->from(route('product-requests.create', ['product' => $product->slug]))
            ->post(route('product-requests.store'), [
                'product_slug' => $product->slug,
                'customer_name' => 'Customer',
                'phone' => '0788000000',
                'quantity' => 2,
                'delivery_location' => 'Kigali',
                'submission_key' => (string) Str::uuid(),
            ])
            ->assertRedirect(route('product-requests.create', ['product' => $product->slug]))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Customer '.Str::random(6),
            'email' => Str::lower(Str::random(8)).'@example.com',
            'phone' => '0788000000',
            'password' => 'valid-password',
        ]);
    }

    private function product(array $attributes = []): ShopProduct
    {
        return ShopProduct::query()->create(array_merge([
            'product_name' => 'Catalog Product '.Str::random(6),
            'slug' => 'catalog-product-'.Str::random(8),
            'status' => 'in_stock',
            'price' => 10000,
            'working_days' => 2,
            'shop_category_id' => $this->category->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ], $attributes));
    }
}
