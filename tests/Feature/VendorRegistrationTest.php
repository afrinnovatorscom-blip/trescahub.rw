<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $logoPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MarketplaceUserSeeder::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->logoPaths as $path) {
            File::delete(public_path('assets/'.$path));
        }

        parent::tearDown();
    }

    public function test_admin_registers_a_pending_vendor_with_a_login_account_and_partner_defaults(): void
    {
        $response = $this->actingAs($this->administrator())->post(route('marketplace.vendors.store'), $this->vendorInput([
            'public_branding_approved' => '1',
            'partner_display_status' => 'published',
        ]));

        $vendor = Vendor::query()->where('email', 'new-vendor@example.com')->firstOrFail();
        $user = User::query()->where('email', 'new-vendor@example.com')->firstOrFail();

        $response->assertRedirect(route('marketplace.vendors.show', $vendor));
        $this->assertSame($vendor->id, $user->vendor_id);
        $this->assertSame('pending', $vendor->status);
        $this->assertFalse($vendor->show_as_partner);
        $this->assertSame('draft', $vendor->partner_display_status);
        $this->assertSame(0, $vendor->display_order);
        $this->assertFalse($vendor->public_branding_approved);
        $this->assertTrue(Hash::check('VendorPassword2026!', $user->password));
        $this->assertTrue($user->hasRole('vendor'));
        $this->assertSame(['vendor'], $user->roles()->pluck('slug')->all());
        $this->assertFalse($user->hasPermission('admin.access'));

        Auth::guard('web')->logout();
        $this->post('/login', [
            'email' => 'new-vendor@example.com',
            'password' => 'VendorPassword2026!',
        ])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user, 'web');
        $this->get(route('vendor.dashboard'))->assertForbidden();
        $this->assertSame('https://example.com', $vendor->fresh()->website_url);
    }

    public function test_vendor_registration_rejects_duplicate_login_emails_and_weak_passwords(): void
    {
        $this->actingAs($this->administrator())
            ->from(route('marketplace.vendors.create'))
            ->post(route('marketplace.vendors.store'), $this->vendorInput([
                'email' => 'admin@example.com',
            ]))
            ->assertRedirect(route('marketplace.vendors.create'))
            ->assertSessionHasErrors('email');

        $this->actingAs($this->administrator())
            ->from(route('marketplace.vendors.create'))
            ->post(route('marketplace.vendors.store'), $this->vendorInput([
                'email' => 'another-vendor@example.com',
                'password' => 'short',
            ]))
            ->assertRedirect(route('marketplace.vendors.create'))
            ->assertSessionHasErrors('password');
    }

    public function test_editing_vendor_updates_linked_login_email_without_changing_password(): void
    {
        $vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();
        $account = User::query()->where('email', 'vendor@example.com')->firstOrFail();
        $originalPassword = $account->password;

        $this->actingAs($this->administrator())
            ->put(route('marketplace.vendors.update', $vendor), $this->vendorInput([
                'business_name' => 'Updated Vendor Business',
                'email' => 'updated-vendor@example.com',
                'show_as_partner' => '1',
                'partner_description' => 'A concise partner profile.',
                'display_order' => '3',
            ]))
            ->assertRedirect(route('marketplace.vendors.show', $vendor));

        $this->assertSame('updated-vendor@example.com', $vendor->fresh()->email);
        $this->assertSame('updated-vendor@example.com', $account->fresh()->email);
        $this->assertSame('Updated Vendor Business', $account->fresh()->name);
        $this->assertSame($originalPassword, $account->fresh()->password);
        $this->assertTrue($vendor->fresh()->show_as_partner);
        $this->assertSame('A concise partner profile.', $vendor->fresh()->partner_description);
        $this->assertSame(3, $vendor->fresh()->display_order);
        $this->assertSame('draft', $vendor->fresh()->partner_display_status);
        $this->assertFalse($vendor->fresh()->public_branding_approved);
    }

    public function test_logo_is_previewed_stored_and_replaced_without_leaving_the_old_file(): void
    {
        $this->actingAs($this->administrator())->post(
            route('marketplace.vendors.store'),
            $this->vendorInput(['business_logo' => $this->fakeLogo('first-logo.png')])
        )->assertSessionHasNoErrors();

        $vendor = Vendor::query()->where('email', 'new-vendor@example.com')->firstOrFail();
        $oldLogo = $vendor->business_logo_path;
        $this->logoPaths[] = $oldLogo;
        $this->assertFileExists(public_path('assets/'.$oldLogo));
        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.edit', $vendor))
            ->assertOk()
            ->assertSee(asset('assets/'.$oldLogo));

        $this->put(route('marketplace.vendors.update', $vendor), $this->vendorInput([
            'email' => $vendor->email,
            'business_logo' => $this->fakeLogo('second-logo.png'),
        ]))->assertSessionHasNoErrors();

        $newLogo = $vendor->fresh()->business_logo_path;
        $this->logoPaths[] = $newLogo;
        $this->assertNotSame($oldLogo, $newLogo);
        $this->assertFileDoesNotExist(public_path('assets/'.$oldLogo));
        $this->assertFileExists(public_path('assets/'.$newLogo));
    }

    public function test_users_without_vendor_creation_permission_cannot_register_vendors(): void
    {
        $this->actingAs(User::query()->where('email', 'customer@example.com')->firstOrFail())
            ->post(route('marketplace.vendors.store'), $this->vendorInput())
            ->assertForbidden();

        $this->assertDatabaseMissing('vendors', ['email' => 'new-vendor@example.com']);
    }

    private function administrator(): User
    {
        return User::query()->where('email', 'admin@example.com')->firstOrFail();
    }

    private function fakeLogo(string $name): UploadedFile
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC');

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function vendorInput(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'New Vendor Business',
            'legal_name' => 'New Vendor Ltd',
            'reference_number' => 'NEW-VENDOR-001',
            'email' => 'new-vendor@example.com',
            'phone' => '0788001234',
            'status' => 'pending',
            'district' => 'Gasabo',
            'operating_location' => 'Kigali',
            'website_url' => 'https://example.com',
            'address_notes' => 'Near the main road.',
            'show_as_partner' => '0',
            'partner_description' => '',
            'display_order' => '0',
            'partner_display_status' => 'draft',
            'internal_notes' => 'Internal note.',
            'password' => 'VendorPassword2026!',
        ], $overrides);
    }
}
