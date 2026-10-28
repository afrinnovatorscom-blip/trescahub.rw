<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_registration_screen_is_available_while_fortify_user_registration_is_disabled(): void
    {
        $this->assertFalse(Features::enabled(Features::registration()));

        $this->get('/register')
            ->assertOk()
            ->assertSee('Create an account')
            ->assertSee('name="phone"', false);
    }

    public function test_new_customer_registration_creates_a_customer_not_a_user(): void
    {
        $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'test-customer@example.com',
            'phone' => '+250 780 123 456',
            'password' => 'Valid-password-123',
            'password_confirmation' => 'Valid-password-123',
        ])->assertRedirect(route('account.orders'));

        $this->assertDatabaseHas('customers', ['email' => 'test-customer@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'test-customer@example.com']);
        $this->assertAuthenticatedAs(Customer::query()->where('email', 'test-customer@example.com')->firstOrFail(), 'customers');
        $this->assertGuest('web');
    }

    public function test_customer_registration_does_not_accept_a_users_table_email(): void
    {
        User::factory()->create(['email' => 'staff@example.com']);

        $this->from('/register')->post('/register', [
            'name' => 'Test Customer',
            'email' => 'staff@example.com',
            'phone' => '+250 780 123 456',
            'password' => 'Valid-password-123',
            'password_confirmation' => 'Valid-password-123',
        ])->assertRedirect('/register')->assertSessionHasErrors('email');
    }
}
