<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ProductReview;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\MarketplaceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private ShopProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MarketplaceUserSeeder::class);
        $category = Shopcategory::query()->create(['name' => 'Reviews', 'slug' => 'reviews', 'active' => true]);
        $this->product = ShopProduct::query()->create([
            'product_name' => 'Reviewed Drill',
            'slug' => 'reviewed-drill',
            'status' => 'in_stock',
            'price' => 20000,
            'working_days' => 2,
            'shop_category_id' => $category->id,
            'approval_status' => 'approved',
            'publication_status' => 'published',
        ]);
    }

    private function customer(string $name = 'Aline Uwase'): Customer
    {
        return Customer::query()->create([
            'name' => $name,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'phone' => '0788000000',
            'password' => 'valid-password',
        ]);
    }

    private function orderFor(Customer $customer, string $status = 'delivered'): void
    {
        $order = app(OrderService::class)->place([
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'delivery_address' => 'Kigali',
            'payment_method' => 'mtn_mobile_money',
        ], [['product' => $this->product, 'quantity' => 1]], null, $customer);

        $order->update(['fulfillment_status' => $status]);
    }

    private function review(array $overrides = []): array
    {
        return array_merge(['rating' => 4, 'title' => 'Solid tool', 'body' => 'Works well and arrived on time.'], $overrides);
    }

    public function test_product_page_shows_empty_state_and_sign_in_prompt_for_guests(): void
    {
        $this->get(route('shop-products.show', $this->product))
            ->assertOk()
            ->assertSee('Ratings &amp; Reviews', false)
            ->assertSee('No reviews yet')
            ->assertSee('to review this product after you receive it')
            ->assertDontSee('Write a review');
    }

    public function test_only_customers_who_received_the_product_can_review_it(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customers')
            ->get(route('shop-products.show', $this->product))
            ->assertSee('Only customers who received this product can review it.')
            ->assertDontSee('Write a review');
        $this->post(route('shop-products.reviews.store', $this->product), $this->review())
            ->assertSessionHasErrors('rating', null, 'review');

        // An order that is not delivered yet does not count
        $this->orderFor($customer, 'dispatched');
        $this->post(route('shop-products.reviews.store', $this->product), $this->review())
            ->assertSessionHasErrors('rating', null, 'review');
        $this->assertDatabaseCount('product_reviews', 0);

        $this->post(route('shop-products.reviews.store', $this->product), $this->review())->assertSessionHasErrors();
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_verified_customer_can_post_edit_and_delete_one_review(): void
    {
        $customer = $this->customer('Aline Uwase Mukamana');
        $this->orderFor($customer);

        $this->actingAs($customer, 'customers')
            ->get(route('shop-products.show', $this->product))
            ->assertSee('Write a review');

        $this->post(route('shop-products.reviews.store', $this->product), $this->review(['rating' => 9]))
            ->assertSessionHasErrors('rating', null, 'review');

        $this->post(route('shop-products.reviews.store', $this->product), $this->review())
            ->assertRedirect(route('shop-products.show', $this->product).'#reviews')
            ->assertSessionHas('review_status', 'Thank you! Your review has been posted.');

        $this->get(route('shop-products.show', $this->product))
            ->assertSee('Solid tool')
            ->assertSee('Works well and arrived on time.')
            ->assertSee('Verified purchase')
            ->assertSee('(1 review)')
            ->assertSee('Edit your review');

        // Other visitors only see a shortened name (signed-in pages also show it in the header).
        auth('customers')->logout();
        $this->get(route('shop-products.show', $this->product))
            ->assertSee('Aline M.')
            ->assertDontSee('Aline Uwase Mukamana');
        $this->actingAs($customer, 'customers');

        $this->post(route('shop-products.reviews.store', $this->product), $this->review(['rating' => 2, 'body' => 'Battery died after a week.']))
            ->assertSessionHas('review_status', 'Your review has been updated.');
        $this->assertDatabaseCount('product_reviews', 1);
        $this->assertSame(2, ProductReview::query()->first()->rating);

        $this->delete(route('shop-products.reviews.destroy', $this->product))->assertRedirect();
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_summary_filters_and_sorting(): void
    {
        foreach ([5, 5, 4, 1] as $index => $rating) {
            $customer = $this->customer('Customer '.$index);
            $this->orderFor($customer);
            $this->actingAs($customer, 'customers')
                ->post(route('shop-products.reviews.store', $this->product), $this->review(['rating' => $rating, 'title' => 'Title '.$rating.'-'.$index]));
        }

        $this->get(route('shop-products.show', $this->product))
            ->assertSee('3.8')
            ->assertSee('(4 reviews)')
            ->assertSee('Reviews (4)');

        $this->get(route('shop-products.show', ['shopProduct' => $this->product, 'reviews_rating' => 1]))
            ->assertSee('Title 1-3')
            ->assertDontSee('Title 5-0');

        $this->get(route('shop-products.show', ['shopProduct' => $this->product, 'reviews_sort' => 'lowest']))
            ->assertSeeInOrder(['Title 1-3', 'Title 4-2', 'Title 5-']);
    }

    public function test_admin_can_hide_and_reply_and_hidden_reviews_stay_hidden(): void
    {
        $customer = $this->customer();
        $this->orderFor($customer);
        $this->actingAs($customer, 'customers')
            ->post(route('shop-products.reviews.store', $this->product), $this->review(['title' => 'Rude words here']));
        $review = ProductReview::query()->firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $this->actingAs($admin, 'web')->get(route('marketplace.reviews.index'))
            ->assertOk()
            ->assertSee('Rude words here')
            ->assertSee('Reviewed Drill');

        $this->actingAs($admin, 'web')->patch(route('marketplace.reviews.update', $review), ['status' => 'hidden'])->assertRedirect();
        $this->actingAs($admin, 'web')->patch(route('marketplace.reviews.update', $review), ['admin_reply' => 'Thanks for the feedback!']);
        $this->assertSame('hidden', $review->fresh()->status);
        $this->assertNotNull($review->fresh()->admin_replied_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product_review.moderated']);

        // Hidden from the public (the author still sees it in their own edit form)
        auth('customers')->logout();
        $this->get(route('shop-products.show', $this->product))->assertDontSee('Rude words here');

        // Editing a hidden review does not republish it
        $this->actingAs($customer, 'customers')
            ->post(route('shop-products.reviews.store', $this->product), $this->review(['title' => 'Edited title']));
        $this->assertSame('hidden', $review->fresh()->status);

        $this->actingAs($admin, 'web')->patch(route('marketplace.reviews.update', $review), ['status' => 'published']);
        $this->get(route('shop-products.show', $this->product))
            ->assertSee('Edited title')
            ->assertSee('Response from TRESCA Hub')
            ->assertSee('Thanks for the feedback!');
    }

    public function test_staff_without_approval_permission_cannot_moderate(): void
    {
        $customer = $this->customer();
        $this->orderFor($customer);
        $review = ProductReview::query()->create([
            'shop_product_id' => $this->product->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'body' => 'Great product overall.',
        ]);

        $staff = User::factory()->create();
        $this->actingAs($staff, 'web')->get(route('marketplace.reviews.index'))->assertForbidden();
        $this->actingAs($staff, 'web')->patch(route('marketplace.reviews.update', $review), ['status' => 'hidden'])->assertForbidden();
        $this->assertSame('published', $review->fresh()->status);
    }

    public function test_delivered_order_page_links_to_reviews(): void
    {
        $customer = $this->customer();
        $this->orderFor($customer);
        $order = $customer->orders()->firstOrFail();

        $this->actingAs($customer, 'customers')
            ->get(route('account.orders.show', $order->reference))
            ->assertOk()
            ->assertSee('How was your order?')
            ->assertSee(route('shop-products.show', $this->product).'#reviews', false);
    }
}
