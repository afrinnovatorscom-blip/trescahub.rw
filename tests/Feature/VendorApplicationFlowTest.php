<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceNotification;
use App\Support\PublicAsset;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MarketplaceUserSeeder::class);
        Storage::fake('local');
    }

    public function test_public_vendor_application_is_saved_pending_with_private_certificate_and_encrypted_payment_data(): void
    {
        $this->get(route('vendor.application.create'))
            ->assertOk()
            ->assertSee('Account Information')
            ->assertSee('Business Information')
            ->assertSee('Business Documents')
            ->assertSee('Payment Details')
            ->assertSee('Submit Vendor Application')
            ->assertSee('Sign in')
            ->assertDontSee('Admin Approval Required')
            ->assertSee('.vendor-registration-page', false);

        $this->post(route('vendor.application.store'), $this->applicationInput())
            ->assertRedirect(route('vendor.application.submitted'))
            ->assertSessionHasNoErrors();

        $vendor = Vendor::query()->where('business_name', 'New Applicant Business')->firstOrFail();
        $user = User::query()->where('email', 'applicant@example.com')->firstOrFail();

        $this->assertSame('pending', $vendor->status);
        $this->assertSame('pending', $vendor->application_status);
        $this->assertNotNull($vendor->application_submitted_at);
        $this->assertSame($vendor->id, $user->vendor_id);
        $this->assertNull($user->phone);
        $this->assertTrue($user->hasRole('vendor'));
        $this->assertSame('1234567890', $vendor->bank_account_number);
        $this->assertNotSame('1234567890', $vendor->getRawOriginal('bank_account_number'));
        $this->assertSame('Applicant Holder', $vendor->bank_account_holder);
        Storage::disk('local')->assertExists($vendor->registration_certificate_path);

        $this->assertGuest('web');
        $this->actingAs($user)->get(route('vendor.dashboard'))->assertForbidden();
    }

    public function test_application_requires_a_certificate_and_at_least_one_complete_payment_method(): void
    {
        $this->from(route('vendor.application.create'))
            ->post(route('vendor.application.store'), $this->applicationInput([
                'rdb_certificate' => UploadedFile::fake()->create('certificate.txt', 10, 'text/plain'),
                'bank_name' => '',
                'bank_account_holder' => '',
                'bank_account_number' => '',
                'mobile_money_provider' => '',
                'mobile_money_phone' => '',
                'mobile_money_account_holder' => '',
            ]))
            ->assertRedirect(route('vendor.application.create'))
            ->assertSessionHasErrors(['rdb_certificate', 'bank_name']);

        $this->from(route('vendor.application.create'))
            ->post(route('vendor.application.store'), $this->applicationInput([
                'bank_account_holder' => '',
                'bank_account_number' => '',
            ]))
            ->assertRedirect(route('vendor.application.create'))
            ->assertSessionHasErrors(['bank_account_holder', 'bank_account_number']);

        $this->post(route('vendor.application.store'), $this->applicationInput([
            'email' => 'admin@example.com',
        ]))->assertSessionHasErrors('email');
        $this->post(route('vendor.application.store'), $this->applicationInput([
            'email' => 'customer@example.com',
        ]))->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('vendors', ['business_name' => 'New Applicant Business']);
    }

    public function test_mobile_money_only_and_combined_payment_details_are_accepted(): void
    {
        $this->post(route('vendor.application.store'), $this->applicationInput([
            'contact_person' => 'Mobile Contact',
            'email' => 'mobile-only@example.com',
            'business_name' => 'Mobile Only Business',
            'business_email' => 'mobile-business@example.com',
            'bank_name' => '',
            'bank_account_holder' => '',
            'bank_account_number' => '',
            'mobile_money_provider' => 'mtn_momo',
            'mobile_money_phone' => '0788001111',
            'mobile_money_account_holder' => 'Mobile Holder',
        ]))->assertRedirect(route('vendor.application.submitted'))->assertSessionHasNoErrors();

        $this->post(route('vendor.application.store'), $this->applicationInput([
            'contact_person' => 'Combined Contact',
            'email' => 'combined@example.com',
            'business_name' => 'Combined Payment Business',
            'business_email' => 'combined-business@example.com',
            'mobile_money_provider' => 'airtel_money',
            'mobile_money_phone' => '0788002222',
            'mobile_money_account_holder' => 'Combined Holder',
        ]))->assertRedirect(route('vendor.application.submitted'))->assertSessionHasNoErrors();

        $mobileOnly = Vendor::query()->where('business_name', 'Mobile Only Business')->firstOrFail();
        $combined = Vendor::query()->where('business_name', 'Combined Payment Business')->firstOrFail();
        $this->assertSame('mtn_momo', $mobileOnly->mobile_money_provider);
        $this->assertNull($mobileOnly->bank_name);
        $this->assertSame('0788001111', $mobileOnly->mobile_money_phone);
        $this->assertSame('Example Bank', $combined->bank_name);
        $this->assertSame('airtel_money', $combined->mobile_money_provider);
    }

    public function test_admin_can_request_correction_and_approve_after_resubmission(): void
    {
        $this->post(route('vendor.application.store'), $this->applicationInput())->assertRedirect(route('vendor.application.submitted'));

        $vendor = Vendor::query()->where('business_name', 'New Applicant Business')->firstOrFail();
        $applicant = User::query()->where('email', 'applicant@example.com')->firstOrFail();
        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $certificatePath = $vendor->registration_certificate_path;

        $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index', ['tab' => 'unverified']))
            ->assertOk()
            ->assertSee('New Applicant Business')
            ->assertSee('TIN-12345')
            ->assertSee(route('marketplace.vendor-applications.show', $vendor), false)
            ->assertDontSee('Vendor Applications');

        $this->actingAs($administrator)
            ->get('/admin/marketplace/vendor-applications')
            ->assertNotFound();

        $this->loginAs($administrator, 'password')
            ->get(route('marketplace.vendor-applications.show', $vendor))
            ->assertOk()
            ->assertSee('Review Vendor Application')
            ->assertSee('Account Information')
            ->assertSee('Business Information')
            ->assertSee('Business Documents')
            ->assertSee('Payment Information')
            ->assertSee('Accept Vendor')
            ->assertSee('Reject Application')
            ->assertSee('1234567890')
            ->assertSee(route('marketplace.vendor-applications.certificate', $vendor), false);

        $this->loginAs($applicant)
            ->get(route('marketplace.vendor-applications.certificate', $vendor))
            ->assertForbidden();

        $this->loginAs($administrator, 'password')
            ->get(route('marketplace.vendor-applications.certificate', $vendor))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($administrator)
            ->post(route('marketplace.vendor-applications.correction', $vendor), [
                'correction_request' => 'How are you',
            ])
            ->assertRedirect(route('marketplace.vendor-applications.show', $vendor));

        $this->assertSame('correction_requested', $vendor->fresh()->application_status);
        $this->assertSame('How are you', $vendor->fresh()->correction_request);

        $this->get(route('marketplace.vendor-applications.show', $vendor))
            ->assertOk()
            ->assertSee('id="review-correction-toast"', false)
            ->assertSee('Requested correction:')
            ->assertSee('How are you')
            ->assertSee('aria-label="Dismiss notification"', false)
            ->assertSee('window.setTimeout(dismiss, 5000)', false)
            ->assertDontSee('class="alert alert-warning review-alert"', false);

        $this->loginAs($administrator, 'password')
            ->get(route('marketplace.vendor-applications.show', $vendor))
            ->assertOk()
            ->assertDontSee('id="review-correction-toast"', false)
            ->assertDontSee('How are you');
        $this->assertSame('correction_requested', $vendor->fresh()->application_status);
        $this->assertSame('How are you', $vendor->fresh()->correction_request);

        $this->assertTrue($applicant->fresh()->hasRole('vendor'));
        $this->assertSame($vendor->id, $applicant->fresh()->vendor?->id);
        $this->assertTrue($applicant->notifications()
            ->where('type', MarketplaceNotification::class)
            ->where('data->dedupe_key', 'like', 'vendor-application-'.$vendor->id.'-correction_requested-%')
            ->exists());
        $this->loginAs($applicant)
            ->get(route('vendor.application.correction'))
            ->assertOk()
            ->assertSee('id="vendor-correction-toast"', false)
            ->assertSee('Requested correction:')
            ->assertSee('How are you')
            ->assertSee('aria-label="Dismiss notification"', false)
            ->assertSee('window.setTimeout(dismiss, 5000)', false);

        $this->loginAs($applicant)
            ->get(route('vendor.application.correction'))
            ->assertOk()
            ->assertDontSee('id="vendor-correction-toast"', false)
            ->assertDontSee('How are you');
        $this->assertSame('correction_requested', $vendor->fresh()->application_status);
        $this->assertSame('How are you', $vendor->fresh()->correction_request);

        $this->loginAs($applicant)
            ->put(route('vendor.application.correction.update'), $this->correctionInput())
            ->assertRedirect(route('vendor.application.submitted'))
            ->assertSessionHasNoErrors();

        $vendor->refresh();
        $this->assertSame('pending', $vendor->application_status);
        $this->assertSame('Correction Updated Address', $vendor->business_address);
        $this->assertSame('1234567890', $vendor->bank_account_number);
        $this->assertSame($certificatePath, $vendor->registration_certificate_path);
        $this->assertSame('How are you', $vendor->correction_request);
        $this->assertNotNull($vendor->correction_requested_at);
        $this->assertTrue($applicant->notifications()
            ->where('type', MarketplaceNotification::class)
            ->where('data->dedupe_key', 'like', 'vendor-application-'.$vendor->id.'-correction_requested-%')
            ->exists());
        $this->assertNotNull($applicant->notifications()
            ->where('type', MarketplaceNotification::class)
            ->where('data->dedupe_key', 'like', 'vendor-application-'.$vendor->id.'-correction_requested-%')
            ->firstOrFail()
            ->read_at);

        $this->loginAs($administrator, 'password')
            ->get(route('marketplace.vendor-applications.show', $vendor))
            ->assertOk()
            ->assertDontSee('Requested correction:');

        $this->loginAs($administrator, 'password')
            ->post(route('marketplace.vendor-applications.approve', $vendor))
            ->assertRedirect(route('marketplace.vendor-applications.show', $vendor));

        $vendor->refresh();
        $this->assertSame('approved', $vendor->application_status);
        $this->assertSame('active', $vendor->status);
        $this->assertSame($administrator->id, $vendor->approved_by);
        $this->assertNotNull($vendor->approved_at);
        $this->assertSame('How are you', $vendor->correction_request);
        $this->assertNotNull($vendor->correction_requested_at);
        $this->loginAs($applicant)->get(route('vendor.dashboard'))->assertOk();
    }

    public function test_approval_populates_the_existing_vendor_profile_and_preserves_application_data(): void
    {
        $logoPath = null;

        try {
            $this->post(route('vendor.application.store'), $this->applicationInput([
                'contact_person' => 'Mapped Applicant',
                'email' => 'mapped-login@example.com',
                'business_name' => 'Mapped Business Ltd',
                'business_email' => 'mapped-business@example.com',
                'business_phone' => '0788111222',
                'business_address' => 'KN 3 Ave, Kigali, Rwanda',
                'business_logo' => $this->fakePng('mapped-logo.png'),
                'mobile_money_provider' => 'mtn_momo',
                'mobile_money_phone' => '0788999000',
                'mobile_money_account_holder' => 'Mapped Mobile Holder',
            ]))->assertRedirect(route('vendor.application.submitted'))
                ->assertSessionHasNoErrors();

            $vendor = Vendor::query()->where('business_name', 'Mapped Business Ltd')->firstOrFail();
            $applicant = User::query()->where('email', 'mapped-login@example.com')->firstOrFail();
            $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();
            $logoPath = $vendor->business_logo_path;
            $certificatePath = $vendor->registration_certificate_path;

            $this->actingAs($administrator)
                ->post(route('marketplace.vendor-applications.approve', $vendor))
                ->assertRedirect(route('marketplace.vendor-applications.show', $vendor));

            $vendor->refresh();
            $this->assertSame('approved', $vendor->application_status);
            $this->assertSame('active', $vendor->status);
            $this->assertSame('Mapped Business Ltd', $vendor->legal_name);
            $this->assertSame('Mapped Applicant', $vendor->contact_person);
            $this->assertSame('mapped-login@example.com', $vendor->email);
            $this->assertSame('mapped-business@example.com', $vendor->business_email);
            $this->assertSame('0788111222', $vendor->phone);
            $this->assertSame('KN 3 Ave, Kigali, Rwanda', $vendor->address_notes);
            $this->assertNull($vendor->district);
            $this->assertNull($vendor->operating_location);
            $this->assertSame('TIN-12345', $vendor->tin);
            $this->assertSame('Example Bank', $vendor->bank_name);
            $this->assertSame('Applicant Holder', $vendor->bank_account_holder);
            $this->assertSame('1234567890', $vendor->bank_account_number);
            $this->assertSame('mtn_momo', $vendor->mobile_money_provider);
            $this->assertSame('0788999000', $vendor->mobile_money_phone);
            $this->assertSame('Mapped Mobile Holder', $vendor->mobile_money_account_holder);
            $this->assertSame($logoPath, $vendor->business_logo_path);
            $this->assertSame($certificatePath, $vendor->registration_certificate_path);
            Storage::disk('local')->assertExists($certificatePath);

            $this->actingAs($administrator)
                ->get(route('marketplace.vendors.index', ['tab' => 'verified']))
                ->assertOk()
                ->assertSee('Mapped Business Ltd');

            $this->actingAs($administrator)
                ->get(route('marketplace.vendors.index', ['tab' => 'unverified']))
                ->assertOk()
                ->assertDontSee('Mapped Business Ltd');

            $this->actingAs($administrator)
                ->get(route('marketplace.vendors.show', $vendor))
                ->assertOk()
                ->assertSee('Mapped Applicant')
                ->assertSee('mapped-login@example.com')
                ->assertSee('mapped-business@example.com')
                ->assertSee('0788111222')
                ->assertSee('KN 3 Ave, Kigali, Rwanda')
                ->assertSee(asset('assets/'.$logoPath), false);

            $this->actingAs($administrator)
                ->get(route('marketplace.vendors.edit', $vendor))
                ->assertOk()
                ->assertSee('value="mapped-login@example.com"', false)
                ->assertSee('value="0788111222"', false)
                ->assertSee('KN 3 Ave, Kigali, Rwanda');

            $this->actingAs($administrator)
                ->put(route('marketplace.vendors.update', $vendor), [
                    'business_name' => 'Mapped Business Ltd',
                    'legal_name' => 'Mapped Business Ltd',
                    'reference_number' => '',
                    'email' => 'mapped-login-updated@example.com',
                    'phone' => '0788111999',
                    'status' => 'active',
                    'district' => '',
                    'operating_location' => '',
                    'website_url' => '',
                    'address_notes' => 'Updated Admin Address',
                    'internal_notes' => '',
                    'show_as_partner' => '0',
                    'partner_description' => '',
                    'display_order' => '0',
                    'public_branding_approved' => '0',
                ])
                ->assertRedirect(route('marketplace.vendors.show', $vendor))
                ->assertSessionHasNoErrors();

            $vendor->refresh();
            $applicant->refresh();
            $this->assertSame('mapped-login-updated@example.com', $vendor->email);
            $this->assertSame('mapped-login-updated@example.com', $applicant->email);
            $this->assertSame('0788111999', $vendor->phone);
            $this->assertNull($applicant->phone);
            $this->assertSame('mapped-business@example.com', $vendor->business_email);
            $this->assertSame('Updated Admin Address', $vendor->address_notes);
            $this->assertSame('KN 3 Ave, Kigali, Rwanda', $vendor->business_address);
            $this->assertSame($logoPath, $vendor->business_logo_path);
            $this->assertSame($certificatePath, $vendor->registration_certificate_path);
            $this->assertSame('1234567890', $vendor->bank_account_number);
            $this->assertSame('0788999000', $vendor->mobile_money_phone);

            $this->actingAs($administrator)
                ->get(route('marketplace.vendors.show', $vendor))
                ->assertOk()
                ->assertSee('mapped-login-updated@example.com')
                ->assertSee('Updated Admin Address');
        } finally {
            PublicAsset::delete($logoPath);
        }
    }

    public function test_new_correction_request_replaces_the_addressed_message(): void
    {
        $this->post(route('vendor.application.store'), $this->applicationInput())
            ->assertRedirect(route('vendor.application.submitted'));

        $vendor = Vendor::query()->where('business_name', 'New Applicant Business')->firstOrFail();
        $applicant = User::query()->where('email', 'applicant@example.com')->firstOrFail();
        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($administrator)
            ->post(route('marketplace.vendor-applications.correction', $vendor), [
                'correction_request' => 'How are you',
            ])
            ->assertRedirect(route('marketplace.vendor-applications.show', $vendor));

        $this->assertTrue($applicant->notifications()
            ->where('type', MarketplaceNotification::class)
            ->where('data->dedupe_key', 'like', 'vendor-application-'.$vendor->id.'-correction_requested-%')
            ->exists());

        $this->loginAs($applicant)
            ->put(route('vendor.application.correction.update'), $this->correctionInput())
            ->assertRedirect(route('vendor.application.submitted'))
            ->assertSessionHasNoErrors();

        $vendor->refresh();
        $this->assertSame('pending', $vendor->application_status);
        $this->assertSame('How are you', $vendor->correction_request);
        $this->assertNotNull($vendor->correction_requested_at);
        $this->assertTrue($applicant->notifications()
            ->where('type', MarketplaceNotification::class)
            ->where('data->dedupe_key', 'like', 'vendor-application-'.$vendor->id.'-correction_requested-%')
            ->exists());
        $this->assertNotNull($applicant->notifications()
            ->where('type', MarketplaceNotification::class)
            ->where('data->dedupe_key', 'like', 'vendor-application-'.$vendor->id.'-correction_requested-%')
            ->firstOrFail()
            ->read_at);

        $this->loginAs($administrator, 'password')
            ->post(route('marketplace.vendor-applications.correction', $vendor), [
                'correction_request' => 'Please confirm your current address.',
            ])
            ->assertRedirect(route('marketplace.vendor-applications.show', $vendor));

        $vendor->refresh();
        $this->assertSame('Please confirm your current address.', $vendor->correction_request);

        $this->loginAs($administrator, 'password')
            ->get(route('marketplace.vendor-applications.show', $vendor))
            ->assertOk()
            ->assertSee('id="review-correction-toast"', false)
            ->assertSee('Please confirm your current address.')
            ->assertDontSee('How are you')
            ->assertDontSee('class="alert alert-warning review-alert"', false);

        $this->get(route('marketplace.vendor-applications.show', $vendor))
            ->assertOk()
            ->assertDontSee('id="review-correction-toast"', false)
            ->assertDontSee('Please confirm your current address.');

        $this->loginAs($applicant)
            ->get(route('vendor.application.correction'))
            ->assertOk()
            ->assertSee('id="vendor-correction-toast"', false)
            ->assertSee('Requested correction:')
            ->assertSee('Please confirm your current address.')
            ->assertDontSee('How are you');

        $this->get(route('vendor.application.correction'))
            ->assertOk()
            ->assertDontSee('id="vendor-correction-toast"', false)
            ->assertDontSee('How are you');
        $this->assertSame('correction_requested', $vendor->fresh()->application_status);
    }

    public function test_pending_application_cannot_be_activated_through_vendor_management_or_rejected_vendor_workspace(): void
    {
        $this->post(route('vendor.application.store'), $this->applicationInput())->assertRedirect(route('vendor.application.submitted'));

        $vendor = Vendor::query()->where('business_name', 'New Applicant Business')->firstOrFail();
        $applicant = User::query()->where('email', 'applicant@example.com')->firstOrFail();
        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($administrator)
            ->patch(route('marketplace.vendors.status', $vendor), ['status' => 'active'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $vendor->fresh()->status);

        $this->actingAs($administrator)
            ->post(route('marketplace.vendor-applications.reject', $vendor), ['rejection_reason' => 'The submitted certificate could not be verified.'])
            ->assertRedirect(route('marketplace.vendor-applications.show', $vendor));

        $this->assertSame('rejected', $vendor->fresh()->application_status);
        $this->assertSame('inactive', $vendor->fresh()->status);
        $this->assertSame('The submitted certificate could not be verified.', $vendor->fresh()->rejection_reason);
        $this->loginAs($applicant)->get(route('vendor.dashboard'))->assertForbidden();
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $applicant->id,
            'type' => \App\Notifications\MarketplaceNotification::class,
        ]);
    }

    private function applicationInput(array $overrides = []): array
    {
        return array_merge([
            'contact_person' => 'Applicant Contact',
            'email' => 'applicant@example.com',
            'password' => 'VendorPassword2026!',
            'password_confirmation' => 'VendorPassword2026!',
            'business_name' => 'New Applicant Business',
            'tin' => 'TIN-12345',
            'business_phone' => '0788001000',
            'business_email' => 'business@example.com',
            'business_address' => 'Kigali, Rwanda',
            'rdb_certificate' => UploadedFile::fake()->createWithContent('certificate.pdf', "%PDF-1.4\nCertificate"),
            'bank_name' => 'Example Bank',
            'bank_account_holder' => 'Applicant Holder',
            'bank_account_number' => '1234567890',
            'mobile_money_provider' => '',
            'mobile_money_phone' => '',
            'mobile_money_account_holder' => '',
        ], $overrides);
    }

    private function fakePng(string $filename): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $filename,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
        );
    }

    private function correctionInput(): array
    {
        return [
            'contact_person' => 'Applicant Contact Updated',
            'business_name' => 'New Applicant Business',
            'tin' => 'TIN-12345',
            'business_phone' => '0788001000',
            'business_email' => 'business@example.com',
            'business_address' => 'Correction Updated Address',
            'bank_name' => '',
            'bank_account_holder' => '',
            'bank_account_number' => '',
            'mobile_money_provider' => '',
            'mobile_money_phone' => '',
            'mobile_money_account_holder' => '',
        ];
    }

    private function loginAs(User $user, string $password = 'VendorPassword2026!'): static
    {
        Auth::forgetGuards();
        $this->flushSession();
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
        ])->assertSessionHasNoErrors()->assertRedirect();

        return $this;
    }
}
