<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('auth-page-shell', false)
            ->assertDontSee('<header', false)
            ->assertDontSee('<footer', false)
            ->assertDontSee('public-login-trigger', false);
    }

    public function test_authorized_admin_can_authenticate_using_the_login_screen(): void
    {
        PermissionCatalog::sync();
        $user = User::factory()->create();
        $user->assignRole('administrator');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admin_login_is_not_blocked_by_an_existing_customer_session(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);
        $admin = User::factory()->create();

        $this->actingAs($customer, 'customers');

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertAuthenticatedAs($customer, 'customers');
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
