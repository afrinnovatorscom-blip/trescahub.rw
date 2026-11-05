<?php

namespace Tests\Feature;

use App\Models\ShopProduct;
use App\Models\Shopcategory;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApprovalPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Vendor $vendor;

    private Shopcategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MarketplaceUserSeeder::class);
        $this->admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->vendor = Vendor::query()->where('reference_number', 'VND-SEED-001')->firstOrFail();
        $this->category = Shopcategory::query()->create([
            'name' => 'Phone Covers',
            'slug' => 'phone-covers-category',
            'active' => true,
        ]);
    }

    public function test_approval_updates_database_and_review_page_shows_success_without_approve_button(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)->get(route('admin.shop-products.show', $product))
            ->assertOk()
            ->assertSee('Approve &amp; Publish', false)
            ->assertSee('Selling Price (RWF)')
            ->assertSee('name="price"', false)
            ->assertSee('value="10000.00"', false)
            ->assertSee('data-approval-form', false)
            ->assertDontSee('Catalog Publication');

        $this->from(route('admin.shop-products.show', $product))
            ->post(route('marketplace.products.approve', $product), [
            'note' => 'Reviewed and approved.',
            'price' => 12500.50,
        ])->assertRedirect(route('admin.shop-products.show', $product))
            ->assertSessionHas('success', 'Product approved and published successfully.');

        $this->assertSame('approved', $product->fresh()->approval_status);
        $this->assertSame('published', $product->fresh()->publication_status);
        $this->assertSame('12500.50', $product->fresh()->price);
        $this->assertNotNull($product->fresh()->approved_at);

        $this->get(route('admin.shop-products.show', $product))
            ->assertOk()
            ->assertSee('Product approved and published successfully.')
            ->assertSee('This product is approved.')
            ->assertSee('Published')
            ->assertDontSee('Approve &amp; Publish', false)
            ->assertDontSee('Catalog Publication');
    }

    public function test_approval_queue_shows_submitted_thumbnail_and_initial_fallback(): void
    {
        $withImage = $this->product([
            'product_name' => 'Pending Phone',
            'slug' => 'pending-phone',
            'thumbnail' => 'saved/old-phone.jpg',
            'pending_payload' => ['thumbnail' => 'public/assets/vendor/pending-phone.jpg'],
        ]);
        $withoutImage = $this->product([
            'product_name' => 'Table Lamp',
            'slug' => 'table-lamp',
        ]);

        $this->actingAs($this->admin)->get(route('marketplace.approvals.index'))
            ->assertOk()
            ->assertSee('Product</th>', false)
            ->assertSee('Category</th>', false)
            ->assertSee('Price</th>', false)
            ->assertSee('Initial Stock</th>', false)
            ->assertSee('Submitted</th>', false)
            ->assertSee('Phone Covers')
            ->assertSee('src="'.asset('assets/vendor/pending-phone.jpg').'"', false)
            ->assertDontSee('src="'.asset('assets/saved/old-phone.jpg').'"', false)
            ->assertSee('onerror="this.style.display=\'none\'"', false)
            ->assertSee('class="approval-product-initial">P</span>', false)
            ->assertSee('class="approval-product-initial">T</span>', false)
            ->assertSee('class="btn btn-sm btn-primary approval-review-button" href="'.route('admin.shop-products.show', $withImage).'"', false)
            ->assertSee('class="btn btn-sm btn-primary approval-review-button" href="'.route('admin.shop-products.show', $withoutImage).'"', false);
    }

    public function test_repeated_approval_is_rejected_without_adding_approval_history(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin)->from(route('admin.shop-products.show', $product))
            ->post(route('marketplace.products.approve', $product), ['price' => 10000])
            ->assertRedirect(route('admin.shop-products.show', $product));
        $historyCount = $product->approvalHistories()->where('action', 'approved')->count();

        $this->from(route('admin.shop-products.show', $product))
            ->post(route('marketplace.products.approve', $product), ['price' => 10000])
            ->assertRedirect(route('admin.shop-products.show', $product))
            ->assertSessionHasErrors('approval');

        $this->assertSame('approved', $product->fresh()->approval_status);
        $this->assertSame($historyCount, $product->approvalHistories()->where('action', 'approved')->count());
    }

    public function test_failed_approval_keeps_the_action_available_and_reports_the_error(): void
    {
        $product = $this->product([
            'tracks_inventory' => false,
            'pending_payload' => ['initial_stock_quantity' => 4],
        ]);

        $this->actingAs($this->admin)->from(route('admin.shop-products.show', $product))
            ->post(route('marketplace.products.approve', $product), ['price' => 12000])
            ->assertRedirect(route('admin.shop-products.show', $product))
            ->assertSessionHasErrors('initial_stock_quantity');

        $this->get(route('admin.shop-products.show', $product))
            ->assertOk()
            ->assertSee('Enable stock tracking before approving the pending initial stock.')
            ->assertSee('Approve &amp; Publish', false)
            ->assertSee('data-approval-form', false)
            ->assertDontSee('Catalog Publication');

        $this->assertSame('pending_approval', $product->fresh()->approval_status);
        $this->assertSame('10000.00', $product->fresh()->price);
    }

    public function test_invalid_approval_price_is_rejected_without_saving_or_publishing(): void
    {
        $product = $this->product();

        foreach ([0, -100, 'not-a-number'] as $price) {
            $this->actingAs($this->admin)->from(route('admin.shop-products.show', $product))
                ->post(route('marketplace.products.approve', $product), ['price' => $price])
                ->assertRedirect(route('admin.shop-products.show', $product))
                ->assertSessionHasErrors('price');
        }

        $this->assertSame('pending_approval', $product->fresh()->approval_status);
        $this->assertSame('unpublished', $product->fresh()->publication_status);
        $this->assertSame('10000.00', $product->fresh()->price);
    }

    private function product(array $overrides = []): ShopProduct
    {
        return ShopProduct::query()->create(array_merge([
            'product_name' => 'Phone Covers',
            'slug' => 'phone-covers',
            'status' => 'in_stock',
            'price' => 10000,
            'working_days' => 1,
            'shop_category_id' => $this->category->id,
            'vendor_id' => $this->vendor->id,
            'approval_status' => 'pending_approval',
            'publication_status' => 'unpublished',
        ], $overrides));
    }
}
