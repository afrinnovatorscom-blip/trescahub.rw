<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Support\PublicAsset;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorLogoAndTabsTest extends TestCase
{
    use RefreshDatabase;

    private array $logoPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MarketplaceUserSeeder::class);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        foreach ($this->logoPaths as $path) {
            PublicAsset::delete($path);
        }

        parent::tearDown();
    }

    public function test_vendor_logo_is_optional_and_uploaded_logo_is_shown_in_vendor_management(): void
    {
        $this->get(route('vendor.application.create'))
            ->assertOk()
            ->assertSee('Business Logo')
            ->assertSee('Business Logo</label>', false)
            ->assertSee('JPG, JPEG, or PNG only. Maximum file size: 5 MB.');

        $this->post(route('vendor.application.store'), $this->applicationInput())
            ->assertRedirect(route('vendor.application.submitted'))
            ->assertSessionHasNoErrors();

        $withoutLogo = Vendor::query()->where('business_name', 'Applicant Without Logo')->firstOrFail();
        $this->assertNull($withoutLogo->business_logo_path);

        $this->post(route('vendor.application.store'), $this->applicationInput([
            'contact_person' => 'Logo Applicant',
            'email' => 'logo-applicant@example.com',
            'business_name' => 'Applicant With Logo',
            'business_email' => 'logo-business@example.com',
            'business_logo' => $this->fakePng('business-logo.png'),
        ]))->assertRedirect(route('vendor.application.submitted'))->assertSessionHasNoErrors();

        $withLogo = Vendor::query()->where('business_name', 'Applicant With Logo')->firstOrFail();
        $this->logoPaths[] = $withLogo->business_logo_path;
        $this->assertNotEmpty($withLogo->business_logo_path);
        $this->assertFileExists(public_path('assets/'.$withLogo->business_logo_path));

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($administrator)
            ->get(route('marketplace.vendors.show', $withLogo))
            ->assertOk()
            ->assertSee(asset('assets/'.$withLogo->business_logo_path), false);
    }

    public function test_registration_rejects_logo_formats_outside_jpeg_and_png(): void
    {
        $this->from(route('vendor.application.create'))
            ->post(route('vendor.application.store'), $this->applicationInput([
                'business_logo' => UploadedFile::fake()->create('business-logo.gif', 20, 'image/gif'),
            ]))
            ->assertRedirect(route('vendor.application.create'))
            ->assertSessionHasErrors('business_logo');

        $this->assertDatabaseMissing('vendors', ['business_name' => 'Applicant Without Logo']);
    }

    public function test_vendor_tabs_separate_approved_and_unreviewed_applications_without_changing_verified_list_features(): void
    {
        $legacyVendor = $this->vendor('Legacy Vendor', null);
        $approvedVendor = $this->vendor('Approved Applicant', 'approved');
        $pendingVendor = $this->vendor('Pending Applicant', 'pending');
        $correctionVendor = $this->vendor('Correction Applicant', 'correction_requested');
        $rejectedVendor = $this->vendor('Rejected Applicant', 'rejected');

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $verifiedPage = $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index'));

        $verifiedPage->assertOk()
            ->assertSee('Verified Vendors')
            ->assertSee('Unverified Applications')
            ->assertSee('Total Vendors')
            ->assertSee('Active Vendors')
            ->assertSee('Terminated Vendors')
            ->assertSee('Search vendors')
            ->assertSee('Filter')
            ->assertSee('Total Products')
            ->assertSee('Joined')
            ->assertSee('Actions')
            ->assertSee('Register Vendor')
            ->assertSee($legacyVendor->business_name)
            ->assertSee($approvedVendor->business_name)
            ->assertDontSee($pendingVendor->business_name)
            ->assertDontSee($correctionVendor->business_name)
            ->assertDontSee($rejectedVendor->business_name)
            ->assertSee(route('marketplace.vendors.index', ['tab' => 'unverified']), false);

        $unverifiedPage = $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index', ['tab' => 'unverified']));

        $unverifiedPage->assertOk()
            ->assertSee('Unverified Applications')
            ->assertSee('Review Application')
            ->assertDontSee($legacyVendor->business_name)
            ->assertDontSee($approvedVendor->business_name)
            ->assertSee($pendingVendor->business_name)
            ->assertSee($correctionVendor->business_name)
            ->assertDontSee($rejectedVendor->business_name)
            ->assertDontSee('Total Products')
            ->assertDontSee('Search vendors');

        $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index', ['q' => 'Legacy', 'status' => 'active']))
            ->assertOk()
            ->assertSee($legacyVendor->business_name)
            ->assertDontSee($approvedVendor->business_name);
    }

    public function test_unverified_tab_lists_submitted_applications_and_moves_approved_vendors_to_verified(): void
    {
        $this->post(route('vendor.application.store'), $this->applicationInput([
            'contact_person' => 'Logo Applicant',
            'email' => 'logo-applicant@example.com',
            'business_name' => 'Applicant With Logo',
            'business_email' => 'logo-business@example.com',
            'business_phone' => '0788111222',
            'business_logo' => $this->fakePng('application-logo.png'),
        ]))->assertRedirect(route('vendor.application.submitted'))->assertSessionHasNoErrors();

        $this->post(route('vendor.application.store'), $this->applicationInput([
            'contact_person' => 'No Logo Contact',
            'email' => 'no-logo-applicant@example.com',
            'business_name' => 'Applicant Without Logo',
            'business_email' => 'no-logo-business@example.com',
            'business_phone' => '0788222333',
        ]))->assertRedirect(route('vendor.application.submitted'))->assertSessionHasNoErrors();

        $withLogo = Vendor::query()->where('business_name', 'Applicant With Logo')->firstOrFail();
        $withoutLogo = Vendor::query()->where('business_name', 'Applicant Without Logo')->firstOrFail();
        $this->logoPaths[] = $withLogo->business_logo_path;
        $this->assertSame($withLogo->id, $withLogo->users()->firstOrFail()->vendor_id);
        $this->assertNull($withoutLogo->business_logo_path);

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($administrator)
            ->post(route('marketplace.vendor-applications.correction', $withoutLogo), [
                'correction_request' => 'Please confirm your business address.',
            ])
            ->assertRedirect(route('marketplace.vendor-applications.show', $withoutLogo));

        $applicationsPage = $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index', ['tab' => 'unverified']));

        $applicationsPage->assertOk()
            ->assertSee('Business Logo')
            ->assertSee('Business Name')
            ->assertSee('Contact Person')
            ->assertSee('TIN')
            ->assertSee('Business Phone')
            ->assertSee('Business Email')
            ->assertSee('Submitted Date')
            ->assertSee('Application Status')
            ->assertSee('Review Application')
            ->assertSee($withLogo->business_name)
            ->assertSee($withLogo->contact_person)
            ->assertSee($withLogo->tin)
            ->assertSee($withLogo->phone)
            ->assertSee($withLogo->business_email)
            ->assertSee($withLogo->application_submitted_at->format('M j, Y H:i'))
            ->assertSee('Pending Review')
            ->assertSee(route('marketplace.vendor-applications.show', $withLogo), false)
            ->assertSee(asset('assets/'.$withLogo->business_logo_path), false)
            ->assertSee($withoutLogo->business_name)
            ->assertSee('Correction Requested');

        $this->actingAs($administrator)
            ->get(route('marketplace.vendor-applications.show', $withLogo))
            ->assertOk()
            ->assertSee($withLogo->business_name);

        $this->actingAs($administrator)
            ->post(route('marketplace.vendor-applications.approve', $withLogo))
            ->assertRedirect(route('marketplace.vendor-applications.show', $withLogo));

        $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index', ['tab' => 'unverified']))
            ->assertOk()
            ->assertDontSee($withLogo->business_name)
            ->assertSee($withoutLogo->business_name);

        $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index', ['tab' => 'verified']))
            ->assertOk()
            ->assertSee($withLogo->business_name)
            ->assertDontSee($withoutLogo->business_name);
    }

    public function test_unverified_tab_shows_an_empty_state_when_no_applications_await_review(): void
    {
        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index', ['tab' => 'unverified']))
            ->assertOk()
            ->assertSee('No vendor applications awaiting review.');
    }

    public function test_vendor_index_uses_existing_status_column_when_application_status_is_absent(): void
    {
        $activeVendor = $this->vendor('Legacy Active Vendor', null);
        $terminatedVendor = $this->vendor('Legacy Terminated Vendor', 'rejected');
        $pendingVendor = $this->vendor('Legacy Pending Vendor', 'pending');

        Schema::table('vendors', function (Blueprint $table): void {
            $table->dropIndex(['application_status']);
            $table->dropColumn('application_status');
        });

        $administrator = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($administrator)
            ->get(route('marketplace.vendors.index'))
            ->assertOk()
            ->assertSee($activeVendor->business_name)
            ->assertSee($terminatedVendor->business_name)
            ->assertDontSee($pendingVendor->business_name);
    }

    private function vendor(string $name, ?string $applicationStatus): Vendor
    {
        return Vendor::query()->create([
            'business_name' => $name,
            'email' => strtolower(str_replace(' ', '-', $name)).'@example.com',
            'status' => match ($applicationStatus) {
                null, 'approved' => 'active',
                'rejected' => 'inactive',
                default => 'pending',
            },
            'application_status' => $applicationStatus,
            'application_submitted_at' => $applicationStatus ? now() : null,
        ]);
    }

    private function applicationInput(array $overrides = []): array
    {
        return array_merge([
            'contact_person' => 'Applicant Contact',
            'email' => 'applicant-without-logo@example.com',
            'password' => 'VendorPassword2026!',
            'password_confirmation' => 'VendorPassword2026!',
            'business_name' => 'Applicant Without Logo',
            'tin' => 'TIN-12345',
            'business_phone' => '0788001000',
            'business_email' => 'business-without-logo@example.com',
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
}
