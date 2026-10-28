<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePopularityTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_popularity_can_be_enabled_and_disabled_from_edit_form(): void
    {
        $this->seed(MarketplaceUserSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $service = $this->service(['is_in_stock' => true]);

        $editResponse = $this->actingAs($admin)
            ->get(route('services.edit', $service))
            ->assertOk()
            ->assertSee('Mark as Popular');
        $this->assertSame(0, preg_match('/name="is_popular"[^>]*checked/s', $editResponse->getContent()));

        $payload = [
            'title' => $service->title,
            'price' => $service->price,
            'is_popular' => '1',
        ];

        $this->put(route('services.update', $service), $payload)
            ->assertRedirect(route('services.index'));

        $service->refresh();
        $this->assertTrue($service->is_popular);
        $editResponse = $this->actingAs($admin)
            ->get(route('services.edit', $service))
            ->assertOk();
        $this->assertMatchesRegularExpression('/name="is_popular"[^>]*checked/s', $editResponse->getContent());

        $this->put(route('services.update', $service), [
            'title' => $service->title,
            'price' => $service->price,
        ])->assertRedirect(route('services.index'));

        $service->refresh();
        $this->assertFalse($service->is_popular);
        $this->assertTrue($service->is_in_stock);
    }

    public function test_homepage_popular_services_section_only_renders_marked_services(): void
    {
        $popular = $this->service(['is_popular' => true]);
        $this->service(['title' => 'Not a popular service', 'slug' => 'not-a-popular-service']);

        $html = view('front.section.popular-service')->render();

        $this->assertStringContainsString($popular->title, $html);
        $this->assertStringNotContainsString('Not a popular service', $html);
    }

    public function test_services_pagination_uses_bootstrap_controls_and_preserves_query_parameters(): void
    {
        $this->seed(MarketplaceUserSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        for ($number = 1; $number <= 21; $number++) {
            $this->service([
                'title' => 'Paginated service '.$number,
                'slug' => 'paginated-service-'.$number,
            ]);
        }

        $firstPage = $this->actingAs($admin)->get(route('services.index', [
            'page' => 1,
            'filter' => 'preserved',
        ]))->assertOk();
        $firstHtml = $firstPage->getContent();
        $firstPageIds = $firstPage->viewData('services')->getCollection()->modelKeys();
        $this->assertStringContainsString('pagination', $firstHtml);
        $this->assertStringContainsString('aria-disabled="true"', $firstHtml);
        $this->assertSame(10, count($firstPageIds));
        $this->assertStringContainsString('page=2', $firstHtml);
        $this->assertStringContainsString('filter=preserved', $firstHtml);
        $this->assertStringNotContainsString('h-5 w-5', $firstHtml);

        $secondPage = $this->get(route('services.index', [
            'page' => 2,
            'filter' => 'preserved',
        ]))->assertOk();
        $secondPageIds = $secondPage->viewData('services')->getCollection()->modelKeys();
        $this->assertCount(10, $secondPageIds);
        $this->assertSame([], array_intersect($firstPageIds, $secondPageIds));

        $lastPage = $this->get(route('services.index', [
            'page' => 3,
            'filter' => 'preserved',
        ]))->assertOk();
        $lastHtml = $lastPage->getContent();
        $this->assertCount(1, $lastPage->viewData('services')->getCollection());
        $this->assertStringContainsString('aria-disabled="true"', $lastHtml);
        $this->assertStringContainsString('page=2', $lastHtml);
        $this->assertStringContainsString('filter=preserved', $lastHtml);
        $this->assertStringContainsString('>3</span>', $lastHtml);
        $this->assertStringNotContainsString('h-5 w-5', $lastHtml);
    }

    private function service(array $overrides = []): Service
    {
        return Service::query()->create(array_merge([
            'title' => 'Service '.str()->random(8),
            'slug' => 'service-'.str()->random(8),
            'price' => 10000,
            'is_popular' => false,
            'is_in_stock' => false,
        ], $overrides));
    }
}
