<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MobileMoneyPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private ShopProduct $product;

    /** What the fake IntouchPay status API answers. */
    private string $intouchStatus = 'Pending';

    private ?array $statusResponse = null;

    private bool $statusConnectionFails = false;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.intouchpay.api_url' => 'https://intouch.test/api',
            'services.intouchpay.username' => 'tester',
            'services.intouchpay.password' => 'secret',
            'services.intouchpay.account_no' => 'SBX1',
        ]);

        $this->seed(MarketplaceUserSeeder::class);

        $category = ShopCategory::query()->create(['name' => 'Payments', 'slug' => 'payments', 'active' => true]);
        $this->product = ShopProduct::query()->create([
            'product_name' => 'Paid Product',
            'slug' => 'paid-product',
            'status' => 'in_stock',
            'price' => 15000,
            'working_days' => 2,
            'shop_category_id' => $category->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);
        $this->customer = Customer::query()->create([
            'name' => 'Paying Customer',
            'email' => 'payer@example.com',
            'phone' => '0788000000',
            'password' => 'valid-password',
        ]);
    }

    /** Whether the fake IntouchPay accepts payment requests. */
    private bool $requestAccepted = true;

    private function fakeIntouchPay(bool $requestAccepted = true): void
    {
        $this->requestAccepted = $requestAccepted;

        Http::fake([
            'intouch.test/api/requestpayment/' => fn () => Http::response($this->requestAccepted
                ? ['success' => true, 'responsecode' => '1000', 'transactionid' => 9001, 'message' => 'Pending']
                : ['success' => false, 'responsecode' => '2400', 'message' => 'Insufficient balance']),
            'intouch.test/api/gettransactionstatus/' => function () {
                if ($this->statusConnectionFails) {
                    throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out');
                }

                return Http::response($this->statusResponse ?? match ($this->intouchStatus) {
                    'Successfull' => ['success' => true, 'responsecode' => '01', 'status' => 'Successfull', 'message' => 'Transaction successful'],
                    'Failed' => ['success' => true, 'responsecode' => '02', 'status' => 'Failed', 'message' => 'Transaction failed'],
                    default => ['success' => true, 'responsecode' => '1000', 'status' => 'Pending', 'message' => 'Pending'],
                });
            },
        ]);
    }

    private function placeOrder(string $phone = '+250 78 123 4567'): PaymentTransaction
    {
        $this->actingAs($this->customer, 'customers')
            ->post(route('cart.add'), ['product' => $this->product->slug, 'quantity' => 2]);
        $this->post(route('checkout.details'), [
            'customer_name' => 'Paying Customer',
            'email' => 'payer@example.com',
            'phone' => '0788000000',
            'delivery_address' => 'Kigali',
        ])->assertRedirect(route('checkout.payment'));

        $response = $this->post(route('checkout.place'), [
            'payment_method' => 'mtn_mobile_money',
            'payment_phone' => $phone,
        ]);

        $transaction = PaymentTransaction::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('checkout.payment.status', $transaction));

        return $transaction;
    }

    public function test_checkout_sends_a_mobile_money_prompt_for_the_order_total(): void
    {
        $this->fakeIntouchPay();

        $transaction = $this->placeOrder();

        $this->assertSame('pending', $transaction->status);
        $this->assertSame('0781234567', $transaction->phone);
        $this->assertSame(30000.0, (float) $transaction->amount);
        $this->assertSame('9001', $transaction->transaction_id);
        $this->assertSame('pending', $transaction->order->payment_status);

        Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/requestpayment/')
            && $request['mobilephone'] === '250781234567'
            && (int) $request['amount'] === 30000
            && $request['requesttransactionid'] === $transaction->request_transaction_id
            && str_ends_with($request['callbackurl'], '/api/intouchpay/callback')
            && $request['password'] === hash('sha256', 'testerSBX1secret'.$request['timestamp']));

        $this->get(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertSee('Approve the payment on your phone');
        $this->getJson(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertJson(['status' => 'pending']);
        $this->assertDatabaseHas('payment_logs', ['payment_transaction_id' => $transaction->id, 'type' => 'request']);
    }

    public function test_verified_callback_marks_the_order_paid_exactly_once(): void
    {
        $this->fakeIntouchPay();
        $transaction = $this->placeOrder();
        $this->intouchStatus = 'Successfull';

        $payload = ['jsonpayload' => [
            'requesttransactionid' => $transaction->request_transaction_id,
            'transactionid' => 9001,
            'responsecode' => '01',
            'status' => 'Successfull',
            'statusdesc' => 'Successfully Processed Transaction',
        ]];

        $this->postJson('/api/intouchpay/callback', $payload)->assertOk()->assertJson(['success' => true]);
        $this->postJson('/api/intouchpay/callback', $payload)->assertOk();

        $order = $transaction->order->fresh('payments');
        $this->assertSame('completed', $transaction->fresh()->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertCount(1, $order->payments);
        $this->assertSame(30000.0, (float) $order->payments[0]->amount);
        $this->assertSame('mtn_mobile_money', $order->payments[0]->method);
        $this->assertTrue($order->hasConfirmedPaidPayment());
        $this->assertSame(1, $this->customer->notifications()->where('data->dedupe_key', 'order-'.$order->id.'-paid')->count());
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame(1, $admin->notifications()->where('data->dedupe_key', 'order-paid-'.$order->id)->count());

        $this->getJson(route('checkout.payment.status', $transaction))->assertJson(['status' => 'completed']);
        $this->assertSame([], session('marketplace.cart', []));
        $this->assertNull(session('marketplace.checkout'));
        $this->get(route('checkout.done', $order->reference))->assertOk();
        $this->actingAs($admin, 'web')->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertSee($order->reference);
    }

    public function test_callback_claims_are_not_trusted_without_api_confirmation(): void
    {
        $this->fakeIntouchPay();
        $transaction = $this->placeOrder();
        // IntouchPay's status API still says pending, whatever the callback claims.

        $this->post('/api/intouchpay/callback', [
            'requesttransactionid' => $transaction->request_transaction_id,
            'status' => 'Successfull',
            'responsecode' => '01',
        ])->assertOk();

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame('pending', $transaction->order->fresh()->payment_status);
        $this->assertDatabaseHas('payment_logs', ['payment_transaction_id' => $transaction->id, 'type' => 'callback']);

        $this->postJson('/api/intouchpay/callback', ['requesttransactionid' => 'unknown'])->assertOk();
        $this->assertDatabaseHas('payment_logs', ['request_transaction_id' => 'unknown', 'message' => 'Transaction not found']);
    }

    public function test_failed_payment_can_be_retried_for_the_same_order(): void
    {
        $this->fakeIntouchPay(requestAccepted: false);
        $transaction = $this->placeOrder();

        $this->assertSame('failed', $transaction->status);
        $this->assertSame('Insufficient balance', $transaction->failure_reason);
        $this->get(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertSee('Payment not completed')
            ->assertSee('Insufficient balance')
            ->assertSee(route('account.orders.pay.submit', $transaction->order->reference), false);

        $order = $transaction->order;
        $this->get(route('account.orders.show', $order->reference))->assertSee('Pay now');

        $this->requestAccepted = true;
        $this->post(route('account.orders.pay.submit', $order->reference), [
            'payment_method' => 'airtel_money',
            'payment_phone' => '0731234567',
        ]);

        $retry = PaymentTransaction::query()->latest('id')->firstOrFail();
        $this->assertNotSame($transaction->id, $retry->id);
        $this->assertSame('pending', $retry->status);
        $this->assertSame('airtel_money', $retry->method);
        $this->assertSame(2, Order::query()->first()->paymentTransactions()->count());

        $this->post(route('account.orders.pay.submit', $order->reference), [
            'payment_method' => 'airtel_money',
            'payment_phone' => '0731234567',
        ])->assertRedirect(route('checkout.payment.status', $retry));
        $this->assertSame(2, $order->paymentTransactions()->count(), 'A double submit reuses the pending prompt.');
    }

    public function test_rwf_100000_rejection_uses_the_payment_failure_card_and_stays_out_of_order_processing(): void
    {
        $this->fakeIntouchPay(requestAccepted: false);
        $this->product->update(['price' => 50000]);
        $transaction = $this->placeOrder();
        $order = $transaction->order;

        $this->assertSame(100000.0, (float) $transaction->amount);
        $this->assertSame('failed', $transaction->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending_confirmation', $order->fulfillment_status);
        $this->assertDatabaseCount('order_payments', 0);

        $this->get(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertViewIs('front.pages.order-payment')
            ->assertSee('Payment not completed')
            ->assertSee('Insufficient balance')
            ->assertSee('Pay RWF 100,000')
            ->assertDontSee('View my order')
            ->assertDontSee('href="'.route('account.orders.show', $order->reference).'"', false)
            ->assertDontSee('ViewException')
            ->assertDontSee('SQLSTATE');
        $this->assertSame([$this->product->slug => 2], session('marketplace.cart'));
        $this->get(route('checkout.payment.status', $transaction))->assertOk();
        $this->get(route('checkout'))->assertOk()->assertSee($this->product->product_name);
        $this->get(route('checkout.payment'))->assertOk()->assertSee('Pay for your order');
        $this->get(route('checkout.done', $order->reference))->assertNotFound();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin, 'web')->get(route('marketplace.orders.index', ['tab' => 'pending']))
            ->assertOk()
            ->assertDontSee($order->reference);

        $item = $order->items()->firstOrFail();
        try {
            app(OrderService::class)->saveProcessingChanges($order, $admin, [$item->id => 'admin'], false);
            $this->fail('An unverified mobile-money order must not be allocated.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'A verified mobile money payment is required',
                implode(' ', array_merge(...array_values($exception->errors()))),
            );
        }
        try {
            app(OrderService::class)->prepareDelivery($order, [$item->id], null, $admin, []);
            $this->fail('An unverified mobile-money order must not get a delivery task.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'A verified mobile money payment is required',
                implode(' ', array_merge(...array_values($exception->errors()))),
            );
        }
        $this->assertDatabaseCount('delivery_tasks', 0);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('A verified mobile money payment is required');
        app(OrderService::class)->transition($order, 'confirmed', $admin, reviewDecision: true);
    }

    public function test_connection_errors_are_not_shown_to_customers(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException(
            'cURL error 60: SSL certificate problem: unable to get local issuer certificate'
        ));

        $transaction = $this->placeOrder();

        $this->assertSame('failed', $transaction->status);
        $this->get(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertSee('We could not reach the mobile money service just now.')
            ->assertDontSee('cURL error')
            ->assertDontSee('intouch.test');
        $this->assertDatabaseHas('payment_logs', ['payment_transaction_id' => $transaction->id, 'type' => 'request']);
        $this->assertStringContainsString('cURL error 60', (string) $transaction->logs()->first()->message);
    }

    public function test_rejected_merchant_credentials_are_not_blamed_on_the_customer(): void
    {
        Http::fake(['intouch.test/api/requestpayment/' => Http::response(['success' => false, 'responsecode' => '0007', 'message' => 'No such user'])]);

        $transaction = $this->placeOrder();

        $this->assertSame('failed', $transaction->status);
        $this->get(route('checkout.payment.status', $transaction))
            ->assertSee('Mobile money payments are temporarily unavailable.')
            ->assertDontSee('No such user');
    }

    public function test_status_poll_marks_declined_payments_failed(): void
    {
        $this->fakeIntouchPay();
        $transaction = $this->placeOrder();
        $this->intouchStatus = 'Failed';

        $this->getJson(route('checkout.payment.status', $transaction))->assertJson(['status' => 'failed']);
        $this->assertSame('pending', $transaction->order->fresh()->payment_status);
    }

    public function test_explicit_failed_gateway_status_is_handled_when_response_success_is_false(): void
    {
        $this->fakeIntouchPay();
        $transaction = $this->placeOrder();
        $this->statusResponse = [
            'success' => false,
            'responsecode' => '02',
            'status' => 'Failed',
            'message' => 'Transaction failed',
        ];

        $this->getJson(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertJson(['status' => 'failed']);
        $this->get(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertSee('Payment not completed')
            ->assertSee('Transaction failed')
            ->assertDontSee('SQLSTATE');
    }

    public function test_status_timeout_keeps_the_existing_pending_payment_state(): void
    {
        $this->fakeIntouchPay();
        $transaction = $this->placeOrder();
        $this->statusConnectionFails = true;

        $this->getJson(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
                'message' => 'Approve the payment of RWF 30,000 on your phone.',
            ])
            ->assertDontSee('cURL error');
        $this->get(route('checkout.payment.status', $transaction))
            ->assertOk()
            ->assertSee('Approve the payment on your phone')
            ->assertDontSee('cURL error')
            ->assertDontSee('SQLSTATE');
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame('pending', $transaction->order->fresh()->payment_status);
        $this->assertSame([$this->product->slug => 2], session('marketplace.cart'));
        $this->get(route('checkout'))->assertOk()->assertSee($this->product->product_name);
    }

    public function test_customers_cannot_see_other_customers_payments(): void
    {
        $this->fakeIntouchPay();
        $transaction = $this->placeOrder();

        $other = Customer::query()->create([
            'name' => 'Other', 'email' => Str::random(6).'@example.com', 'phone' => '0788111111', 'password' => 'valid-password',
        ]);

        $this->actingAs($other, 'customers')->get(route('checkout.payment.status', $transaction))->assertNotFound();
        $this->post(route('account.orders.pay.submit', $transaction->order->reference), [
            'payment_method' => 'mtn_mobile_money',
            'payment_phone' => '0781234567',
        ])->assertNotFound();
    }
}
