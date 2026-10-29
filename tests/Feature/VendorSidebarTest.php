<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class VendorSidebarTest extends TestCase
{
    use RefreshDatabase;

    private array $logoFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->logoFiles as $path) {
            File::delete(public_path($path));
        }

        parent::tearDown();
    }

    public function test_each_vendor_sidebar_destination_is_active_on_its_page(): void
    {
        $vendor = Vendor::query()->create([
            'business_name' => 'Sidebar Vendor',
            'status' => 'active',
            'email' => 'sidebar-vendor@example.com',
            'phone' => '0788000000',
        ]);
        $user = User::factory()->create(['vendor_id' => $vendor->id]);
        $user->assignRole('vendor');

        $destinations = [
            'vendor.dashboard' => 'Overview',
            'vendor.products.index' => 'My Products',
            'vendor.products.create' => 'Submit Product',
            'vendor.orders.index' => 'Orders',
            'vendor.deliveries.index' => 'Deliveries',
            'vendor.inventory' => 'Inventory',
            'marketplace.notifications.index' => 'Notifications',
            'vendor.profile' => 'Account Profile',
        ];

        foreach ($destinations as $routeName => $label) {
            $response = $this->actingAs($user)->get(route($routeName));
            $content = $response->assertOk()
                ->assertSee('Vendor Panel')
                ->assertDontSee('Assigned Requests')
                ->assertSee($label)
                ->getContent();

            $this->assertStringContainsString(
                'href="'.route($routeName).'" class="nav-item-link active"',
                $content,
                "{$routeName} should be active after loading or refreshing its page."
            );
            $this->assertSame(1, preg_match_all('/class="nav-item-link active"/', $content));
        }
    }

    public function test_admin_sidebar_branding_remains_unchanged(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertDontSee('Vendor Panel');
    }

    public function test_vendor_sidebar_uses_the_logged_in_vendors_existing_logo_with_a_load_error_fallback(): void
    {
        $logoPath = 'assets/vendors/logos/sidebar-logo.png';
        $absoluteLogoPath = public_path($logoPath);
        File::ensureDirectoryExists(dirname($absoluteLogoPath));
        File::put($absoluteLogoPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'));
        $this->logoFiles[] = $logoPath;

        $vendor = Vendor::query()->create([
            'business_name' => 'Logo Vendor',
            'business_logo_path' => 'vendors/logos/sidebar-logo.png',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['vendor_id' => $vendor->id]);
        $user->assignRole('vendor');

        $this->actingAs($user)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee(asset($logoPath), false)
            ->assertSee('alt="Logo Vendor logo"', false)
            ->assertSee('object-fit: cover;', false)
            ->assertSee('onerror="this.hidden = true; this.nextElementSibling.hidden = false;"', false)
            ->assertSee('>L</span>', false);
    }

    public function test_vendor_sidebar_uses_business_user_or_safe_default_initial_without_a_loadable_logo(): void
    {
        $vendor = Vendor::query()->create([
            'business_name' => 'Missing Logo Vendor',
            'business_logo_path' => 'vendors/logos/missing-logo.png',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['vendor_id' => $vendor->id]);
        $user->assignRole('vendor');

        $response = $this->actingAs($user)->get(route('vendor.dashboard'))->assertOk();
        $response->assertDontSee('missing-logo.png')
            ->assertSee('>M</span>', false);

        $vendor->update(['business_name' => '']);
        $user->update(['name' => 'Fallback User']);

        $this->actingAs($user->fresh())->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('>F</span>', false);

        $user->update(['name' => '']);

        $this->actingAs($user->fresh())->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee('>A</span>', false);
    }
}
