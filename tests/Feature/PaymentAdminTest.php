<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Models\PaymentTransaction;
use App\Models\Payout;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentAdminTest extends TestCase
{
    use RefreshDatabase;

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
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }

    private function fakeIntouch(float $balance = 50000, array $deposit = ['success' => true, 'responsecode' => '2001', 'referenceid' => 'REF-1', 'status' => 'Successfull'], array $status = []): void
    {
        Http::fake([
            'intouch.test/api/getbalance/' => Http::response(['status' => 'success', 'balance' => $balance, 'currency' => 'RWF']),
            'intouch.test/api/requestdeposit/' => Http::response($deposit),
            'intouch.test/api/gettransactionstatus/' => Http::response($status ?: ['success' => true, 'responsecode' => '1000', 'status' => 'Pending']),
        ]);
    }

    private function transaction(string $status = 'completed'): PaymentTransaction
    {
        $customer = Customer::query()->create(['name' => 'Payer One', 'email' => 'payer@example.com', 'phone' => '0788000000', 'password' => 'valid-password']);
        $order = Order::query()->create([
            'reference' => 'TH-TEST-0001', 'source' => 'cart_checkout', 'customer_id' => $customer->id, 'customer_name' => 'Payer One',
            'phone' => '0788000000', 'delivery_address' => 'Kigali', 'subtotal' => 30000, 'total' => 30000,
            'payment_status' => $status === 'completed' ? 'paid' : 'pending', 'payment_method' => 'mtn_mobile_money', 'fulfillment_status' => 'pending_confirmation',
        ]);

        return PaymentTransaction::query()->create([
            'order_id' => $order->id, 'method' => 'mtn_mobile_money', 'phone' => '0781234567', 'amount' => 30000,
            'status' => $status, 'request_transaction_id' => 'TH_TEST_REF_1', 'transaction_id' => 'INTOUCH-9',
            'paid_at' => $status === 'completed' ? now() : null,
        ]);
    }

    public function test_admin_sees_payment_records_logs_and_wallet(): void
    {
        $this->fakeIntouch(125000);
        $transaction = $this->transaction();
        PaymentLog::record('request', $transaction, ['response_code' => '1000', 'message' => 'Pending approval']);
        PaymentLog::record('callback', null, ['request_transaction_id' => 'UNKNOWN_REF', 'message' => 'Transaction not found']);
        $admin = $this->user('administrator');

        $this->actingAs($admin, 'web')->get(route('marketplace.payments.index'))
            ->assertOk()
            ->assertSee('TH-TEST-0001')
            ->assertSee('Payer One')
            ->assertSee('RWF 30,000')
            ->assertSee('TH_TEST_REF_1');

        $this->get(route('marketplace.payments.logs'))
            ->assertOk()
            ->assertSee('Pending approval')
            ->assertSee('UNKNOWN_REF');
        $this->get(route('marketplace.payments.logs', ['unmatched' => 1]))
            ->assertSee('UNKNOWN_REF')
            ->assertDontSee('Pending approval');

        $this->get(route('marketplace.wallet.index'))
            ->assertOk()
            ->assertSee('RWF 125,000')
            ->assertSee('Send payout');
        $this->getJson(route('marketplace.wallet.balance'))
            ->assertOk()
            ->assertJson(['success' => true, 'balance' => 125000, 'formatted' => 'RWF 125,000']);
    }

    public function test_admin_can_send_a_payout(): void
    {
        $this->fakeIntouch(50000);
        $admin = $this->user('administrator');

        $this->actingAs($admin, 'web')->post(route('marketplace.wallet.payout'), [
            'phone' => '+250 781 234 567', 'amount' => 1500, 'reason' => 'Vendor settlement', 'confirm' => 1,
        ])->assertSessionHas('success', 'Sent RWF 1,500 to 0781234567.');

        $payout = Payout::query()->firstOrFail();
        $this->assertSame('successful', $payout->status);
        $this->assertSame('REF-1', $payout->reference_id);
        $this->assertSame($admin->id, $payout->user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payout.sent']);

        Http::assertSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/requestdeposit/')
            && $request['mobilephone'] === '250781234567'
            && (int) $request['amount'] === 1500
            && $request['reason'] === 'Vendor settlement'
            && $request['password'] === hash('sha256', 'testerSBX1secret'.$request['timestamp']));
    }

    public function test_payout_is_refused_above_balance_or_without_confirmation(): void
    {
        $this->fakeIntouch(1000);
        $admin = $this->user('administrator');

        $this->actingAs($admin, 'web')->post(route('marketplace.wallet.payout'), [
            'phone' => '0781234567', 'amount' => 5000, 'reason' => 'Too much', 'confirm' => 1,
        ])->assertSessionHasErrors('amount');
        $this->post(route('marketplace.wallet.payout'), [
            'phone' => '0781234567', 'amount' => 500, 'reason' => 'No confirm',
        ])->assertSessionHasErrors('confirm');
        $this->post(route('marketplace.wallet.payout'), [
            'phone' => '0123', 'amount' => 500, 'reason' => 'Bad phone', 'confirm' => 1,
        ])->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('payouts', 0);
        Http::assertNotSent(fn (HttpRequest $request) => str_ends_with($request->url(), '/requestdeposit/'));
    }

    public function test_failed_payout_is_recorded_and_reported(): void
    {
        $this->fakeIntouch(50000, ['success' => false, 'responsecode' => '1108', 'message' => 'Insufficient Account Balance']);
        $admin = $this->user('administrator');

        $this->actingAs($admin, 'web')->post(route('marketplace.wallet.payout'), [
            'phone' => '0781234567', 'amount' => 1000, 'reason' => 'Test', 'confirm' => 1,
        ])->assertSessionHasErrors(['payout' => 'Payout failed: Insufficient Account Balance']);

        $this->assertSame('failed', Payout::query()->firstOrFail()->status);
    }

    public function test_pending_payout_and_payment_can_be_rechecked(): void
    {
        $this->fakeIntouch(50000, ['success' => true, 'responsecode' => '1000', 'transactionid' => 'DEP-7'],
            ['success' => true, 'responsecode' => '01', 'status' => 'Successfull']);
        $admin = $this->user('administrator');

        $this->actingAs($admin, 'web')->post(route('marketplace.wallet.payout'), [
            'phone' => '0781234567', 'amount' => 1000, 'reason' => 'Test', 'confirm' => 1,
        ])->assertSessionHas('success', 'Payout submitted: RWF 1,000 to 0781234567.');
        $payout = Payout::query()->firstOrFail();
        $this->assertSame('pending', $payout->status);

        $this->post(route('marketplace.wallet.payout.refresh', $payout))->assertSessionHas('success', 'Payout status: Successful.');
        $this->assertSame('successful', $payout->fresh()->status);

        $transaction = $this->transaction('pending');
        $this->post(route('marketplace.payments.recheck', $transaction))->assertRedirect();
        $this->assertSame('completed', $transaction->fresh()->status);
        $this->assertSame('paid', $transaction->order->fresh()->payment_status);
    }

    public function test_staff_without_finance_permission_cannot_open_payments(): void
    {
        $this->fakeIntouch();
        $manager = $this->user('operations-manager');

        $this->actingAs($manager, 'web')->get(route('marketplace.payments.index'))->assertForbidden();
        $this->get(route('marketplace.payments.logs'))->assertForbidden();
        $this->get(route('marketplace.wallet.index'))->assertForbidden();
        $this->post(route('marketplace.wallet.payout'), ['phone' => '0781234567', 'amount' => 500, 'reason' => 'x', 'confirm' => 1])->assertForbidden();
    }

    public function test_finance_viewer_without_payout_permission_cannot_send_money(): void
    {
        $this->fakeIntouch();
        $viewer = $this->user('operations-manager');
        $viewer->directPermissions()->attach(Permission::query()->where('slug', 'finance.view')->value('id'));
        $viewer = $viewer->fresh();

        $this->actingAs($viewer, 'web')->get(route('marketplace.wallet.index'))
            ->assertOk()
            ->assertSee('sending payouts needs the')
            ->assertDontSee('id="payout-form"', false);
        $this->post(route('marketplace.wallet.payout'), ['phone' => '0781234567', 'amount' => 500, 'reason' => 'x', 'confirm' => 1])
            ->assertForbidden();
        $this->assertDatabaseCount('payouts', 0);
    }
}
