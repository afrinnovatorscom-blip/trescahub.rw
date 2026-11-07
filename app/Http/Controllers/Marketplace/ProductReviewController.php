<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\ShopProduct;
use App\Services\ProductReviewService;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function __construct(private ProductReviewService $reviews) {}

    public function store(Request $request, ShopProduct $shopProduct)
    {
        abort_unless($shopProduct->isPubliclyVisible(), 404);

        $data = $request->validateWithBag('review', [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'rating.required' => 'Choose a star rating.',
            'body.min' => 'Tell other customers a little more (at least 10 characters).',
        ]);

        try {
            $review = $this->reviews->save($request->user('customers'), $shopProduct, $data);
        } catch (\DomainException $exception) {
            return back()->withErrors(['rating' => $exception->getMessage()], 'review');
        }

        return redirect(route('shop-products.show', $shopProduct).'#reviews')
            ->with('review_status', $review->wasRecentlyCreated ? 'Thank you! Your review has been posted.' : 'Your review has been updated.');
    }

    public function destroy(Request $request, ShopProduct $shopProduct)
    {
        ProductReview::query()
            ->where('shop_product_id', $shopProduct->id)
            ->where('customer_id', $request->user('customers')->id)
            ->delete();

        return redirect(route('shop-products.show', $shopProduct).'#reviews')
            ->with('review_status', 'Your review has been deleted.');
    }
}
