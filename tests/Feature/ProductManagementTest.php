<?php

namespace Tests\Feature;

use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\PublicAsset;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private Shopcategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);

        $this->category = Shopcategory::query()->create([
            'name' => 'Test products',
            'slug' => 'test-products',
            'active' => true,
        ]);
    }

    public function test_admin_product_form_creates_approved_products_and_promotion_never_publishes_them_implicitly(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.shop-products.create'))
            ->assertOk()
            ->assertSee('Product Information')
            ->assertSee('Promote on Homepage')
            ->assertSee('Main Product Image (Thumbnail)')
            ->assertSee('Additional Product Images (Optional)')
            ->assertSee('Additional Settings')
            ->assertSee('Assign to Vendor')
            ->assertSee('Unit of Measurement')
            ->assertSee('Initial Stock Quantity')
            ->assertDontSee('Track physical stock')
            ->assertSee('Create Product')
            ->assertDontSee('Enable Discount')
            ->assertDontSee('Delivery Time (working days)')
            ->assertSee('name="working_days" value="1"', false)
            ->assertDontSee('Approve Now')
            ->assertSee('Publish after saving')
            ->assertDontSee('SKU (Automatically Assigned)')
            ->assertDontSee('Current Stock');

        $standardPayload = $this->payload([
            'product_name' => 'Standard product without discount',
        ]);
        unset($standardPayload['working_days']);
        $standardPayload['working_days'] = '1';
        $this->actingAs($admin)->post(route('admin.shop-products.store'), $standardPayload)
            ->assertRedirect(route('admin.shop-products.index'));
        $standard = ShopProduct::query()->where('product_name', 'Standard product without discount')->firstOrFail();
        $this->assertSame('approved', $standard->approval_status);
        $this->assertSame('unpublished', $standard->publication_status);
        $this->assertSame('TH-PROD-'.str_pad((string) $standard->id, 4, '0', STR_PAD_LEFT), $standard->sku);
        $this->assertFalse($standard->is_popular);
        $this->assertFalse($standard->discount_enabled);
        $this->assertFalse($standard->tracks_inventory);
        $this->assertFalse($standard->hasValidDiscount());
        $this->assertSame(1, (int) $standard->working_days);

        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Unpublished promoted product',
            'is_popular' => 1,
            'discount_enabled' => 1,
            'original_price' => 1250,
        ]))->assertRedirect(route('admin.shop-products.index'));

        $pending = ShopProduct::query()->where('product_name', 'Unpublished promoted product')->firstOrFail();
        $this->assertSame('approved', $pending->approval_status);
        $this->assertSame('unpublished', $pending->publication_status);
        $this->assertTrue($pending->discount_enabled);
        $this->assertTrue($pending->is_popular);
        $this->assertFalse($pending->isPubliclyListed());
        $this->get(route('home'))->assertDontSee('Unpublished promoted product');

        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Approved sale product',
            'is_popular' => 1,
            'discount_enabled' => 1,
            'original_price' => 1250,
            'publish_now' => 1,
        ]))->assertRedirect(route('admin.shop-products.index'));

        $published = ShopProduct::query()->where('product_name', 'Approved sale product')->firstOrFail();
        $this->assertSame('approved', $published->approval_status);
        $this->assertSame('published', $published->publication_status);
        $this->assertTrue($published->hasValidDiscount());
        $this->get(route('home'))->assertSee('Approved sale product')->assertSee('20% off');
        $this->get(route('shop-products.show', $published))->assertOk()
            ->assertSee('RWF 1,000')
            ->assertSee('RWF 1,250')
            ->assertSee('20% off')
            ->assertDontSee('Choose your preferred option')
            ->assertDontSee('id="productVariationSelector"', false)
            ->assertSee('Request this product');
        $this->getJson('/api/shop-products')->assertOk()->assertJsonFragment(['percentage' => 20]);
        $editResponse = $this->actingAs($admin)->get(route('admin.shop-products.edit', $published));
        $editResponse->assertOk()
            ->assertSee('Edit Product')
            ->assertDontSee('Delivery Time (working days)')
            ->assertSee('Enable Discount')
            ->assertSee('Track physical stock')
            ->assertSee('aria-labelledby="stock-heading"', false)
            ->assertSee('Promote on Homepage');
        $editHtml = $editResponse->getContent();
        $this->assertLessThan(
            strpos($editHtml, 'aria-labelledby="promotion-heading"'),
            strpos($editHtml, 'aria-labelledby="stock-heading"')
        );
        $this->assertStringNotContainsString('Pricing and Inventory', $editHtml);
    }

    public function test_admin_can_create_and_edit_price_variations_with_the_default_price_synced(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.shop-products.create'))
            ->assertOk()
            ->assertSee('Enable Price Variations')
            ->assertSee('Add Variation');

        $this->actingAs($admin)
            ->from(route('admin.shop-products.create'))
            ->post(route('admin.shop-products.store'), $this->payload([
                'discount_enabled' => 1,
                'original_price' => 1500,
                'price_variations_enabled' => 1,
                'price_variations' => [[
                    'feature_label' => 'Package',
                    'variation_value' => 'Standard',
                    'price' => 1200,
                    'is_default' => 1,
                ]],
            ]))
            ->assertRedirect(route('admin.shop-products.create'))
            ->assertSessionHasErrors('discount_enabled');

        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Product with options',
            'price_variations_enabled' => 1,
            'price_variations' => [
                [
                    'feature_label' => 'Package',
                    'variation_value' => 'Standard',
                    'price' => 1200,
                    'is_default' => 1,
                ],
                [
                    'feature_label' => 'Package',
                    'variation_value' => 'Premium',
                    'price' => 1800,
                    'is_default' => 0,
                ],
            ],
            'publish_now' => 1,
        ]))->assertRedirect(route('admin.shop-products.index'));

        $product = ShopProduct::query()->where('product_name', 'Product with options')->firstOrFail();
        $variations = $product->variations()->orderBy('id')->get();
        $this->assertCount(2, $variations);
        $this->assertSame(1200.0, (float) $product->price);
        $this->assertTrue($variations[0]->is_default);
        $this->assertFalse($variations[1]->is_default);

        $this->get(route('shop-products.show', $product))
            ->assertOk()
            ->assertSee('Choose your preferred option')
            ->assertSee('Standard')
            ->assertSee('Premium')
            ->assertSee('RWF 1,800')
            ->assertSee('data-product-variation-choice', false)
            ->assertSee('id="selectedProductPrice"', false)
            ->assertSee('name="variation_id" value="'.$variations[0]->id.'"', false)
            ->assertSee('variation_id='.$variations[0]->id, false)
            ->assertSee('checked', false);

        $this->actingAs($admin)->put(route('admin.shop-products.update', $product), $this->payload([
            'product_name' => $product->product_name,
            'price_variations_enabled' => 1,
            'price_variations' => [
                [
                    'id' => $variations[0]->id,
                    'feature_label' => 'Package',
                    'variation_value' => 'Standard',
                    'price' => 1200,
                    'is_default' => 0,
                ],
                [
                    'id' => $variations[1]->id,
                    'feature_label' => 'Package',
                    'variation_value' => 'Premium',
                    'price' => 2000,
                    'is_default' => 1,
                ],
            ],
            'publish_now' => 1,
        ]))->assertRedirect(route('admin.shop-products.index'));

        $this->assertSame(2000.0, (float) $product->fresh()->price);
        $this->assertTrue($variations[1]->fresh()->is_default);
        $this->assertSame(2000.0, (float) $variations[1]->fresh()->price);
    }

    public function test_generated_sku_is_immutable_when_editing_a_product(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Generated SKU product',
            'sku' => 'CUSTOM-SKU',
        ]))->assertRedirect(route('admin.shop-products.index'));

        $product = ShopProduct::query()->where('product_name', 'Generated SKU product')->firstOrFail();
        $originalSku = $product->sku;
        $this->assertSame('TH-PROD-'.str_pad((string) $product->id, 4, '0', STR_PAD_LEFT), $originalSku);

        $this->actingAs($admin)->put(route('admin.shop-products.update', $product), $this->payload([
            'product_name' => $product->product_name,
            'sku' => 'ATTEMPTED-CHANGE',
        ]))->assertRedirect(route('admin.shop-products.index'));

        $this->assertSame($originalSku, $product->fresh()->sku);
    }

    public function test_generated_sku_avoids_values_already_used_by_legacy_products(): void
    {
        $nextProductId = (int) ShopProduct::query()->max('id') + 2;
        $legacy = ShopProduct::query()->create($this->payload([
            'product_name' => 'Legacy SKU record',
            'slug' => 'legacy-sku-record',
            'sku' => 'TH-PROD-'.str_pad((string) $nextProductId, 4, '0', STR_PAD_LEFT),
        ]));
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Generated around legacy SKU',
        ]))->assertRedirect(route('admin.shop-products.index'));

        $generated = ShopProduct::query()->where('product_name', 'Generated around legacy SKU')->firstOrFail();
        $this->assertSame('TH-PROD-'.str_pad((string) $generated->id, 4, '0', STR_PAD_LEFT).'-1', $generated->sku);
        $this->assertNotSame($legacy->sku, $generated->sku);
    }

    public function test_product_slugs_are_generated_uniquely_from_the_name(): void
    {
        $admin = $this->admin();

        foreach (['First repeated product', 'First repeated product'] as $name) {
            $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
                'product_name' => $name,
            ]))->assertRedirect(route('admin.shop-products.index'));
        }

        $this->assertDatabaseHas('shop_products', ['slug' => 'first-repeated-product']);
        $this->assertDatabaseHas('shop_products', ['slug' => 'first-repeated-product-1']);
    }

    public function test_product_validation_rejects_invalid_discount_status_and_cross_category_subcategory(): void
    {
        $admin = $this->admin();
        $otherCategory = Shopcategory::query()->create([
            'name' => 'Other products',
            'slug' => 'other-products',
            'active' => true,
        ]);
        $subcategory = Shopcategory::query()->create([
            'name' => 'Other subcategory',
            'slug' => 'other-subcategory',
            'parent_id' => $otherCategory->id,
            'active' => true,
        ]);

        $this->actingAs($admin)->from(route('admin.shop-products.create'))
            ->post(route('admin.shop-products.store'), $this->payload([
                'product_name' => '',
                'status' => 'unavailable',
                'discount_enabled' => 1,
                'original_price' => 500,
                'subcategory_id' => $subcategory->id,
            ]))
            ->assertRedirect(route('admin.shop-products.create'))
            ->assertSessionHasErrors(['product_name', 'status', 'original_price', 'subcategory_id']);

        $this->assertDatabaseMissing('shop_products', ['product_name' => '']);
    }

    public function test_finished_products_are_not_public_or_purchasable(): void
    {
        $finished = ShopProduct::query()->create($this->payload([
            'product_name' => 'Finished item',
            'slug' => 'finished-item',
            'status' => 'in_stock',
            'is_finished' => true,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]));

        $this->assertFalse($finished->isPubliclyVisible());
        $this->assertFalse($finished->canPurchase(1));
        $this->assertDatabaseHas('shop_products', ['id' => $finished->id, 'is_finished' => true]);
        $this->get('/shop')->assertDontSee('Finished item');
        $this->get(route('shop-products.show', $finished))->assertNotFound();
    }

    public function test_out_of_stock_status_overrides_positive_tracked_quantity(): void
    {
        $outOfStock = ShopProduct::query()->create($this->payload([
            'product_name' => 'Unavailable tracked item',
            'slug' => 'unavailable-tracked-item',
            'status' => 'out_of_stock',
            'tracks_inventory' => true,
            'stock_quantity' => 5,
            'reserved_quantity' => 0,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]));

        $this->assertSame('out_of_stock', $outOfStock->customerAvailability());
        $this->assertFalse($outOfStock->canPurchase(1));
        $this->get('/shop')->assertSee('Unavailable tracked item');
        $this->get(route('shop-products.show', $outOfStock))->assertOk()
            ->assertSee('Add to cart')
            ->assertSee('disabled', false);
    }

    public function test_gallery_uploads_are_saved_with_the_product_and_displayed_on_detail_page(): void
    {
        $admin = $this->admin();
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII=');
        $mainImage = UploadedFile::fake()->createWithContent('main.png', $imageContent);
        $firstGalleryContent = $imageContent.'first';
        $secondGalleryContent = $imageContent.'second';
        $firstGalleryImage = UploadedFile::fake()->createWithContent('side.png', $firstGalleryContent);
        $secondGalleryImage = UploadedFile::fake()->createWithContent('back.png', $secondGalleryContent);

        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Gallery product',
            'thumbnail' => $mainImage,
            'images' => [$firstGalleryImage, $secondGalleryImage],
            'gallery_order_present' => 1,
            'gallery_order' => ['upload:1', 'upload:0'],
            'approve_now' => 1,
            'publish_now' => 1,
        ]))->assertRedirect(route('admin.shop-products.index'));

        $product = ShopProduct::query()->where('product_name', 'Gallery product')->firstOrFail();
        $this->assertNotEmpty($product->thumbnail);
        $this->assertCount(2, $product->images);
        $this->assertFileExists(public_path('assets/'.$product->thumbnail));
        $this->assertFileExists(public_path('assets/'.$product->images[0]));
        $this->assertFileExists(public_path('assets/'.$product->images[1]));
        $this->assertSame(hash('sha256', $secondGalleryContent), hash_file('sha256', public_path('assets/'.$product->images[0])));
        $this->assertSame(hash('sha256', $firstGalleryContent), hash_file('sha256', public_path('assets/'.$product->images[1])));
        $this->get(route('shop-products.show', $product))->assertOk()
            ->assertSee('productMainImage')
            ->assertSee('productGallery')
            ->assertSee('Product image 3');

        $removedImage = $product->images[0];
        $remainingImage = $product->images[1];
        $updatePayload = $this->payload([
            'product_name' => $product->product_name,
            'gallery_order_present' => 1,
            'gallery_order' => ['existing:'.base64_encode($remainingImage)],
        ]);
        $this->actingAs($admin)->put(route('admin.shop-products.update', $product), $updatePayload)
            ->assertRedirect(route('admin.shop-products.index'));
        $this->assertSame([$remainingImage], $product->fresh()->images);
        $this->assertFileDoesNotExist(public_path('assets/'.$removedImage));

        unset($updatePayload['gallery_order']);
        $this->actingAs($admin)->put(route('admin.shop-products.update', $product), $updatePayload)
            ->assertRedirect(route('admin.shop-products.index'));
        $this->assertSame([], $product->fresh()->images);
        $this->assertFileDoesNotExist(public_path('assets/'.$remainingImage));
        PublicAsset::delete($product->thumbnail);
    }

    public function test_main_thumbnail_can_be_removed_without_removing_additional_images(): void
    {
        $admin = $this->admin();
        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII=');
        $thumbnail = UploadedFile::fake()->createWithContent('main.png', $imageContent);
        $galleryImage = UploadedFile::fake()->createWithContent('side.png', $imageContent.'side');

        $this->actingAs($admin)->post(route('admin.shop-products.store'), $this->payload([
            'product_name' => 'Thumbnail removal product',
            'thumbnail' => $thumbnail,
            'images' => [$galleryImage],
            'gallery_order_present' => 1,
            'gallery_order' => ['upload:0'],
        ]))->assertRedirect(route('admin.shop-products.index'));

        $product = ShopProduct::query()->where('product_name', 'Thumbnail removal product')->firstOrFail();
        $oldThumbnail = $product->thumbnail;
        $galleryImagePath = $product->images[0];

        $this->actingAs($admin)->put(route('admin.shop-products.update', $product), $this->payload([
            'product_name' => $product->product_name,
            'remove_thumbnail' => 1,
            'gallery_order_present' => 1,
            'gallery_order' => ['existing:'.base64_encode($galleryImagePath)],
        ]))->assertRedirect(route('admin.shop-products.index'));

        $this->assertNull($product->fresh()->thumbnail);
        $this->assertSame([$galleryImagePath], $product->fresh()->images);
        $this->assertFileDoesNotExist(public_path('assets/'.$oldThumbnail));
        $this->assertFileExists(public_path('assets/'.$galleryImagePath));

        PublicAsset::delete($galleryImagePath);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        return $user->fresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Test product',
            'description' => 'A full product description.',
            'price' => 1000,
            'working_days' => 2,
            'status' => 'active',
            'shop_category_id' => $this->category->id,
        ], $overrides);
    }
}
