<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\ShopProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductReviewService
{
    /** Orders in these states count as a received purchase. */
    public const REVIEWABLE_ORDER_STATUSES = ['delivered', 'completed'];

    public const SORTS = [
        'recent' => 'Most recent',
        'highest' => 'Highest rating',
        'lowest' => 'Lowest rating',
    ];

    /**
     * The customer's latest received order containing this product, or null if they never received it.
     */
    public function verifiedOrder(Customer $customer, ShopProduct $product): ?Order
    {
        return Order::query()
            ->where('customer_id', $customer->id)
            ->whereIn('fulfillment_status', self::REVIEWABLE_ORDER_STATUSES)
            ->whereHas('items', fn ($items) => $items->where('shop_product_id', $product->id))
            ->latest('id')
            ->first();
    }

    /**
     * Create or update the customer's review. Throws if the customer has not received the product.
     */
    public function save(Customer $customer, ShopProduct $product, array $data): ProductReview
    {
        $order = $this->verifiedOrder($customer, $product);
        if (! $order) {
            throw new \DomainException('Only customers who received this product can review it.');
        }

        $review = ProductReview::query()->firstOrNew([
            'shop_product_id' => $product->id,
            'customer_id' => $customer->id,
        ]);

        $review->fill([
            'order_id' => $order->id,
            'rating' => (int) $data['rating'],
            'title' => filled($data['title'] ?? null) ? trim($data['title']) : null,
            'body' => trim($data['body']),
        ]);
        // A review hidden by a moderator stays hidden when the customer edits it.
        $review->status ??= 'published';
        $review->save();

        return $review;
    }

    /**
     * Average, count and per-star counts of published reviews.
     *
     * @return array{average: float, count: int, distribution: array<int, int>}
     */
    public function summary(ShopProduct $product): array
    {
        $counts = $product->publishedReviews()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $distribution[$star] = (int) ($counts[$star] ?? 0);
        }

        $count = array_sum($distribution);
        $sum = 0;
        foreach ($distribution as $star => $total) {
            $sum += $star * $total;
        }

        return [
            'average' => $count > 0 ? round($sum / $count, 1) : 0.0,
            'count' => $count,
            'distribution' => $distribution,
        ];
    }

    public function list(ShopProduct $product, string $sort = 'recent', ?int $rating = null, int $perPage = 6): LengthAwarePaginator
    {
        $query = $product->publishedReviews()->with('customer:id,name');

        if ($rating !== null && $rating >= 1 && $rating <= 5) {
            $query->where('rating', $rating);
        }

        match ($sort) {
            'highest' => $query->orderByDesc('rating')->latest('id'),
            'lowest' => $query->orderBy('rating')->latest('id'),
            default => $query->latest('id'),
        };

        return $query->paginate($perPage, ['*'], 'reviews_page')->withQueryString()->fragment('reviews');
    }
}
