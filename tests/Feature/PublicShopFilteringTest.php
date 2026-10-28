<?php

namespace Tests\Feature;

use App\Models\Shopcategory;
use App\Models\ShopProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShopFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_filter_sentinels_return_the_public_catalog_and_category_counts(): void
    {
        $physical = $this->category('Physical');
        $digital = $this->category('Digital Services');
        $laptops = $this->category('Laptops & Computers', $physical);

        $this->product('Physical laptop', $physical, $laptops);
        $this->product('Physical headset', $physical);
        $this->product('Digital consultation', $digital);

        $this->get('/shop')
            ->assertOk()
            ->assertSee('Physical laptop')
            ->assertSee('Physical headset')
            ->assertSee('Digital consultation')
            ->assertSeeTextInOrder(['All Products', '3', 'Digital Services', '1', 'Physical', '2']);

        foreach ([
            '/shop?category=all&subcategory=all',
            '/shop?category=ALL&subcategory=ALL',
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Physical laptop')
                ->assertSee('Physical headset')
                ->assertSee('Digital consultation')
                ->assertSee('All Products')
                ->assertSee('Digital Services');
        }

        $this->get('/shop?category=physical&subcategory=all')
            ->assertOk()
            ->assertSee('Physical laptop')
            ->assertSee('Physical headset')
            ->assertDontSee('Digital consultation')
            ->assertSeeTextInOrder(['All Products', '3', 'Digital Services', '1', 'Physical', '2']);
    }

    public function test_category_subcategory_and_search_filters_combine_and_survive_refresh(): void
    {
        $physical = $this->category('Physical');
        $laptops = $this->category('Laptops & Computers', $physical);
        $phones = $this->category('Phones & Tablets', $physical);

        $this->product('Laptop Alpha', $physical, $laptops, 'A lightweight laptop.');
        $this->product('Laptop Beta', $physical, $laptops, 'A powerful laptop.');
        $this->product('Phone Alpha', $physical, $phones, 'A smartphone.');

        $url = '/shop?category=physical&subcategory=laptops-computers&search=Alpha';

        $this->get($url)
            ->assertOk()
            ->assertSee('Laptop Alpha')
            ->assertDontSee('Laptop Beta')
            ->assertDontSee('Phone Alpha');

        $this->get($url)
            ->assertOk()
            ->assertSee('Laptop Alpha')
            ->assertDontSee('Phone Alpha');

        $this->get('/shop?category=physical&subcategory=laptops-computers')
            ->assertOk()
            ->assertSee('Laptop Alpha')
            ->assertSee('Laptop Beta')
            ->assertDontSee('Phone Alpha');
    }

    private function category(string $name, ?Shopcategory $parent = null): Shopcategory
    {
        return Shopcategory::query()->create([
            'name' => $name,
            'slug' => str($name)->slug(),
            'parent_id' => $parent?->id,
            'active' => true,
        ]);
    }

    private function product(string $name, Shopcategory $parent, ?Shopcategory $subcategory = null, string $description = ''): ShopProduct
    {
        return ShopProduct::query()->create([
            'product_name' => $name,
            'slug' => str($name)->slug(),
            'description' => $description,
            'status' => 'in_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $parent->id,
            'subcategory_id' => $subcategory?->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
            'is_finished' => false,
        ]);
    }
}
