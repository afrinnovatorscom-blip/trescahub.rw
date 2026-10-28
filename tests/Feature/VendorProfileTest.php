<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorProfileTest extends TestCase
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

    public function test_vendor_can_edit_shared_business_fields_without_changing_admin_controls_or_login_email(): void
    {
        $vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();
        $user = User::query()->where('email', 'vendor@example.com')->firstOrFail();
        $secondaryUser = User::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'vendor-worker@example.com',
        ]);
        $secondaryUser->assignRole('vendor');

        $this->actingAs($user)->get(route('vendor.profile'))
            ->assertOk()
            ->assertSee('Account Profile')
            ->assertSee('Manage your business information and account security.')
            ->assertSee('Kigali Supplies')
            ->assertSee('vendor@example.com');

        $this->actingAs($user)->put(route('vendor.profile.update'), [
            'business_name' => 'Updated Kigali Supplies',
            'legal_name' => 'Updated Kigali Supplies Ltd',
            'email' => 'business-contact@example.com',
            'phone' => '0788111222',
            'district' => 'Kicukiro',
            'operating_location' => 'Updated location',
            'website_url' => 'https://updated.example.com',
            'address_notes' => 'Updated address information.',
            'partner_description' => 'Updated public description.',
            'reference_number' => 'FORGED-REFERENCE',
            'status' => 'suspended',
            'show_as_partner' => '1',
            'display_order' => '999',
            'partner_display_status' => 'published',
            'public_branding_approved' => '1',
            'internal_notes' => 'Forged internal note.',
            'vendor_id' => 99999,
        ])->assertRedirect(route('vendor.profile'))
            ->assertSessionHas('success', 'Business profile saved.');

        $vendor->refresh();
        $this->assertSame('Updated Kigali Supplies', $vendor->business_name);
        $this->assertSame('Updated Kigali Supplies Ltd', $vendor->legal_name);
        $this->assertSame('business-contact@example.com', $vendor->email);
        $this->assertSame('0788111222', $vendor->phone);
        $this->assertSame('Kicukiro', $vendor->district);
        $this->assertSame('Updated location', $vendor->operating_location);
        $this->assertSame('https://updated.example.com', $vendor->website_url);
        $this->assertSame('Updated address information.', $vendor->address_notes);
        $this->assertSame('Updated public description.', $vendor->partner_description);
        $this->assertSame('VND-SEED-001', $vendor->reference_number);
        $this->assertSame('active', $vendor->status);
        $this->assertFalse($vendor->show_as_partner);
        $this->assertSame(0, $vendor->display_order);
        $this->assertSame('draft', $vendor->partner_display_status);
        $this->assertFalse($vendor->public_branding_approved);
        $this->assertSame('Seeded vendor account for local development.', $vendor->internal_notes);
        $this->assertSame('vendor@example.com', $user->fresh()->email);
        $this->assertSame('vendor-worker@example.com', $secondaryUser->fresh()->email);
        $this->assertSame($vendor->id, $secondaryUser->fresh()->vendor_id);
    }

    public function test_business_profile_validation_rejects_invalid_email_url_and_logo(): void
    {
        $vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();
        $user = User::query()->where('email', 'vendor@example.com')->firstOrFail();

        $this->actingAs($user)->from(route('vendor.profile'))
            ->put(route('vendor.profile.update'), [
                'business_name' => 'Valid name',
                'email' => 'not-an-email',
                'website_url' => 'not-a-url',
                'business_logo' => UploadedFile::fake()->create('invalid.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('vendor.profile'))
            ->assertSessionHasErrors(['email', 'website_url', 'business_logo']);

        $this->assertSame('Kigali Supplies', $vendor->fresh()->business_name);
    }

    public function test_business_logo_upload_preview_persistence_replacement_and_removal(): void
    {
        $vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();
        $user = User::query()->where('email', 'vendor@example.com')->firstOrFail();

        $this->actingAs($user)->put(route('vendor.profile.update'), $this->businessInput([
            'business_logo' => $this->fakeLogo('profile-logo.png'),
        ]))->assertSessionHasNoErrors();
        $firstPath = $vendor->fresh()->business_logo_path;
        $this->logoPaths[] = $firstPath;
        $this->assertFileExists(public_path('assets/'.$firstPath));
        $this->actingAs($user)->get(route('vendor.profile'))
            ->assertOk()
            ->assertSee(asset('assets/'.$firstPath), false);
        $this->assertStringContainsString(
            'onerror="this.hidden = true; this.nextElementSibling.hidden = false;"',
            $this->get(route('vendor.profile'))->getContent()
        );

        $this->actingAs($user)->put(route('vendor.profile.update'), $this->businessInput([
            'business_logo' => $this->fakeLogo('replacement-logo.png'),
        ]))->assertSessionHasNoErrors();
        $replacementPath = $vendor->fresh()->business_logo_path;
        $this->logoPaths[] = $replacementPath;
        $this->assertFileDoesNotExist(public_path('assets/'.$firstPath));
        $this->assertFileExists(public_path('assets/'.$replacementPath));

        $this->actingAs($user)->put(route('vendor.profile.update'), $this->businessInput([
            'remove_logo' => '1',
        ]))->assertSessionHasNoErrors();
        $this->assertNull($vendor->fresh()->business_logo_path);
        $this->assertFileDoesNotExist(public_path('assets/'.$replacementPath));
        $this->actingAs($user)->get(route('vendor.profile'))
            ->assertOk()
            ->assertSee('id="business-logo-fallback"', false)
            ->assertSee('K', false);

        $vendor->update(['business_logo_path' => 'missing/logo.png']);
        $this->actingAs($user)->get(route('vendor.profile'))
            ->assertOk()
            ->assertSee('id="business-logo-fallback"', false)
            ->assertDontSee(asset('assets/missing/logo.png'), false);
    }

    public function test_vendor_can_update_only_their_own_individual_password_securely(): void
    {
        $vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();
        $user = User::query()->where('email', 'vendor@example.com')->firstOrFail();
        $otherUser = User::factory()->create([
            'vendor_id' => $vendor->id,
            'email' => 'another-worker@example.com',
            'password' => 'OriginalPassword2026!',
        ]);
        $otherUser->assignRole('vendor');
        $otherPassword = $otherUser->fresh()->password;

        $this->actingAs($user)->from(route('vendor.profile'))->put(route('vendor.profile.password'), [
            'current_password' => 'incorrect',
            'password' => 'UpdatedVendorPassword2026!',
            'password_confirmation' => 'UpdatedVendorPassword2026!',
        ])->assertRedirect(route('vendor.profile'))
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->from(route('vendor.profile'))->put(route('vendor.profile.password'), [
            'current_password' => 'password',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertRedirect(route('vendor.profile'))
            ->assertSessionHasErrorsIn('updatePassword', 'password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $this->put(route('vendor.profile.password'), [
            'current_password' => 'password',
            'password' => 'UpdatedVendorPassword2026!',
            'password_confirmation' => 'UpdatedVendorPassword2026!',
        ])->assertRedirect(route('vendor.profile'))
            ->assertSessionHas('success', 'Your password has been updated.');

        $this->assertTrue(Hash::check('UpdatedVendorPassword2026!', $user->fresh()->password));
        $this->assertSame($otherPassword, $otherUser->fresh()->password);
        $this->assertAuthenticatedAs($user->fresh());
        $this->get(route('vendor.profile'))->assertOk();
    }

    public function test_vendor_profile_updates_require_vendor_access_and_password_change_ignores_forged_business_fields(): void
    {
        $vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();
        $otherVendor = Vendor::query()->create([
            'business_name' => 'Separate Business',
            'status' => 'active',
            'email' => 'separate-business@example.com',
        ]);
        $otherUser = User::factory()->create([
            'vendor_id' => $otherVendor->id,
            'email' => 'separate-user@example.com',
        ]);
        $otherUser->assignRole('vendor');

        $this->actingAs($otherUser)->put(route('vendor.profile.update'), $this->businessInput([
            'business_name' => 'Attempt to access another vendor',
        ] + ['vendor_id' => $vendor->id]))->assertSessionHasNoErrors();
        $this->assertSame('Attempt to access another vendor', $otherVendor->fresh()->business_name);
        $this->assertSame('Kigali Supplies', $vendor->fresh()->business_name);

        $this->put(route('vendor.profile.password'), [
            'current_password' => 'password',
            'password' => 'UpdatedVendorPassword2026!',
            'password_confirmation' => 'UpdatedVendorPassword2026!',
            'business_name' => 'Must not update business data',
            'status' => 'suspended',
            'vendor_id' => $vendor->id,
        ])->assertSessionHas('success');
        $this->assertSame('Attempt to access another vendor', $otherVendor->fresh()->business_name);
        $this->assertSame('active', $otherVendor->fresh()->status);
    }

    private function businessInput(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Updated Business',
            'legal_name' => 'Updated Legal Name',
            'email' => 'updated-business@example.com',
            'phone' => '0788001111',
            'district' => 'Gasabo',
            'operating_location' => 'Kigali',
            'website_url' => 'https://updated.example.com',
            'address_notes' => 'Updated address.',
            'partner_description' => 'Updated description.',
        ], $overrides);
    }

    private function fakeLogo(string $name): UploadedFile
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC');

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
