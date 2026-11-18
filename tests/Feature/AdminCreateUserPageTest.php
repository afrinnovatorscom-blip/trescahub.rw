<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCreateUserPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);
    }

    public function test_create_user_form_has_three_sections_and_only_the_seven_allowed_roles(): void
    {
        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $response = $this->actingAs($superAdmin)->get(route('marketplace.users.create'));

        $response->assertOk()
            ->assertSee('Users')
            ->assertSee('Create New User')
            ->assertSee('Add a new account and configure its system access.')
            ->assertSee('01. Personal Information')
            ->assertSee('02. Account Security')
            ->assertSee('03. Assign Roles')
            ->assertSee('Administrator')
            ->assertSee('Customer')
            ->assertSee('Delivery Officer')
            ->assertSee('Inventory Officer')
            ->assertSee('Operations Manager')
            ->assertSee('Product Manager')
            ->assertSee('Request and Order Officer')
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);

        $this->assertSame(3, substr_count($response->getContent(), '<section class="create-user-section"'));
        $this->assertSame(7, substr_count($response->getContent(), 'class="create-user-role-card"'));
        $this->assertStringNotContainsString('name="roles[]" value="'.Role::query()->where('slug', 'vendor')->value('id').'"', $response->getContent());
        $this->assertStringNotContainsString('name="roles[]" value="'.Role::query()->where('slug', 'super-administrator')->value('id').'"', $response->getContent());
        $this->assertStringNotContainsString('name="vendor_id"', $response->getContent());
    }

    public function test_existing_user_creation_flow_saves_multiple_roles_and_hashes_password(): void
    {
        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $administratorRole = Role::query()->where('slug', 'administrator')->firstOrFail();
        $deliveryRole = Role::query()->where('slug', 'delivery-officer')->firstOrFail();

        $this->actingAs($superAdmin)->post(route('marketplace.users.store'), [
            'name' => 'New Staff Member',
            'email' => 'new-staff@example.com',
            'phone' => '0788111222',
            'password' => 'SecurePassword2026!',
            'password_confirmation' => 'SecurePassword2026!',
            'roles' => [$administratorRole->id, $deliveryRole->id],
        ])->assertRedirect(route('marketplace.users.index'))
            ->assertSessionHas('success', 'User created.');

        $created = User::query()->where('email', 'new-staff@example.com')->firstOrFail();
        $this->assertSame('New Staff Member', $created->name);
        $this->assertSame('0788111222', $created->phone);
        $this->assertTrue(Hash::check('SecurePassword2026!', $created->password));
        $this->assertTrue($created->hasRole('administrator'));
        $this->assertTrue($created->hasRole('delivery-officer'));
        $this->assertNull($created->vendor_id);
    }

    public function test_existing_password_confirmation_validation_is_preserved(): void
    {
        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();

        $this->actingAs($superAdmin)->from(route('marketplace.users.create'))->post(route('marketplace.users.store'), [
            'name' => 'Invalid Password User',
            'email' => 'invalid-password@example.com',
            'password' => 'SecurePassword2026!',
            'password_confirmation' => 'DifferentPassword2026!',
            'roles' => [],
        ])->assertRedirect(route('marketplace.users.create'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'invalid-password@example.com']);
    }
}
