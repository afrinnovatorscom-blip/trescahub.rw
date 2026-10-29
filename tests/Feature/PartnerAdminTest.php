<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PartnerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_partner_form_contains_profile_and_publication_fields_without_partner_type(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $this->actingAs($admin, 'web')
            ->get(route('partners.create'))
            ->assertOk()
            ->assertSee('Partner logo')
            ->assertSee('Partner name')
            ->assertSee('Short description')
            ->assertSee('Website URL')
            ->assertSee('Show on website')
            ->assertSee('Save partner')
            ->assertDontSee('Partner Type');
    }

    public function test_admin_can_create_edit_and_update_partner_visibility_and_logo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $logoPaths = [];

        try {
            $createResponse = $this->actingAs($admin, 'web')->post(route('partners.store'), [
                'title' => 'Northwind Partners',
                'description' => 'Partner description.',
                'website_url' => 'https://northwind.example',
                'show_on_website' => '1',
                'status' => 'active',
                'logo' => $this->fakePng('northwind.png'),
            ])->assertRedirect(route('partners.index'));

            $partner = Partner::query()->where('title', 'Northwind Partners')->firstOrFail();
            $logoPaths[] = $partner->logo_path;
            $this->assertSame('Partner description.', $partner->description);
            $this->assertSame('https://northwind.example', $partner->website_url);
            $this->assertTrue($partner->show_on_website);
            $this->assertSame('active', $partner->status);
            $this->assertFileExists(public_path('assets/'.$partner->logo_path));
            $this->assertNotNull($createResponse->getSession()->get('success'));

            $this->get(route('partners.index'))
                ->assertOk()
                ->assertSee('Northwind Partners')
                ->assertSee('Shown on website')
                ->assertSee('Active');

            $editResponse = $this->put(route('partners.update', $partner), [
                'title' => 'Northwind Group',
                'description' => 'Updated partner description.',
                'website_url' => 'https://group.example',
                'show_on_website' => '1',
                'status' => 'active',
                'logo' => $this->fakePng('northwind-updated.png'),
            ])->assertRedirect(route('partners.index'));

            $partner->refresh();
            $logoPaths[] = $partner->logo_path;
            $this->assertSame('Northwind Group', $partner->title);
            $this->assertSame('Updated partner description.', $partner->description);
            $this->assertSame('https://group.example', $partner->website_url);
            $this->assertTrue($partner->show_on_website);
            $this->assertSame('active', $partner->status);
            $this->assertFileExists(public_path('assets/'.$partner->logo_path));
            $this->assertFileDoesNotExist(public_path('assets/'.$logoPaths[0]));
            $this->assertNotNull($editResponse->getSession()->get('success'));

            $this->get(route('home'))
                ->assertOk()
                ->assertSee('Northwind Group')
                ->assertSee('Updated partner description.')
                ->assertSee('href="https://group.example"', false)
                ->assertSee(asset('assets/'.$partner->logo_path), false)
                ->assertDontSee('Northwind Partners');

            $this->put(route('partners.update', $partner), [
                'title' => 'Northwind Group',
                'description' => 'Updated partner description.',
                'website_url' => 'https://group.example',
                'show_on_website' => '0',
                'status' => 'active',
            ])->assertRedirect(route('partners.index'));

            $partner->refresh();
            $this->assertFalse($partner->show_on_website);
            $this->assertSame('active', $partner->status);
            $this->get(route('home'))
                ->assertOk()
                ->assertDontSee('Northwind Group');
            $this->get(route('partners.index'))
                ->assertOk()
                ->assertSee('Northwind Group')
                ->assertSee('Hidden');

            $this->put(route('partners.update', $partner), [
                'title' => 'Northwind Group',
                'description' => 'Updated partner description.',
                'website_url' => 'https://group.example',
                'show_on_website' => '1',
                'status' => 'inactive',
            ])->assertRedirect(route('partners.index'));

            $partner->refresh();
            $this->assertTrue($partner->show_on_website);
            $this->assertSame('inactive', $partner->status);
            $this->get(route('home'))
                ->assertOk()
                ->assertDontSee('Northwind Group');
        } finally {
            foreach ($logoPaths as $logoPath) {
                if ($logoPath) {
                    File::delete(public_path('assets/'.$logoPath));
                }
            }
        }
    }

    public function test_partner_form_validates_required_name_website_and_logo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $this->actingAs($admin, 'web')
            ->from(route('partners.create'))
            ->post(route('partners.store'), [
                'title' => '',
                'description' => '',
                'website_url' => 'not-a-url',
                'show_on_website' => '1',
                'status' => 'active',
                'logo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('partners.create'))
            ->assertSessionHasErrors(['title', 'website_url', 'logo']);
    }

    private function fakePng(string $filename): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $filename,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
        );
    }
}
