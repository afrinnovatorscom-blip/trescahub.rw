<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\DeliveryTask;
use App\Models\OrderStatusHistory;
use App\Models\ProductRequest;
use App\Models\ServiceRequest;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
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
        ])->assertRedirect(route('home'));

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

        $this->post(route('checkout.details'), [
            'customer_name' => 'Updated Recipient',
            'email' => 'updated@example.com',
            'phone' => '0788000111',
            'delivery_address' => 'Changed checkout address, Nyarugenge, Kigali',
            'delivery_notes' => 'Leave with reception.',
        ])->assertRedirect(route('checkout.payment'));

        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee('Use saved address')
            ->assertSee('Changed checkout address, Nyarugenge, Kigali')
            ->assertSee('Leave with reception.')
            ->assertDontSee('readonly data-saved-address');

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
            ->assertSee('Home')
            ->assertSee('My Orders')
            ->assertSee('Track your order')
            ->assertSee('Download Receipt')
            ->assertSee(route('account.orders.receipt', $order->reference), false)
            ->assertSee('Order progress')
            ->assertSee('aria-label="Order progress timeline"', false)
            ->assertSee('tabindex="0"', false)
            ->assertSee('Scroll horizontally to view all order stages.')
            ->assertSee('max-width: 991.98px')
            ->assertSee('grid-template-columns: repeat(6, minmax(120px, 1fr))')
            ->assertSee('Order summary')
            ->assertSee('Delivery details')
            ->assertSee('KG 10 Street, Kigali')
            ->assertSee('Order confirmed from persisted status.')
            ->assertSee('Accepted by vendor')
            ->assertSee('Confirm only after you have received your order.')
            ->assertDontSee('Confirm delivery');

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
            ->assertJsonPath('tracking.timeline.steps.0.label', 'Order received')
            ->assertJsonPath('tracking.timeline.steps.0.state', 'complete')
            ->assertJsonPath('tracking.timeline.steps.1.label', 'Order confirmed')
            ->assertJsonPath('tracking.timeline.steps.1.state', 'complete')
            ->assertJsonPath('tracking.timeline.steps.2.state', 'current')
            ->assertJsonFragment(['label' => 'Order confirmed from persisted status.']);

        $eventCount = count($trackingResponse->json('tracking.events'));
        $historyCount = OrderStatusHistory::query()->where('order_id', $order->id)->count();
        $this->getJson($trackingRoute)->assertOk()->assertJsonCount($eventCount, 'tracking.events');
        $this->assertSame($historyCount, OrderStatusHistory::query()->where('order_id', $order->id)->count());

        $this->actingAs($other, 'customers')->get(route('account.orders.show', $order->reference))->assertNotFound();
        $this->getJson($trackingRoute)->assertNotFound();
        $this->get(route('account.orders'))->assertDontSee($order->reference);
    }

    public function test_my_orders_keeps_horizontal_rows_and_shows_actions_for_each_order(): void
    {
        $customer = $this->customer();
        $order = $this->placeCustomerOrder($customer, 'KG 10 Street');
        $inTransit = DeliveryTask::query()->create([
            'reference' => 'DL-CUSTOMER-IN-TRANSIT',
            'order_id' => $order->id,
            'order_item_id' => $order->items()->value('id'),
            'status' => 'in_transit',
        ]);
        $anotherOrder = $this->placeCustomerOrder($customer, 'KN 4 Road');
        $anotherOrder->update(['payment_status' => 'paid']);
        $anotherOrder->payments()->create([
            'amount' => number_format((float) $anotherOrder->total, 2, '.', ''),
            'type' => 'full',
            'method' => 'bank_transfer',
            'submission_key' => (string) Str::uuid(),
        ]);
        $assigned = DeliveryTask::query()->create([
            'reference' => 'DL-CUSTOMER-ASSIGNED',
            'order_id' => $anotherOrder->id,
            'order_item_id' => $anotherOrder->items()->value('id'),
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($customer, 'customers')->get(route('account.orders'))
            ->assertOk()
            ->assertSee('account-order-row', false)
            ->assertSee($order->reference)
            ->assertSee($anotherOrder->reference)
            ->assertSee('Customer test product')
            ->assertSee('account-order-status-mobile', false)
            ->assertSee('account-order-status is-pending', false)
            ->assertSee('Pending Confirmation')
            ->assertSee('account-order-contact-mobile', false)
            ->assertSee('KG 10 Street')
            ->assertSee('max-width: 767.98px')
            ->assertSee('aria-label="More actions for order '.$order->reference.'"', false)
            ->assertDontSee('Payment</span>')
            ->assertDontSee('Cash on delivery')
            ->assertSee('In transit')
            ->assertSee('Delivery created')
            ->assertSee('Download Receipt')
            ->assertSee('<i class="bi bi-download" aria-hidden="true"></i> Download Receipt', false)
            ->assertDontSee('account-order-receipt', false)
            ->assertSee('Mark as delivered')
            ->assertSee('Track order')
            ->assertSee('View details')
            ->assertDontSee('Order received')
            ->assertDontSee('Order confirmed')
            ->assertSee(route('account.orders.show', $order->reference), false)
            ->assertSee(route('account.orders.receipt', $order->reference), false)
            ->assertSee(route('account.orders.receipt', $anotherOrder->reference), false)
            ->assertSee(route('account.orders.delivery-confirm', [
                'reference' => $order->reference,
                'deliveryReference' => $inTransit->reference,
            ]), false)
            ->assertDontSee(route('account.orders.delivery-confirm', [
                'reference' => $anotherOrder->reference,
                'deliveryReference' => $assigned->reference,
            ]), false);

        $this->assertSame(2, substr_count((string) $response->getContent(), 'data-bs-toggle="dropdown"'));
        $this->assertSame(2, substr_count((string) $response->getContent(), 'Track order'));
        $this->assertSame(2, substr_count((string) $response->getContent(), 'View details'));
    }

    public function test_customer_can_confirm_only_their_own_delivery_that_is_in_transit(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $order = $this->placeCustomerOrder($owner, 'KG 10 Street');
        $item = $order->items()->firstOrFail();
        $prematureOrder = $this->placeCustomerOrder($owner, 'KN 4 Road');
        $inTransit = DeliveryTask::query()->create([
            'reference' => 'DL-CUSTOMER-CONFIRM',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'status' => 'in_transit',
        ]);
        $assigned = DeliveryTask::query()->create([
            'reference' => 'DL-CUSTOMER-NOT-IN-TRANSIT',
            'order_id' => $prematureOrder->id,
            'status' => 'assigned',
        ]);
        $notDispatched = DeliveryTask::query()->create([
            'reference' => 'DL-CUSTOMER-NOT-DISPATCHED',
            'order_id' => $prematureOrder->id,
            'order_item_id' => $prematureOrder->items()->value('id'),
            'status' => 'in_transit',
            'dispatched_at' => now(),
        ]);

        $this->actingAs($owner, 'customers')
            ->post(route('account.orders.delivery-confirm', [
                'reference' => $prematureOrder->reference,
                'deliveryReference' => $assigned->reference,
            ]))
            ->assertNotFound();
        $this->assertSame('assigned', $assigned->fresh()->status);

        $this->actingAs($owner, 'customers')
            ->post(route('account.orders.delivery-confirm', [
                'reference' => $prematureOrder->reference,
                'deliveryReference' => $notDispatched->reference,
            ]))
            ->assertNotFound();
        $this->assertSame('in_transit', $notDispatched->fresh()->status);

        $this->actingAs($other, 'customers')
            ->post(route('account.orders.delivery-confirm', [
                'reference' => $order->reference,
                'deliveryReference' => $inTransit->reference,
            ]))
            ->assertNotFound();
        $this->assertSame('in_transit', $inTransit->fresh()->status);

        $order->update(['fulfillment_status' => 'dispatched']);
        $order->histories()->create([
            'from_status' => 'ready_for_dispatch',
            'to_status' => 'dispatched',
            'customer_message' => 'Your order has been dispatched.',
        ]);
        $trackingResponse = $this->actingAs($owner, 'customers')
            ->getJson(route('account.orders.tracking', $order->reference))
            ->assertOk()
            ->assertJsonPath('tracking.fulfillment_status', 'out_for_delivery')
            ->assertJsonPath('tracking.timeline.steps.3.state', 'complete')
            ->assertJsonPath('tracking.timeline.steps.4.state', 'current')
            ->assertJsonPath('tracking.deliveries.0.can_confirm', true);
        $this->assertNotNull($trackingResponse->json('tracking.timeline.steps.4.occurred_at'));
        $this->assertNull($inTransit->fresh()->dispatched_at);
        $this->get(route('account.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('Confirm delivery');

        $this->from(route('account.orders'))
            ->actingAs($owner, 'customers')
            ->post(route('account.orders.delivery-confirm', [
                'reference' => $order->reference,
                'deliveryReference' => $inTransit->reference,
            ]))
            ->assertRedirect(route('account.orders.show', $order->reference))
            ->assertSessionHas('success', 'Delivery marked as delivered.');

        $this->assertSame('delivered', $inTransit->fresh()->status);
        $this->assertNotNull($inTransit->fresh()->delivered_at);
        $this->assertSame('delivered', $item->fresh()->fulfillment_status);
        $this->assertSame('delivered', $order->fresh()->fulfillment_status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'dispatched',
            'to_status' => 'delivered',
        ]);
        $this->getJson(route('account.orders.tracking', $order->reference))
            ->assertOk()
            ->assertJsonPath('tracking.status_label', 'Delivered')
            ->assertJsonPath('tracking.timeline.steps.5.state', 'current')
            ->assertJsonPath('tracking.deliveries.0.status', 'delivered');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'delivery.completed',
            'auditable_type' => $inTransit->getMorphClass(),
            'auditable_id' => $inTransit->id,
        ]);
        $auditLog = AuditLog::query()
            ->where('action', 'delivery.completed')
            ->where('auditable_type', $inTransit->getMorphClass())
            ->where('auditable_id', $inTransit->id)
            ->firstOrFail();
        $this->assertSame('customer', $auditLog->meta['actor_type']);
        $this->assertSame($owner->id, (int) $auditLog->meta['actor_id']);

        $this->actingAs($owner, 'customers')
            ->post(route('account.orders.delivery-confirm', [
                'reference' => $order->reference,
                'deliveryReference' => $inTransit->reference,
            ]))
            ->assertNotFound();
        $this->assertSame(1, AuditLog::query()
            ->where('action', 'delivery.completed')
            ->where('auditable_type', $inTransit->getMorphClass())
            ->where('auditable_id', $inTransit->id)
            ->count());
    }

    public function test_customer_order_receipt_is_available_before_payment_and_uses_owned_order_data(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $order = $this->placeCustomerOrder($owner, 'KG 10 Street');

        $this->actingAs($owner, 'customers')
            ->get(route('account.orders.receipt', $order->reference))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=receipt-'.$order->reference.'.pdf');

        $order->items()->create([
            'product_name' => 'Second receipt item',
            'unit_price' => 250,
            'quantity' => 2,
            'line_total' => 500,
            'fulfillment_status' => 'processing',
            'assignment_status' => 'unassigned',
        ]);
        $order->update(['total' => (float) $order->total + 500]);
        $paymentStatus = $order->fresh()->payment_status;
        config(['app.url' => 'https://trescahub.rw']);

        $pdfContent = $this->get(route('account.orders.receipt', $order->reference))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=receipt-'.$order->reference.'.pdf')
            ->getContent();

        $generatedAt = now();
        $receiptHtml = view('front.pages.account-order-receipt', [
            'order' => $order->load('items'),
            'websiteUrl' => parse_url((string) config('app.url'), PHP_URL_HOST),
            'generatedAt' => $generatedAt,
        ])->render();
        $this->assertStringContainsString('Dear '.$owner->name.',', $receiptHtml);
        $this->assertStringContainsString('<div class="document-title">ORDER RECEIPT</div>', $receiptHtml);
        $this->assertStringNotContainsString('PAYMENT NOT CONFIRMED', $receiptHtml);
        $this->assertStringNotContainsString('does not confirm that payment was received', $receiptHtml);
        $this->assertStringContainsString('font-family: "Times New Roman", Times, serif;', $receiptHtml);
        $this->assertStringContainsString('Reference: '.$order->reference, $receiptHtml);
        $this->assertStringContainsString('<span class="brand-mark">T</span>', $receiptHtml);
        $this->assertStringContainsString('Order date', $receiptHtml);
        $this->assertStringContainsString($order->phone, $receiptHtml);
        $this->assertStringContainsString($order->delivery_address, $receiptHtml);
        $this->assertStringContainsString($order->reference, $receiptHtml);
        $this->assertStringContainsString('Customer test product', $receiptHtml);
        $this->assertStringContainsString('Second receipt item', $receiptHtml);
        $this->assertStringContainsString('Unit price', $receiptHtml);
        $this->assertStringContainsString('Subtotal', $receiptHtml);
        $this->assertStringContainsString('Delivery fee', $receiptHtml);
        $this->assertStringContainsString('Total order amount', $receiptHtml);
        $this->assertStringContainsString('Not separately recorded', $receiptHtml);
        $this->assertStringNotContainsString('PAYMENT RECEIPT', $receiptHtml);
        $this->assertStringNotContainsString('PAID', $receiptHtml);
        $this->assertStringContainsString('Generated on '.$generatedAt->format('M j, Y g:i A'), $receiptHtml);
        $this->assertStringContainsString('trescahub.rw', $receiptHtml);
        $this->assertStringContainsString('<th class="product">Product</th>', $receiptHtml);
        $this->assertStringContainsString('<th class="quantity">Qty</th>', $receiptHtml);
        $this->assertSame(1, substr_count($receiptHtml, '<table class="items">'));
        $this->assertStringNotContainsString('Recorded payments', $receiptHtml);
        $this->assertStringNotContainsString('<strong>Payment:</strong>', $receiptHtml);
        $this->assertStringNotContainsString('Transaction Reference', $receiptHtml);
        $this->assertSame($paymentStatus, $order->fresh()->payment_status);
        $this->assertSame(0, $order->payments()->count());

        $this->assertStringStartsWith('%PDF-', $pdfContent);
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $pdfContent));
        $this->assertSame(1, preg_match(
            '/\/MediaBox\s*\[\s*0(?:\.0+)?\s+0(?:\.0+)?\s+([\d.]+)\s+([\d.]+)\s*\]/',
            $pdfContent,
            $pageBox,
        ));
        $this->assertEqualsWithDelta(595.276, (float) $pageBox[1], 0.1);
        $this->assertEqualsWithDelta(841.89, (float) $pageBox[2], 0.1);

        $this->actingAs($other, 'customers')
            ->get(route('account.orders.receipt', $order->reference))
            ->assertNotFound();
    }

    public function test_order_receipt_is_available_for_every_payment_status_without_changing_payment_records(): void
    {
        $customer = $this->customer();
        $statuses = ['pending', 'partially_paid', 'failed', 'cancelled', 'refunded', 'unpaid', 'paid'];
        $orders = collect($statuses)->map(function (string $status) use ($customer) {
            $order = $this->placeCustomerOrder($customer, 'KG 10 Street');
            $order->update(['payment_status' => $status]);

            return $order;
        });
        $underpaid = $this->placeCustomerOrder($customer, 'KN 4 Road');
        $underpaid->update(['payment_status' => 'paid']);
        $underpaid->payments()->create([
            'amount' => number_format(max(0, (float) $underpaid->total - 1), 2, '.', ''),
            'type' => 'partial',
            'method' => 'bank_transfer',
            'submission_key' => (string) Str::uuid(),
        ]);
        $orders->push($underpaid);

        $response = $this->actingAs($customer, 'customers')->get(route('account.orders'))->assertOk();
        foreach ($orders as $order) {
            $response->assertSee(route('account.orders.receipt', $order->reference), false);
            $this->get(route('account.orders.receipt', $order->reference))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
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

    public function test_custom_inactivity_configuration_is_removed_and_normal_customer_sessions_remain_authenticated(): void
    {
        $customer = $this->customer();
        $this->assertArrayNotHasKey('customer_inactivity_timeout', config('session'));

        $this->actingAs($customer, 'customers')
            ->get(route('account.profile'))
            ->assertOk();

        $this->assertAuthenticatedAs($customer, 'customers');
    }

    public function test_vendor_and_administrator_sessions_do_not_use_custom_inactivity_tracking(): void
    {
        $vendor = Vendor::query()->create(['business_name' => 'Vendor Example', 'status' => 'active']);
        $vendorUser = User::factory()->create(['vendor_id' => $vendor->id]);
        $vendorUser->assignRole('vendor');
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('administrator');

        $this->actingAs($vendorUser, 'web')
            ->get(route('vendor.dashboard'));
        $this->assertAuthenticatedAs($vendorUser, 'web');

        $this->actingAs($admin, 'web')
            ->get(route('dashboard'));
        $this->assertAuthenticatedAs($admin, 'web');
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
