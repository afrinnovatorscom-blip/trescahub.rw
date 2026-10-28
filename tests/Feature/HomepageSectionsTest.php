<?php

namespace Tests\Feature;

use App\Models\Background;
use App\Models\Category;
use App\Models\Cta;
use App\Models\CtaCategory;
use App\Models\Service;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_empty_states_for_data_driven_sections(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Our Service Stores')
            ->assertSee('No service stores are available right now.')
            ->assertSee('Popular Services')
            ->assertSee('No popular services are available right now.')
            ->assertSee('Popular Products')
            ->assertSee('No popular products are available right now.')
            ->assertSee('How It Works')
            ->assertSee('How it works steps will be shown here when they are added.')
            ->assertSee('For Youth & Partners')
            ->assertSee('Featured information will appear here when it is added.')
            ->assertSee('No testimonials yet. Be the first to share your experience!');
    }

    public function test_homepage_renders_categories_and_cms_content_from_their_current_schemas(): void
    {
        $store = Category::create([
            'title' => 'Engineering & Technical Services',
            'slug' => 'engineering-technical-services',
            'description' => 'Technical services for the community.',
        ]);

        Service::create([
            'title' => 'Technical consultation',
            'slug' => 'technical-consultation',
            'price' => 12000,
            'classification_name' => $store->id,
            'is_popular' => true,
            'is_in_stock' => false,
            'thumbnail' => 'assets/services/thumbnails/technical.jpg',
        ]);

        Background::create([
            'title' => ['Built for Young Professionals'],
            'slug' => 'young-professionals',
            'description' => ['Affordable services for growing businesses.'],
            'thumbnail' => ['assets/backgrounds/hero.jpg'],
            'go_to' => ['services'],
            'button_text' => ['Explore Services'],
        ]);

        $ctaCategory = CtaCategory::create([
            'title' => 'For Youth',
            'slug' => 'for-youth',
            'description' => 'Support for young professionals.',
        ]);

        Cta::create([
            'cta_category_title' => $ctaCategory->title,
            'items' => [
                ['title' => 'Affordable services', 'description' => 'Find support for your next step.'],
            ],
        ]);

        Work::create(['title' => 'Browse', 'description' => 'Explore available services.']);

        $shopCategory = Shopcategory::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        ShopProduct::create([
            'product_name' => 'Wireless Earbuds',
            'slug' => 'wireless-earbuds',
            'status' => 'in_stock',
            'is_popular' => true,
            'description' => 'Wireless audio.',
            'price' => 25000,
            'working_days' => 1,
            'shop_category_id' => $shopCategory->id,
            'thumbnail' => 'shop-products/earbuds.jpg',
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Built for Young')
            ->assertSee('Professionals')
            ->assertSee('Affordable services for growing businesses.')
            ->assertSee(asset('assets/backgrounds/hero.jpg'), false)
            ->assertSee('Engineering & Technical Services')
            ->assertSee(route('services.category', $store->slug), false)
            ->assertSee('Browse 1 service')
            ->assertSee('Technical consultation')
            ->assertSee(asset('assets/services/thumbnails/technical.jpg'), false)
            ->assertSee('Popular Products')
            ->assertSee('Wireless Earbuds')
            ->assertSee(asset('assets/shop-products/earbuds.jpg'), false)
            ->assertSee('How It Works')
            ->assertSee('Browse')
            ->assertSee('For Youth')
            ->assertSee('Affordable services')
            ->assertSee('Find support for your next step.');
    }
}
