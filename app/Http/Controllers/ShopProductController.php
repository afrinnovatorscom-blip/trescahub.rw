<?php

namespace App\Http\Controllers;

use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\Vendor;
use App\Services\ProductImageService;
use App\Services\ProductWorkflowService;
use App\Support\ProductPayload;
use App\Support\PublicAsset;
use App\Support\PublicProduct;
use Illuminate\Http\Request;
use Throwable;

class ShopProductController extends Controller
{
    public function __construct(
        private ProductWorkflowService $workflow,
        private ProductImageService $images,
    ) {}

    public function publicIndex(Request $request)
    {
        $categorySlug = $request->query('category');
        $categorySlug = is_string($categorySlug) ? trim($categorySlug) : null;
        if ($categorySlug === '' || strtolower($categorySlug) === 'all') {
            $categorySlug = null;
        }

        $subcategorySlug = $request->query('subcategory');
        $subcategorySlug = is_string($subcategorySlug) ? trim($subcategorySlug) : null;
        if ($subcategorySlug === '' || strtolower($subcategorySlug) === 'all') {
            $subcategorySlug = null;
        }

        $popularProducts = ShopProduct::query()->publicCatalog()
            ->with(['category', 'subcategory'])
            ->where('is_popular', true)
            ->latest()
            ->get();

        $catalogQuery = ShopProduct::query()->publicCatalog();

        $products = (clone $catalogQuery)
            ->with(['category', 'subcategory'])
            ->when($categorySlug, function ($query) use ($categorySlug) {
                $query->whereHas('category', fn ($category) => $category->where('slug', $categorySlug));
            })
            ->when($subcategorySlug, function ($query) use ($subcategorySlug) {
                $query->whereHas('subcategory', fn ($category) => $category->where('slug', $subcategorySlug));
            })
            ->when($request->status, function ($query) use ($request) {
                if ($request->status === 'in_stock') {
                    $query->where('status', 'in_stock')
                        ->where(fn ($availability) => $availability
                            ->where('tracks_inventory', false)
                            ->orWhereColumn('stock_quantity', '>', 'reserved_quantity'));
                } elseif ($request->status === 'out_of_stock') {
                    $query->where(fn ($availability) => $availability
                        ->where('status', 'out_of_stock')
                        ->orWhere(fn ($tracked) => $tracked
                            ->where('tracks_inventory', true)
                            ->whereColumn('stock_quantity', '<=', 'reserved_quantity')));
                }
            })
            ->when($request->search, function ($query) use ($request) {
                $term = '%'.$request->search.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('product_name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->latest()
            ->get();

        $allProductsCount = (clone $catalogQuery)->count();
        $categoryCounts = (clone $catalogQuery)
            ->selectRaw('shop_category_id, COUNT(*) as products_count')
            ->groupBy('shop_category_id')
            ->pluck('products_count', 'shop_category_id');

        $parentCategories = Shopcategory::with(['subcategories' => fn ($query) => $query->where('active', true)])
            ->whereNull('parent_id')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('front.pages.shop', compact(
            'products',
            'popularProducts',
            'parentCategories',
            'allProductsCount',
            'categoryCounts',
        ));
    }

    public function publicShow(ShopProduct $shopProduct)
    {
        abort_unless($shopProduct->isPubliclyVisible(), 404);

        $shopProduct->load(['category', 'subcategory']);

        $related = ShopProduct::query()->publicCatalog()
            ->with(['category', 'subcategory'])
            ->where('shop_category_id', $shopProduct->shop_category_id)
            ->where('id', '!=', $shopProduct->id)
            ->limit(4)
            ->get();

        return view('front.pages.shopproduct', compact('shopProduct', 'related'));
    }

    public function publicApi(Request $request)
    {
        $products = ShopProduct::query()->publicCatalog()
            ->with('category')
            ->latest()
            ->paginate(24)
            ->through(fn (ShopProduct $product) => PublicProduct::make($product));

        return response()->json($products);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ShopProduct::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'approval_status' => ['nullable', 'string', 'max:50'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
        ]);
        if (isset($filters['vendor_id'])) {
            abort_unless($request->user()->hasPermission('vendors.view'), 403);
        }

        $products = ShopProduct::query()
            ->with(['category', 'subcategory', 'vendor'])
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('product_name', 'like', '%'.$term.'%'))
            ->when($filters['approval_status'] ?? null, fn ($query, $status) => $query->where('approval_status', $status))
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->where('vendor_id', $vendorId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $vendors = $request->user()->hasPermission('vendors.view')
            ? Vendor::query()->orderBy('business_name')->get(['id', 'business_name'])
            : collect();

        return view('shop-products.index', compact('products', 'vendors'));
    }

    public function create()
    {
        $this->authorize('create', ShopProduct::class);

        $parentCategories = $this->categories();
        $vendors = $this->vendorOptions();
        $shopProduct = new ShopProduct;

        return view('shop-products.create', compact('parentCategories', 'vendors', 'shopProduct'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', ShopProduct::class);

        $validated = ProductPayload::normalize($request->validate(ProductPayload::rules(true)));
        [$validated, $newUploads, $obsoleteUploads] = $this->images->withUploads($request, $validated, null);
        $user = $request->user();

        try {
            $product = $this->workflow->createStaff(
                $user,
                $validated,
                $request->boolean('publish_now') && $user->hasPermission('products.publish'),
            );
        } catch (Throwable $exception) {
            foreach ($newUploads as $path) {
                PublicAsset::delete($path);
            }

            throw $exception;
        }

        $this->images->deleteUnreferenced($obsoleteUploads);

        $message = $product->isPubliclyListed()
            ? 'Product approved and published.'
        : 'Product saved.';

        return redirect()->route('admin.shop-products.index')->with('success', $message);
    }

    public function show(ShopProduct $shopProduct)
    {
        $this->authorize('view', $shopProduct);
        $shopProduct->load(['category', 'subcategory', 'vendor', 'locations', 'approvalHistories.actor', 'creator', 'submitter', 'approver']);

        return view('marketplace.products.show', [
            'product' => $shopProduct,
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function edit(ShopProduct $shopProduct)
    {
        $this->authorize('update', $shopProduct);
        $shopProduct->load(['category', 'subcategory']);
        $parentCategories = $this->categories();
        $vendors = $this->vendorOptions();

        return view('shop-products.edit', compact('shopProduct', 'parentCategories', 'vendors'));
    }

    public function update(Request $request, ShopProduct $shopProduct)
    {
        $this->authorize('update', $shopProduct);

        $validated = ProductPayload::normalize($request->validate(ProductPayload::rules()));
        [$validated, $newUploads, $obsoleteUploads] = $this->images->withUploads($request, $validated, $shopProduct);
        $user = $request->user();

        try {
            $this->workflow->updateStaff($shopProduct, $user, $validated);

            if ($request->boolean('publish_now') && $user->hasPermission('products.publish')) {
                $this->workflow->publish($shopProduct->refresh(), $user);
            }
        } catch (Throwable $exception) {
            foreach ($newUploads as $path) {
                PublicAsset::delete($path);
            }

            throw $exception;
        }

        $this->images->deleteUnreferenced($obsoleteUploads);

        return redirect()->route('admin.shop-products.index')->with('success', 'Product updated.');
    }

    public function destroy(ShopProduct $shopProduct)
    {
        $this->authorize('delete', $shopProduct);

        $pendingPayload = $shopProduct->pending_payload ?? [];
        $assets = array_filter(array_merge(
            [$shopProduct->thumbnail, $pendingPayload['thumbnail'] ?? null],
            $shopProduct->images ?? [],
            $pendingPayload['images'] ?? [],
        ));

        $name = $shopProduct->product_name;
        $shopProduct->delete();
        $this->images->deleteUnreferenced($assets);

        return redirect()->route('admin.shop-products.index')->with('success', "Product '{$name}' deleted.");
    }

    private function categories()
    {
        return Shopcategory::with('subcategories')->whereNull('parent_id')->orderBy('name')->get();
    }

    private function vendorOptions()
    {
        if (! auth()->user()->hasPermission('vendors.view') && ! auth()->user()->hasPermission('products.assign_vendor')) {
            return collect();
        }

        return \App\Models\Vendor::query()->orderBy('business_name')->get(['id', 'business_name', 'status']);
    }
}
