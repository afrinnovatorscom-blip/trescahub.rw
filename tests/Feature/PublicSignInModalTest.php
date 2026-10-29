<?php

namespace Tests\Feature;

use App\Livewire\PublicLoginModal;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicSignInModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_header_renders_the_customer_sign_in_modal_and_account_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sign In')
            ->assertSee('Welcome back')
            ->assertDontSee('Please sign in first to continue with your order.')
            ->assertSee(route('customer.password.request'), false)
            ->assertSee(route('register'), false)
            ->assertSee('Don\'t have an account?', false)
            ->assertSee('Create an account')
            ->assertDontSee('My Orders')
            ->assertDontSee('My Account')
            ->assertDontSee('Sign Out');
    }

    public function test_guests_are_sent_to_the_customer_sign_in_surface_for_account_pages(): void
    {
        $this->get(route('account.profile'))->assertRedirect(route('home'));
        $this->post(route('customer.logout'))->assertRedirect(route('home'));
    }

    public function test_authenticated_customer_header_shows_the_customer_initial_and_account_menu(): void
    {
        $customer = $this->customer([
            'name' => 'Fred Example',
            'email' => 'fred@example.com',
        ]);

        $response = $this->actingAs($customer, 'customers')->get(route('home'));

        $response
            ->assertOk()
            ->assertSee('aria-label="Open Fred Example account menu"', false)
            ->assertSee('Fred Example')
            ->assertSee('fred@example.com')
            ->assertSee('My Orders')
            ->assertSee(route('account.orders'), false)
            ->assertSee('My Account')
            ->assertSee(route('account.profile'), false)
            ->assertSee('Sign Out')
            ->assertSee(route('customer.logout'), false)
            ->assertSeeInOrder(['My Orders', 'My Account', 'Sign Out'])
            ->assertDontSee('public-login-trigger')
            ->assertDontSee('Welcome back');

        $this->assertMatchesRegularExpression(
            '/x-ref="accountTrigger"\s*>\s*F\s*<\/button>/',
            $response->getContent()
        );
    }

    public function test_authenticated_customer_header_uses_a_user_icon_when_name_is_empty(): void
    {
        $customer = $this->customer(['name' => '']);

        $this->actingAs($customer, 'customers')->get(route('home'))
            ->assertOk()
            ->assertSee('bi-person-fill', false)
            ->assertDontSee('>F</button>', false);
    }

    public function test_admin_and_vendor_web_sessions_do_not_appear_as_customer_accounts(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Example',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('administrator');

        $vendor = Vendor::query()->create([
            'business_name' => 'Vendor Example',
            'status' => 'active',
        ]);
        $vendorUser = User::factory()->create([
            'name' => 'Vendor Login',
            'email' => 'vendor@example.com',
            'vendor_id' => $vendor->id,
        ]);

        foreach ([$admin, $vendorUser] as $user) {
            $response = $this->actingAs($user, 'web')->get(route('home'));

            $response
                ->assertOk()
                ->assertSee('public-login-trigger')
                ->assertSee('Sign In')
                ->assertDontSee('My Orders')
                ->assertDontSee('My Account')
                ->assertDontSee('Open '.$user->name.' account menu')
                ->assertDontSee(route('customer.logout'), false);
        }
    }

    public function test_admin_and_vendor_web_sessions_can_open_customer_registration(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $vendor = Vendor::query()->create([
            'business_name' => 'Vendor Example',
            'status' => 'active',
        ]);
        $vendorUser = User::factory()->create(['vendor_id' => $vendor->id]);

        foreach ([$admin, $vendorUser] as $user) {
            $this->actingAs($user, 'web')
                ->get(route('register'))
                ->assertOk()
                ->assertSee('Create an account');
        }
    }

    public function test_staff_web_session_can_register_customer_without_being_logged_out(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $this->actingAs($admin, 'web')->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'new-customer@example.com',
            'phone' => '0788000000',
            'password' => 'Valid-password-123',
            'password_confirmation' => 'Valid-password-123',
        ])->assertRedirect(route('account.orders'));

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertAuthenticated('customers');
    }

    public function test_my_orders_lists_only_orders_owned_by_the_authenticated_customer(): void
    {
        $owner = $this->customer();
        $other = $this->customer();
        $ownerOrder = $this->orderFor($owner, 'TH-OWNER-ORDER');
        $otherOrder = $this->orderFor($other, 'TH-OTHER-ORDER');

        $this->actingAs($owner, 'customers')->get(route('account.orders'))
            ->assertOk()
            ->assertSee($ownerOrder->reference)
            ->assertSee('RWF 1,000')
            ->assertSee('Pending')
            ->assertSee('Bank transfer')
            ->assertDontSee($otherOrder->reference);

        $this->get(route('account.orders.show', $otherOrder->reference))->assertNotFound();
    }

    public function test_customer_logout_uses_a_post_route_and_ends_the_customer_session(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customers')
            ->post(route('customer.logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest('customers');
    }

    public function test_livewire_sign_in_authenticates_only_customers_and_redirects_home(): void
    {
        $customer = $this->customer([
            'email' => 'sign-in@example.com',
            'password' => 'valid-password',
        ]);

        Livewire::test(PublicLoginModal::class)
            ->set('email', 'sign-in@example.com')
            ->set('password', 'valid-password')
            ->call('login')
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($customer, 'customers');
        $this->assertGuest('web');
    }

    public function test_customer_login_works_without_ending_an_existing_staff_session(): void
    {
        $customer = $this->customer([
            'email' => 'sign-in-with-staff@example.com',
            'password' => 'valid-password',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $this->actingAs($admin, 'web');

        Livewire::test(PublicLoginModal::class)
            ->set('email', 'sign-in-with-staff@example.com')
            ->set('password', 'valid-password')
            ->call('login')
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($customer, 'customers');
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_invalid_customer_login_shows_validation_error_without_ending_staff_session(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $this->actingAs($admin, 'web');

        Livewire::test(PublicLoginModal::class)
            ->set('email', 'not-a-customer@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest('customers');
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_guest_order_routes_open_the_existing_modal_with_the_required_message(): void
    {
        $requestProduct = $this->get(route('product-requests.create'))
            ->assertRedirectContains('auth_required=1');
        $this->assertSame(route('shop').'?auth_required=1', $requestProduct->headers->get('Location'));
        $this->assertSame(route('product-requests.create'), session('customer.auth_redirect'));

        $checkout = $this->get(route('checkout'))
            ->assertRedirectContains('auth_required=1');
        $this->assertSame(route('checkout'), session('customer.auth_redirect'));
        $this->assertSame(route('cart').'?auth_required=1', $checkout->headers->get('Location'));

        $this->get($checkout->headers->get('Location'))
            ->assertOk()
            ->assertSee('Please sign in first to continue with your order.')
            ->assertSee('open: true', false);

        $response = $this->get(route('home').'?auth_required=1');

        $response
            ->assertOk()
            ->assertSee('Please sign in first to continue with your order.')
            ->assertSee('open: true', false);
    }

    public function test_guests_can_add_to_cart_but_must_sign_in_before_checkout(): void
    {
        $this->from('/shop/example-product')
            ->post(route('cart.add'), ['product' => 'example-product', 'quantity' => 1])
            ->assertRedirect('/shop/example-product')
            ->assertSessionMissing('customer.auth_redirect');

        $this->from('/shop/example-product')
            ->post(route('checkout.place'), [])
            ->assertRedirectContains('auth_required=1')
            ->assertSessionHas('url.intended', url('/shop/example-product'));

        $this->from('/shop/example-product')
            ->post(route('product-requests.store'), [])
            ->assertRedirectContains('auth_required=1')
            ->assertSessionHas('url.intended', url('/shop/example-product'));
    }

    public function test_customer_login_returns_to_the_intended_order_page(): void
    {
        $customer = $this->customer([
            'email' => 'intended-order@example.com',
            'password' => 'valid-password',
        ]);
        $intended = route('checkout').'?source=product';

        session(['customer.auth_redirect' => $intended]);

        Livewire::test(PublicLoginModal::class)
            ->set('email', $customer->email)
            ->set('password', 'valid-password')
            ->call('login')
            ->assertRedirect($intended);

        $this->assertNull(session('customer.auth_redirect'));
        $this->assertNull(session('url.intended'));

        $this->assertAuthenticatedAs($customer, 'customers');
    }

    public function test_customer_login_returns_to_the_intended_product_request_form(): void
    {
        $customer = $this->customer([
            'email' => 'intended-request@example.com',
            'password' => 'valid-password',
        ]);
        $intended = route('product-requests.create');

        session(['customer.auth_redirect' => $intended]);

        Livewire::test(PublicLoginModal::class)
            ->set('email', $customer->email)
            ->set('password', 'valid-password')
            ->call('login')
            ->assertRedirect($intended);

        $this->assertNull(session('customer.auth_redirect'));
        $this->assertAuthenticatedAs($customer, 'customers');
    }

    public function test_authenticated_customer_registration_page_redirects_to_existing_customer_account(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customers')
            ->get(route('register'))
            ->assertRedirect(route('account.orders'));

        $this->assertSame(1, Customer::query()->count());
    }

    public function test_user_table_credentials_cannot_authenticate_through_the_customer_login(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => 'staff-password',
        ]);
        $user->assignRole('customer');

        Livewire::test(PublicLoginModal::class)
            ->set('email', 'staff@example.com')
            ->set('password', 'staff-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest('customers');
        $this->post(route('login'), [
            'email' => 'staff@example.com',
            'password' => 'staff-password',
        ])->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_staff_login_still_authenticates_users_from_the_users_table(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => 'staff-password',
        ]);
        $user->assignRole('administrator');

        $this->post(route('login'), [
            'email' => 'staff@example.com',
            'password' => 'staff-password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_livewire_sign_in_validates_email_and_required_credentials(): void
    {
        Livewire::test(PublicLoginModal::class)
            ->set('email', 'not-an-email')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['email' => 'email', 'password' => 'required']);

        $this->assertGuest('customers');
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Customer Example',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '0788000000',
            'password' => 'valid-password',
        ], $attributes));
    }

    private function orderFor(Customer $customer, string $reference): Order
    {
        return Order::query()->create([
            'reference' => $reference,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'email' => $customer->email,
            'phone' => '0788000000',
            'delivery_address' => 'Kigali',
            'subtotal' => 1000,
            'total' => 1000,
            'payment_status' => 'pending',
            'payment_method' => 'bank_transfer',
            'fulfillment_status' => 'pending_confirmation',
        ]);
    }
}
