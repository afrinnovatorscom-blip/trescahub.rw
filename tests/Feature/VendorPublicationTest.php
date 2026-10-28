<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPublicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);
    }

    public function test_admin_can_publish_and_remove_an_eligible_vendor_from_the_homepage(): void
    {
        $vendor = $this->vendor('Eligible Homepage Partner', [
            'show_as_partner' => true,
            'public_branding_approved' => true,
        ]);
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Publish on Website')
            ->assertSee('have approved public branding.');

        $this->actingAs($admin)
            ->patch(route('marketplace.vendors.publication', $vendor), [
                'action' => 'publish',
                'confirmed' => '1',
            ])
            ->assertRedirect(route('marketplace.vendors.show', $vendor))
            ->assertSessionHas('success', 'Vendor published on the website.');

        $this->assertSame('published', $vendor->fresh()->partner_display_status);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Vendor::class,
            'auditable_id' => $vendor->id,
            'action' => 'vendor.published',
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Partner: Published')
            ->assertSee('Website: Published')
            ->assertSee('Remove from Website');

        $this->get('/')
            ->assertOk()
            ->assertSee('Eligible Homepage Partner');

        $this->actingAs($admin)
            ->patch(route('marketplace.vendors.publication', $vendor), [
                'action' => 'unpublish',
                'confirmed' => '1',
            ])
            ->assertRedirect(route('marketplace.vendors.show', $vendor))
            ->assertSessionHas('success', 'Vendor removed from the website.');

        $this->assertSame('draft', $vendor->fresh()->partner_display_status);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Vendor::class,
            'auditable_id' => $vendor->id,
            'action' => 'vendor.unpublished',
        ]);
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Eligible Homepage Partner');
        $this->actingAs($admin)
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertSee('Publish on Website');
    }

    public function test_publication_requires_explicit_branding_approval_and_admin_can_approve_it_in_edit(): void
    {
        $vendor = $this->vendor('Branding Review Partner', ['show_as_partner' => true]);
        $admin = $this->administrator();

        $this->actingAs($admin)
            ->patch(route('marketplace.vendors.publication', $vendor), [
                'action' => 'publish',
                'confirmed' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors([
                'publication' => 'This vendor cannot be published because branding approval is not complete.',
            ]);

        $this->assertSame('draft', $vendor->fresh()->partner_display_status);
        $this->assertFalse($vendor->fresh()->public_branding_approved);

        $this->actingAs($admin)
            ->get(route('marketplace.vendors.edit', $vendor))
            ->assertOk()
            ->assertSee('Approve this business name and logo for public display')
            ->assertSee('Changing either one resets this approval');

        $this->actingAs($admin)
            ->put(route('marketplace.vendors.update', $vendor), $this->vendorUpdate([
                'business_name' => $vendor->business_name,
                'email' => $vendor->email,
                'show_as_partner' => '1',
                'public_branding_approved' => '1',
            ]))
            ->assertRedirect(route('marketplace.vendors.show', $vendor));

        $this->assertTrue($vendor->fresh()->public_branding_approved);
        $this->assertSame('draft', $vendor->fresh()->partner_display_status);

        $this->actingAs($admin)
            ->patch(route('marketplace.vendors.publication', $vendor), [
                'action' => 'publish',
                'confirmed' => '1',
            ])
            ->assertRedirect(route('marketplace.vendors.show', $vendor));

        $this->assertSame('published', $vendor->fresh()->partner_display_status);
    }

    public function test_vendor_name_or_logo_changes_reset_public_branding_approval(): void
    {
        $vendor = $this->vendor('Approved Branding Partner', [
            'public_branding_approved' => true,
        ]);

        $this->actingAs($this->administrator())
            ->put(route('marketplace.vendors.update', $vendor), $this->vendorUpdate([
                'business_name' => 'Changed Business Name',
                'email' => $vendor->email,
                'public_branding_approved' => '1',
            ]))
            ->assertRedirect(route('marketplace.vendors.show', $vendor));

        $this->assertFalse($vendor->fresh()->public_branding_approved);
    }

    public function test_publication_rejects_ineligible_vendors_and_unauthorized_users(): void
    {
        $inactiveVendor = $this->vendor('Inactive Publication Partner', [
            'status' => 'suspended',
            'show_as_partner' => true,
            'public_branding_approved' => true,
        ]);
        $notPartner = $this->vendor('Not Yet Partner', [
            'show_as_partner' => false,
            'public_branding_approved' => true,
        ]);

        $this->actingAs($this->administrator())
            ->patch(route('marketplace.vendors.publication', $inactiveVendor), [
                'action' => 'publish',
                'confirmed' => '1',
            ])
            ->assertSessionHasErrors([
                'publication' => 'Only active vendors can be published on the website.',
            ]);

        $this->actingAs($this->administrator())
            ->patch(route('marketplace.vendors.publication', $notPartner), [
                'action' => 'publish',
                'confirmed' => '1',
            ])
            ->assertSessionHasErrors([
                'publication' => 'This vendor is not marked for public partner display.',
            ]);

        $this->assertSame('draft', $inactiveVendor->fresh()->partner_display_status);
        $this->assertSame('draft', $notPartner->fresh()->partner_display_status);
    }

    public function test_users_without_vendor_management_permission_cannot_publish(): void
    {
        $vendor = $this->vendor('Restricted Publication Partner', [
            'show_as_partner' => true,
            'public_branding_approved' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('marketplace.vendors.publication', $vendor), [
                'action' => 'publish',
                'confirmed' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('draft', $vendor->fresh()->partner_display_status);
    }

    public function test_unpublishing_a_vendor_remains_available_after_it_becomes_ineligible(): void
    {
        $vendor = $this->vendor('Suspended Published Partner', [
            'status' => 'suspended',
            'show_as_partner' => true,
            'public_branding_approved' => true,
            'partner_display_status' => 'published',
        ]);

        $this->actingAs($this->administrator())
            ->get(route('marketplace.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Remove from Website')
            ->assertSee('Website: Blocked: Only active vendors can be published on the website.');

        $this->actingAs($this->administrator())
            ->patch(route('marketplace.vendors.publication', $vendor), [
                'action' => 'unpublish',
                'confirmed' => '1',
            ])
            ->assertRedirect(route('marketplace.vendors.show', $vendor));

        $this->assertSame('draft', $vendor->fresh()->partner_display_status);
    }

    private function administrator(): User
    {
        return User::query()->where('email', 'admin@example.com')->firstOrFail();
    }

    private function vendor(string $name, array $attributes = []): Vendor
    {
        return Vendor::query()->create(array_merge([
            'business_name' => $name,
            'email' => str()->slug($name).'-'.str()->random(5).'@example.com',
            'status' => 'active',
            'partner_display_status' => 'draft',
        ], $attributes));
    }

    private function vendorUpdate(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Updated Publication Partner',
            'email' => 'updated-publication-partner@example.com',
            'status' => 'active',
            'show_as_partner' => '0',
            'display_order' => '0',
        ], $overrides);
    }
}
