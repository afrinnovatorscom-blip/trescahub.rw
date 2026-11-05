<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/**
 * Review moderation. Uses the products.approve permission, like product approvals.
 */
class ProductReviewAdminController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeModeration($request);

        $filters = $request->validate([
            'status' => ['nullable', 'in:published,hidden'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $reviews = ProductReview::query()
            ->with(['product:id,product_name,slug', 'customer:id,name,email', 'order:id,reference'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['rating'] ?? null, fn ($query, $rating) => $query->where('rating', $rating))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('product_name', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = ProductReview::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('marketplace.reviews.index', [
            'reviews' => $reviews,
            'filters' => $filters,
            'counts' => $counts,
            'averageRating' => round((float) ProductReview::query()->published()->avg('rating'), 1),
        ]);
    }

    public function update(Request $request, ProductReview $review)
    {
        $this->authorizeModeration($request);

        $data = $request->validate([
            'status' => ['sometimes', 'in:published,hidden'],
            'admin_reply' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $changes = [];
        if (array_key_exists('status', $data) && $data['status'] !== $review->status) {
            $changes['status'] = [$review->status, $data['status']];
            $review->status = $data['status'];
        }
        if (array_key_exists('admin_reply', $data)) {
            $reply = filled($data['admin_reply']) ? trim($data['admin_reply']) : null;
            if ($reply !== $review->admin_reply) {
                $changes['admin_reply'] = $reply === null ? 'removed' : 'updated';
                $review->admin_reply = $reply;
                $review->admin_replied_at = $reply === null ? null : now();
            }
        }

        if ($changes !== []) {
            $review->moderated_by = $request->user()->id;
            $review->save();
            AuditLogger::log($request->user(), 'product_review.moderated', $review, $changes);
        }

        return back()->with('success', 'Review updated.');
    }

    private function authorizeModeration(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('products.approve'), 403);
    }
}
