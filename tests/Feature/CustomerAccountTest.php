<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryTask;
use App\Models\OrderStatusHistory;
use App\Models\ProductRequest;
use App\Models\ServiceRequest;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Notifications\CustomerPasswordResetNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_customer_identity_without_creating_a_user(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('TRESCA')
            ->assertSee('Hub')
            ->assertSee('Phone Number')
            ->assertSee('name="phone"', false);

        $this->post(route('register.store'), [
            'name' => 'Customer Example',
            'email' => 'customer@example.com',
            'phone' => '+250 780 123 456',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ])->assertRedirect(route('account.orders'));

        $customer = Customer::query()->where('email', 'customer@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($customer, 'customers');
        $this->assertSame('+250 780 123 456', $customer->phone);
        $this->assertTrue(Hash::check('Strong-password-123', $customer->password));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_customer_registration_requires_a_phone_number_and_rejects_staff_emails(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Customer Example',
            'email' => 'customer@example.com',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ])->assertRedirect(route('register'))->assertSessionHasErrors('phone');

        $staff = User::factory()->create(['email' => 'staff@example.com']);
        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Customer Example',
            'email' => $staff->email,
            'phone' => '0788000000',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ])->assertRedirect(route('register'))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_customer_can_manage_default_addresses_and_cannot_change_another_customers_address(): void
    {
        $customer = $this->customer(['phone' => '0788000001']);
        $other = $this->customer(['phone' => '0788000002']);

        $this->actingAs($customer, 'customers')->get(route('account.profile'))
            ->assertOk()
            ->assertSeeInOrder(['Personal Details', 'Address', 'Change Password'])
            ->assertSee('Manage your personal information, delivery address, and account security.')
            ->assertSee('Save Changes')
            ->assertSee('Save Address')
            ->assertSee('Update Password');

        $accountPage = $this->get(route('account.profile'))->assertOk();
        $this->assertSame(1, substr_count($accountPage->getContent(), '<h2 class="h5 fw-bold mb-1">Address</h2>'));
        $this->assertSame(3, substr_count($accountPage->getContent(), '<section class="card'));
        $this->assertSingleAddressForm($accountPage);

        $this->put(route('account.profile.update'), [
            'name' => 'Updated Customer',
            'email' => 'updated-customer@example.com',
            'phone' => '0788000011',
        ])->assertRedirect();

        $customer->refresh();
        $this->assertSame('Updated Customer', $customer->name);
        $this->assertSame('updated-customer@example.com', $customer->email);
        $this->assertSame('0788000011', $customer->phone);

        $this->post(route('account.addresses.store'), [
            'label' => 'Home',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'recipient_email' => $customer->email,
            'address_line' => 'KG 1 Avenue',
            'district' => 'Gasabo',
            'city' => 'Kigali',
        ])->assertRedirect();

        $savedPage = $this->get(route('account.profile'))->assertOk();
        $this->assertSame(1, substr_count($savedPage->getContent(), '<h2 class="h5 fw-bold mb-1">Address</h2>'));
        $this->assertSame(3, substr_count($savedPage->getContent(), '<section class="card'));
        $this->assertSingleAddressForm($savedPage);

        $address = $customer->addresses()->firstOrFail();
        $this->assertTrue($address->is_default);

        $this->post(route('account.addresses.store'), [
            'label' => 'Office',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'address_line' => 'KN 3 Road',
            'is_default' => '1',
        ])->assertRedirect();

        $this->assertFalse($address->fresh()->is_default);
        $this->assertSame(1, $customer->addresses()->where('is_default', true)->count());

        $office = $customer->addresses()->where('label', 'Office')->firstOrFail();
        $this->put(route('account.addresses.update', $office), [
            'label' => 'Office',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'address_line' => 'KN 3 Road updated',
            'is_default' => '1',
        ])->assertRedirect();
        $this->assertSame('KN 3 Road updated', $office->fresh()->address_line);

        $reloadedPage = $this->get(route('account.profile'))->assertOk()->assertSee('KN 3 Road updated');
        $this->assertSame(1, substr_count($reloadedPage->getContent(), '<h2 class="h5 fw-bold mb-1">Address</h2>'));
        $this->assertSingleAddressForm($reloadedPage);

        $this->actingAs($other, 'customers')->put(route('account.addresses.update', $address), [
            'label' => 'Changed',
            'recipient_name' => $other->name,
            'recipient_phone' => $other->phone,
            'address_line' => 'Unauthorized change',
        ])->assertNotFound();

        $this->assertSame('KG 1 Avenue', $address->fresh()->address_line);
    }

    public function test_address_updates_do_not_change_existing_order_snapshots(): void
    {
        $customer = $this->customer(['phone' => '0788000001']);
        $order = $this->placeCustomerOrder($customer, 'Historical order address');

        $this->actingAs($customer, 'customers')->post(route('account.addresses.store'), [
            'label' => 'Home',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'address_line' => 'Old saved address',
        ])->assertRedirect();

        $address = $customer->addresses()->firstOrFail();
        $this->put(route('account.addresses.update', $address), [
            'label' => 'Home',
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
            'address_line' => 'New saved address',
            'is_default' => '1',
        ])->assertRedirect();

        $this->assertSame('Historical order address', $order->fresh()->delivery_address);
    }

    public function test_customer_email_may_remain_unchanged_but_cannot_be_taken_from_another_account(): void
    {
        $customer = $this->customer(['email' => 'owner@example.com']);
        $other = $this->customer(['email' => 'other@example.com']);
        User::factory()->create(['email' => 'staff@example.com']);

        $this->actingAs($customer, 'customers');
        $this->put(route('account.profile.update'), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ])->assertRedirect()->assertSessionHas('success');

        $this->from(route('account.profile'))->put(route('account.profile.update'), [
            'name' => 'Attempted change',
            'email' => $other->email,
            'phone' => $customer->phone,
        ])->assertRedirect(route('account.profile'))->assertSessionHasErrors('email');

        $this->from(route('account.profile'))->put(route('account.profile.update'), [
            'name' => 'Attempted change',
            'email' => 'staff@example.com',
            'phone' => $customer->phone,
        ])->assertRedirect(route('account.profile'))->assertSessionHasErrors('email');

        $this->assertSame('owner@example.com', $customer->fresh()->email);
        $this->assertSame($customer->name, $customer->fresh()->name);
    }

    public function test_customer_can_change_password_only_after_current_password_is_verified(): void
    {
        $customer = $this->customer(['password' => 'Current-password-123']);
        $payload = [
            'current_password' => 'wrong-password',
            'password' => 'New-password-456',
            'password_confirmation' => 'New-password-456',
        ];

        $this->actingAs($customer, 'customers')->from(route('account.profile'))
            ->put(route('account.password.update'), $payload)
            ->assertRedirect(route('account.profile'))
            ->assertSessionHasErrors('current_password', null, 'updatePassword');

        $this->assertTrue(Hash::check('Current-password-123', $customer->fresh()->password));

        $payload['current_password'] = 'Current-password-123';
        $payload['password_confirmation'] = 'different-password';
        $this->put(route('account.password.update'), $payload)
            ->assertRedirect(route('account.profile'))
            ->assertSessionHasErrors('password', null, 'updatePassword');

        $this->assertTrue(Hash::check('Current-password-123', $customer->fresh()->password));

        $payload['password_confirmation'] = 'New-password-456';
        $this->put(route('account.password.update'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'Your password has been updated.');

        $this->assertTrue(Hash::check('New-password-456', $customer->fresh()->password));
    }

    public function test_saved_customer_address_prefills_checkout_and_product_request_forms(): void
    {
        $customer = $this->customer([
            'name' => 'Saved Customer',
            'email' => 'saved@example.com',
            'phone' => '0788111222',
        ]);
        $customer->addresses()->create([
            'label' => 'Home',
            'recipient_name' => 'Recipient Name',
            'recipient_phone' => '0788999000',
            'recipient_email' => 'recipient@example.com',
            'address_line' => 'KG 10 Street',
            'district' => 'Kicukiro',
            'city' => 'Kigali',
            'delivery_instructions' => 'Call on arrival',
            'is_default' => true,
        ]);
        $product = $this->product();

        $this->actingAs($customer, 'customers')
            ->withSession(['marketplace.cart' => [$product->slug => 1]])
            ->get(route('checkout'))
            ->assertOk()
            ->assertSee('Recipient Name')
            ->assertSee('0788999000')
            ->assertSee('recipient@example.com')
            ->assertSee('KG 10 Street, Kicukiro, Kigali')
            ->assertSee('Call on arrival');

        $this->get(route('product-requests.create'))
            ->assertOk()
            ->assertSee('Recipient Name')
            ->assertSee('0788999000')
            ->assertSee('KG 10 Street, Kicukiro, Kigali');
    }

    public function test_customer_service_and_product_requests_use_customer_id_and_guest_requests_remain_unlinked(): void
    {
        $customer = $this->customer(['phone' => '0788111222']);
        $payload = [
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'description' => 'Please provide a service quote.',
        ];

        $this->actingAs($customer, 'customers')->post(route('request-service.store'), $payload)
            ->assertRedirect(route('request-service'));

        $this->assertDatabaseHas('service_requests', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
        ]);
        $this->assertSame(1, $customer->serviceRequests()->count());

        $this->post(route('product-requests.store'), [
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'quantity' => 1,
            'product_name' => 'Custom product',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_requests', [
            'customer_id' => $customer->id,
            'product_name' => 'Custom product',
        ]);
        $this->assertSame(1, $customer->productRequests()->count());

        Auth::guard('customers')->logout();
        $this->post(route('request-service.store'), [
            'name' => 'Guest Customer',
            'phone' => '0788000000',
            'description' => 'Guest request.',
        ])->assertRedirect(route('request-service'));

        $this->assertDatabaseHas('service_requests', [
            'user_id' => null,
            'customer_id' => null,
            'name' => 'Guest Customer',
        ]);
    }

    public function test_customer_request_list_only_shows_the_authenticated_customers_records(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        ServiceRequest::query()->create([
            'customer_id' => $owner->id,
            'name' => $owner->name,
            'phone' => '0788111222',
            'description' => 'Private service request',
        ]);
        ServiceRequest::query()->create([
            'customer_id' => $other->id,
            'name' => $other->name,
            'phone' => '0788333444',
            'description' => 'Another customer private request',
        ]);
        ProductRequest::query()->create([
            'reference' => 'PR-CUSTOMER-ONE',
            'customer_id' => $owner->id,
            'customer_name' => $owner->name,
            'phone' => $owner->phone,
            'product_name' => 'Owner request',
            'quantity' => 1,
            'status' => 'submitted',
        ]);

        $this->actingAs($owner, 'customers')->get(route('account.requests'))
            ->assertOk()
            ->assertSee('Private service request')
            ->assertSee('Owner request')
            ->assertDontSee('Another customer private request');
    }

    public function test_new_customer_orders_are_owned_and_guest_orders_remain_unlinked(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $order = $this->placeCustomerOrder($customer, 'KG 10 Street');
        $guestOrder = app(OrderService::class)->place([
            'name' => 'Guest',
            'phone' => '0788333444',
            'delivery_address' => 'KN 4 Road',
            'payment_method' => 'cash_on_delivery',
        ], [['product' => $product->fresh(), 'quantity' => 1]], null);

        $this->assertSame($customer->id, $order->customer_id);
        $this->assertNull($order->user_id);
        $this->assertSame(1, $customer->orders()->count());
        $this->assertNull($guestOrder->customer_id);
        $this->assertNull($guestOrder->user_id);
    }

    public function test_customer_cancellation_is_scoped_to_the_owning_customer(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $order = $this->placeCustomerOrder($owner, 'KG 10 Street');

        $this->actingAs($owner, 'customers')->post(route('account.orders.cancel', $order->reference), [
            'reason' => 'I no longer need this order.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('cancellation_requested', $order->fresh()->fulfillment_status);

        $otherOrder = $this->placeCustomerOrder($other, 'KN 4 Road');
        $this->actingAs($owner, 'customers')->post(route('account.orders.cancel', $otherOrder->reference), [
            'reason' => 'Unauthorized cancellation.',
        ])->assertNotFound();

        $this->assertSame('pending_confirmation', $otherOrder->fresh()->fulfillment_status);
    }

    public function test_order_history_tracking_and_access_are_scoped_to_the_customer(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $product = $this->product();
        $order = $this->placeCustomerOrder($owner, 'KG 10 Street, Kigali', $product, 2);
        $otherOrder = $this->placeCustomerOrder($other, 'Another address');

        $order->update([
            'fulfillment_status' => 'confirmed',
            'payment_status' => 'partially_paid',
            'customer_status_message' => 'Order confirmed from persisted status.',
        ]);
        $item = $order->items()->firstOrFail();
        $item->update([
            'fulfillment_status' => 'processing',
            'assignment_status' => 'accepted',
        ]);
        $order->histories()->create([
            'user_id' => null,
            'from_status' => 'pending_confirmation',
            'to_status' => 'confirmed',
            'customer_message' => 'Order confirmed from persisted status.',
        ]);
        $order->payments()->create([
            'amount' => '250.00',
            'type' => 'partial',
            'method' => 'bank_transfer',
            'submission_key' => (string) Str::uuid(),
        ]);
        DeliveryTask::query()->create([
            'reference' => 'DL-CUSTOMER-TRACK',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($owner, 'customers')->get(route('account.orders'))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee($product->product_name)
            ->assertSee('RWF')
            ->assertDontSee($otherOrder->reference);

        $this->get(route('account.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('KG 10 Street, Kigali')
            ->assertSee('Order confirmed from persisted status.')
            ->assertSee('Accepted by vendor');

        $trackingRoute = route('account.orders.tracking', $order->reference);
        $trackingResponse = $this->getJson($trackingRoute)
            ->assertOk()
            ->assertJsonPath('tracking.reference', $order->reference)
            ->assertJsonPath('tracking.payment_status', 'partially_paid')
            ->assertJsonPath('tracking.currency', 'RWF')
            ->assertJsonPath('tracking.items.0.name', $product->product_name)
            ->assertJsonPath('tracking.items.0.quantity', 2)
            ->assertJsonPath('tracking.items.0.assignment_label', 'Accepted by vendor')
            ->assertJsonPath('tracking.deliveries.0.reference', 'DL-CUSTOMER-TRACK')
            ->assertJsonFragment(['label' => 'Order confirmed from persisted status.']);

        $eventCount = count($trackingResponse->json('tracking.events'));
        $historyCount = OrderStatusHistory::query()->where('order_id', $order->id)->count();
        $this->getJson($trackingRoute)->assertOk()->assertJsonCount($eventCount, 'tracking.events');
        $this->assertSame($historyCount, OrderStatusHistory::query()->where('order_id', $order->id)->count());

        $this->actingAs($other, 'customers')->get(route('account.orders.show', $order->reference))->assertNotFound();
        $this->getJson($trackingRoute)->assertNotFound();
        $this->get(route('account.orders'))->assertDontSee($order->reference);
    }

    public function test_completed_order_without_delivery_confirmation_is_not_reported_as_delivered(): void
    {
        $customer = $this->customer();
        $order = $this->placeCustomerOrder($customer, 'KG 10 Street');
        $order->update(['fulfillment_status' => 'completed']);

        $this->actingAs($customer, 'customers')->get(route('account.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Delivery record created')
            ->assertDontSee('Delivered</span>');
    }

    public function test_customer_password_reset_uses_its_own_broker_and_token_table(): void
    {
        Notification::fake();
        $customer = $this->customer(['email' => 'reset@example.com']);

        $this->post(route('customer.password.email'), ['email' => $customer->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($customer, CustomerPasswordResetNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $this->assertNotNull($token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $customer->email]);

        $this->post(route('customer.password.update'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'Reset-password-456',
            'password_confirmation' => 'Reset-password-456',
        ])->assertRedirect(route('home'))->assertSessionHas('status', 'Your password has been reset. You can now sign in.');

        $this->assertTrue(Hash::check('Reset-password-456', $customer->fresh()->password));
        $this->assertDatabaseMissing('customer_password_reset_tokens', ['email' => $customer->email]);
    }

    private function assertSingleAddressForm(TestResponse $response): void
    {
        $html = $response->getContent();
        preg_match_all('/<form\b[^>]*\bdata-address-form\b/s', $html, $forms);
        preg_match_all('/<button\b[^>]*\bdata-address-save\b/s', $html, $saveButtons);
        $this->assertCount(1, $forms[0]);
        $this->assertSame(1, substr_count($html, 'name="address_line"'));
        $this->assertCount(1, $saveButtons[0]);
        $this->assertSame(1, substr_count($html, '>Save Address</button>'));
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Customer '.Str::random(6),
            'email' => Str::lower(Str::random(8)).'@example.com',
            'phone' => '0788000000',
            'password' => 'valid-password',
        ], $attributes));
    }

    private function placeCustomerOrder(Customer $customer, string $address, ?ShopProduct $product = null, int $quantity = 1): \App\Models\Order
    {
        return app(OrderService::class)->place([
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'delivery_address' => $address,
            'payment_method' => 'cash_on_delivery',
        ], [['product' => $product ?? $this->product(), 'quantity' => $quantity]], null, $customer);
    }

    private function product(): ShopProduct
    {
        $category = ShopCategory::query()->create([
            'name' => 'Customer test category',
            'slug' => 'customer-test-category-'.Str::random(8),
        ]);

        return ShopProduct::query()->create([
            'product_name' => 'Customer test product',
            'slug' => 'customer-test-product-'.Str::random(8),
            'status' => 'in_stock',
            'price' => 1000,
            'shop_category_id' => $category->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);
    }
}
