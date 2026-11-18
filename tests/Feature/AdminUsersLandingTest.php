<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);
    }

    public function test_default_users_tab_separates_staff_customers_and_vendor_accounts(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $vendor = User::query()->where('email', 'vendor@example.com')->firstOrFail();
        $customerRoleAccount = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $noOrderCustomer = Customer::query()->create([
            'name' => 'No Order Customer',
            'email' => 'no-order@example.com',
            'phone' => '0788111222',
            'password' => 'password',
        ]);

        $staffPage = $this->actingAs($admin)->get(route('marketplace.users.index'));
        $staffPage
            ->assertOk()
            ->assertSee('Manage system users, customers and access permissions.')
            ->assertSee('User Directory')
            ->assertSee('Administrator')
            ->assertSee('>9</strong>', false)
            ->assertSee('>7</strong>', false)
            ->assertSee('>2</strong>', false)
            ->assertSee('aria-selected="true"', false);
        preg_match('/<section id="users-panel-all".*?<\/section>/s', $staffPage->getContent(), $staffPanel);
        $this->assertStringNotContainsString('customer@example.com', $staffPanel[0] ?? '');
        $this->assertStringNotContainsString('vendor@example.com', $staffPanel[0] ?? '');
        $this->assertStringNotContainsString('Vendor</span>', $staffPanel[0] ?? '');

        $this->get(route('marketplace.users.index', ['tab' => 'customers']))
            ->assertOk()
            ->assertSee('Customer Directory')
            ->assertSee('Demo Customer')
            ->assertSee('No Order Customer');

        $this->assertSame('vendor', $vendor->fresh()->roles()->firstOrFail()->slug);
        $this->assertSame('customer', $customerRoleAccount->fresh()->roles()->firstOrFail()->slug);
        $this->assertDatabaseHas('customers', ['id' => $noOrderCustomer->id]);
    }

    public function test_search_and_role_filter_apply_independently_to_each_directory(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        Customer::query()->create([
            'name' => 'Searchable Customer',
            'email' => 'searchable@example.com',
            'phone' => '0788999000',
            'password' => 'password',
        ]);

        $staffSearch = $this->actingAs($admin)->get(route('marketplace.users.index', [
            'tab' => 'all',
            'staff_search' => '0788001003',
            'staff_role' => Role::query()->where('slug', 'operations-manager')->value('id'),
        ]));
        $staffSearch
            ->assertOk()
            ->assertSee('Operations Manager')
            ->assertDontSee('catalog@example.com');

        $this->get(route('marketplace.users.index', [
            'tab' => 'customers',
            'customer_search' => '0788999000',
        ]))
            ->assertOk()
            ->assertSee('Searchable Customer')
            ->assertDontSee('Demo Customer');
    }

    public function test_manage_access_updates_staff_roles_without_exposing_or_changing_vendor_role(): void
    {
        $admin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $staff = User::query()->where('email', 'catalog@example.com')->firstOrFail();
        $requestOfficerRole = Role::query()->where('slug', 'request-officer')->firstOrFail();
        $vendor = User::query()->where('email', 'vendor@example.com')->firstOrFail();

        $this->actingAs($admin)->get(route('marketplace.users.index'))
            ->assertOk()
            ->assertDontSee('name="roles[]" value="'.$vendor->roles()->firstOrFail()->id.'"', false);
        $this->put(route('marketplace.users.access', $vendor), ['roles' => []])->assertForbidden();

        $this->put(route('marketplace.users.access', $staff), [
            'roles' => [$requestOfficerRole->id],
        ])->assertRedirect(route('marketplace.users.index', ['tab' => 'all']))
            ->assertSessionHas('success', 'User access updated.');

        $this->assertTrue($staff->fresh()->hasRole('request-officer'));
        $this->assertFalse($staff->fresh()->hasRole('product-manager'));
        $this->assertSame($vendor->vendor_id, $vendor->fresh()->vendor_id);
        $this->assertTrue($vendor->fresh()->hasRole('vendor'));
    }

    public function test_directory_paginates_staff_and_customers_independently(): void
    {
        $admin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $roleId = Role::query()->where('slug', 'operations-manager')->value('id');

        for ($index = 1; $index <= 21; $index++) {
            $user = User::factory()->create(['name' => 'Directory Staff '.$index]);
            $user->roles()->sync([$roleId]);

            Customer::query()->create([
                'name' => 'Directory Customer '.$index,
                'email' => 'directory-customer-'.$index.'@example.com',
                'phone' => '078800'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'password' => 'password',
            ]);
        }

        $staffResponse = $this->actingAs($admin)->get(route('marketplace.users.index'));
        $staffResponse->assertOk()->assertSee('Page 1 of 2')->assertSee('rel="next"', false);

        $customerResponse = $this->get(route('marketplace.users.index', ['tab' => 'customers']));
        $customerResponse->assertOk()->assertSee('Page 1 of 2')->assertSee('rel="next"', false);
        $customerResponse->assertSee('Directory Customer 1');
    }
}
