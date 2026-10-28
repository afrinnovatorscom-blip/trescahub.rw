<?php

namespace Tests\Feature;

use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HomepagePartnersTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_only_active_published_partners_with_approved_branding(): void
    {
        $logoPath = 'homepage-partner-test.png';
        $logoAbsolutePath = public_path('assets/'.$logoPath);
        File::put(
            $logoAbsolutePath,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
        );

        Vendor::create([
            'business_name' => 'Approved Partner',
            'status' => 'active',
            'public_branding_approved' => true,
            'business_logo_path' => $logoPath,
            'show_as_partner' => true,
            'partner_display_status' => 'published',
            'display_order' => 2,
        ]);

        Vendor::create([
            'business_name' => 'First Listed Partner',
            'status' => 'active',
            'public_branding_approved' => true,
            'show_as_partner' => true,
            'partner_display_status' => 'published',
            'display_order' => 1,
        ]);

        Vendor::create([
            'business_name' => 'Pending Vendor',
            'status' => 'pending',
            'public_branding_approved' => true,
            'show_as_partner' => true,
            'partner_display_status' => 'published',
        ]);

        Vendor::create([
            'business_name' => 'Suspended Vendor',
            'status' => 'suspended',
            'public_branding_approved' => true,
            'show_as_partner' => true,
            'partner_display_status' => 'published',
        ]);

        Vendor::create([
            'business_name' => 'Unapproved Branding Vendor',
            'status' => 'active',
            'public_branding_approved' => false,
            'show_as_partner' => true,
            'partner_display_status' => 'published',
        ]);

        Vendor::create([
            'business_name' => 'Draft Partner',
            'status' => 'active',
            'public_branding_approved' => true,
            'show_as_partner' => true,
            'partner_display_status' => 'draft',
        ]);

        Vendor::create([
            'business_name' => 'Opted Out Vendor',
            'status' => 'active',
            'public_branding_approved' => true,
            'show_as_partner' => false,
            'partner_display_status' => 'published',
        ]);

        Vendor::create([
            'business_name' => '   ',
            'status' => 'active',
            'public_branding_approved' => true,
            'show_as_partner' => true,
            'partner_display_status' => 'published',
        ]);

        try {
            $response = $this->get('/')
                ->assertOk()
                ->assertSee('Our Partners')
                ->assertSee('Approved Partner')
                ->assertSeeInOrder(['First Listed Partner', 'Approved Partner'])
                ->assertSee(asset('assets/'.$logoPath), false)
                ->assertDontSee('Pending Vendor')
                ->assertDontSee('Suspended Vendor')
                ->assertDontSee('Unapproved Branding Vendor')
                ->assertDontSee('Draft Partner')
                ->assertDontSee('Opted Out Vendor');

            $this->assertStringContainsString(
                'onerror="this.onerror=null; this.hidden=true; this.nextElementSibling.style.display=\'flex\';"',
                $response->getContent(),
            );
            $this->assertMatchesRegularExpression(
                '~<span[^>]*style="display:none;[^"]*"[^>]*>\s*A\s*</span>~s',
                $response->getContent(),
            );
        } finally {
            File::delete($logoAbsolutePath);
        }
    }

    public function test_homepage_reveals_the_fallback_when_an_existing_logo_file_is_invalid(): void
    {
        $logoPath = 'homepage-invalid-partner-logo.png';
        $logoAbsolutePath = public_path('assets/'.$logoPath);
        File::put($logoAbsolutePath, 'not an image');

        Vendor::create([
            'business_name' => 'Invalid Logo Partner',
            'status' => 'active',
            'public_branding_approved' => true,
            'business_logo_path' => $logoPath,
            'show_as_partner' => true,
            'partner_display_status' => 'published',
        ]);

        try {
            $response = $this->get('/')
                ->assertOk()
                ->assertSee(asset('assets/'.$logoPath), false);

            $this->assertStringContainsString(
                'onerror="this.onerror=null; this.hidden=true; this.nextElementSibling.style.display=\'flex\';"',
                $response->getContent(),
            );
            $this->assertMatchesRegularExpression(
                '~<span[^>]*style="display:none;[^"]*"[^>]*>\s*I\s*</span>~s',
                $response->getContent(),
            );
        } finally {
            File::delete($logoAbsolutePath);
        }
    }

    public function test_homepage_uses_a_name_fallback_when_an_eligible_partner_logo_is_missing(): void
    {
        Vendor::create([
            'business_name' => 'Logo Missing Partner',
            'status' => 'active',
            'public_branding_approved' => true,
            'business_logo_path' => 'vendors/logos/missing.png',
            'show_as_partner' => true,
            'partner_display_status' => 'published',
        ]);

        $response = $this->get('/')
            ->assertOk()
            ->assertSee('Logo Missing Partner')
            ->assertDontSee(asset('assets/vendors/logos/missing.png'), false);

        $this->assertMatchesRegularExpression(
            '~<span[^>]*style="display:flex;[^"]*"[^>]*>\s*L\s*</span>~s',
            $response->getContent(),
        );
    }

    public function test_homepage_handles_an_empty_public_partner_list(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Our Partners')
            ->assertSee('Partner logos will appear here as businesses are approved for public display.');
    }
}
