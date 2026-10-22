<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ShopCategoryController;
use App\Http\Controllers\ShopProductController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\BackgroundController;
use App\Http\Controllers\CtaCategoryController;
use App\Http\Controllers\CtaController;
use App\Http\Controllers\RequestServiceController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Marketplace\AdminAccessController;
use App\Models\ShopProduct;
use App\Models\Cta;


// ─── Public routes ────────────────────────────────────────────────────────────

Route::get('/', function () {
    $popularProducts = ShopProduct::query()->publicCatalog()
        ->with(['category', 'subcategory'])
        ->where('is_popular', true)
        ->latest()
        ->get();

    $ctas = Cta::with('category')->get();

    return view('welcome', compact('popularProducts', 'ctas'));
})->name('home');

Route::get('/services', [ServiceController::class, 'publicIndex'])->name('services');
Route::get('/services/{category}', [ServiceController::class, 'showByCategory'])->name('services.category');
Route::get('/service/{service:slug}', [ServiceController::class, 'publicShow'])->name('service.show');
Route::get('/categories', [CategoryController::class, 'publicIndex'])->name('categories.public');

// ── Request Service ───────────────────────────────────────────────────────────
Route::get('/request-service',  [RequestServiceController::class, 'create'])->name('request-service');
Route::post('/request-service', [RequestServiceController::class, 'store'])->name('request-service.store');

// ── Shop (Public) ─────────────────────────────────────────────────────────────
Route::get('/shop', [ShopProductController::class, 'publicIndex'])->name('shop');
Route::get('/shop/{shopProduct:slug}', [ShopProductController::class, 'publicShow'])->name('shop-products.show');

// ── Partners (Public) ─────────────────────────────────────────────────────────
Route::get('/for-partners', [PartnerController::class, 'publicIndex'])->name('for-partners');

Route::get('/search', [SearchController::class, 'live'])->name('search.live');

Route::get('/admin', function () {
    return redirect()->route('dashboard');
})->name('admin');

Route::post('/testimonials', [TestimonialController::class, 'store'])->name('testimonials.store');


// ─── Authenticated routes ─────────────────────────────────────────────────────

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', [AdminAccessController::class, 'dashboard'])->name('dashboard');

    Route::middleware('permission:cms.manage')->group(function () {
    Route::get('/admin/services/subcategories', [ServiceController::class, 'subcategories'])
        ->name('services.subcategories');

    Route::get('/admin/services',                [ServiceController::class, 'index'])->name('services.index');
    Route::get('/admin/services/create',         [ServiceController::class, 'create'])->name('services.create');
    Route::post('/admin/services',               [ServiceController::class, 'store'])->name('services.store');
    Route::get('/admin/services/{service}',      [ServiceController::class, 'show'])->name('services.show');
    Route::get('/admin/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/admin/services/{service}',      [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/admin/services/{service}',   [ServiceController::class, 'destroy'])->name('services.destroy');

    // ── Categories ────────────────────────────────────────────────────────────
    Route::get('/admin/categories',                  [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/admin/categories/create',           [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/admin/categories',                 [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/admin/categories/{category}',       [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/admin/categories/{category}/edit',  [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/admin/categories/{category}',       [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/admin/categories/{category}',    [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::middleware('permission:admin.access')->group(function () {
    Route::get('/admin/shop-categories',                       [ShopCategoryController::class, 'index'])->name('shop-categories.index');
    Route::get('/admin/shop-categories/create',                [ShopCategoryController::class, 'create'])->name('shop-categories.create');
    Route::post('/admin/shop-categories',                      [ShopCategoryController::class, 'store'])->name('shop-categories.store');
    Route::get('/admin/shop-categories/{shopCategory}',        [ShopCategoryController::class, 'show'])->name('shop-categories.show');
    Route::get('/admin/shop-categories/{shopCategory}/edit',   [ShopCategoryController::class, 'edit'])->name('shop-categories.edit');
    Route::put('/admin/shop-categories/{shopCategory}',        [ShopCategoryController::class, 'update'])->name('shop-categories.update');
    Route::delete('/admin/shop-categories/{shopCategory}',     [ShopCategoryController::class, 'destroy'])->name('shop-categories.destroy');

    // ── Shop Products (Admin) ─────────────────────────────────────────────────
    Route::get('/admin/shop-products',                         [ShopProductController::class, 'index'])->name('admin.shop-products.index');
    Route::get('/admin/shop-products/create',                  [ShopProductController::class, 'create'])->name('admin.shop-products.create');
    Route::post('/admin/shop-products',                        [ShopProductController::class, 'store'])->name('admin.shop-products.store');
    Route::get('/admin/shop-products/{shopProduct}',           [ShopProductController::class, 'show'])->name('admin.shop-products.show');
    Route::get('/admin/shop-products/{shopProduct}/edit',      [ShopProductController::class, 'edit'])->name('admin.shop-products.edit');
    Route::put('/admin/shop-products/{shopProduct}',           [ShopProductController::class, 'update'])->name('admin.shop-products.update');
    Route::delete('/admin/shop-products/{shopProduct}',        [ShopProductController::class, 'destroy'])->name('admin.shop-products.destroy');
    });

    Route::middleware('permission:cms.manage')->group(function () {
    Route::get('/admin/partners',                      [PartnerController::class, 'index'])->name('partners.index');
    Route::get('/admin/partners/create',               [PartnerController::class, 'create'])->name('partners.create');
    Route::post('/admin/partners',                     [PartnerController::class, 'store'])->name('partners.store');
    Route::get('/admin/partners/{partner}/edit',       [PartnerController::class, 'edit'])->name('partners.edit');
    Route::put('/admin/partners/{partner}',            [PartnerController::class, 'update'])->name('partners.update');
    Route::delete('/admin/partners/{partner}',         [PartnerController::class, 'destroy'])->name('partners.destroy');

    // ── Testimonials ──────────────────────────────────────────────────────────
    Route::get('/admin/testimonials',                          [TestimonialController::class, 'index'])->name('testimonials.index');
    Route::patch('/admin/testimonials/{testimonial}/approve',  [TestimonialController::class, 'approve'])->name('testimonials.approve');
    Route::delete('/admin/testimonials/{testimonial}',         [TestimonialController::class, 'destroy'])->name('testimonials.destroy');

    // ── Works ─────────────────────────────────────────────────────────────────
    Route::get('/admin/works',                  [WorkController::class, 'index'])->name('works.index');
    Route::get('/admin/works/create',           [WorkController::class, 'create'])->name('works.create');
    Route::post('/admin/works',                 [WorkController::class, 'store'])->name('works.store');
    Route::get('/admin/works/{work}/edit',      [WorkController::class, 'edit'])->name('works.edit');
    Route::put('/admin/works/{work}',           [WorkController::class, 'update'])->name('works.update');
    Route::delete('/admin/works/{work}',        [WorkController::class, 'destroy'])->name('works.destroy');

    // ── Backgrounds ───────────────────────────────────────────────────────────
    Route::get('/admin/backgrounds',                              [BackgroundController::class, 'index'])->name('backgrounds.index');
    Route::get('/admin/backgrounds/create',                       [BackgroundController::class, 'create'])->name('backgrounds.create');
    Route::post('/admin/backgrounds',                             [BackgroundController::class, 'store'])->name('backgrounds.store');
    Route::get('/admin/backgrounds/{background:slug}/edit',       [BackgroundController::class, 'edit'])->name('backgrounds.edit');
    Route::put('/admin/backgrounds/{background:slug}',            [BackgroundController::class, 'update'])->name('backgrounds.update');
    Route::delete('/admin/backgrounds/{background:slug}',         [BackgroundController::class, 'destroy'])->name('backgrounds.destroy');

    // ── CTA Categories ────────────────────────────────────────────────────────
    Route::get('/admin/cta-categories',                    [CtaCategoryController::class, 'index'])->name('cta-categories.index');
    Route::get('/admin/cta-categories/create',             [CtaCategoryController::class, 'create'])->name('cta-categories.create');
    Route::post('/admin/cta-categories',                   [CtaCategoryController::class, 'store'])->name('cta-categories.store');
    Route::get('/admin/cta-categories/{ctaCategory}/edit', [CtaCategoryController::class, 'edit'])->name('cta-categories.edit');
    Route::put('/admin/cta-categories/{ctaCategory}',      [CtaCategoryController::class, 'update'])->name('cta-categories.update');
    Route::delete('/admin/cta-categories/{ctaCategory}',   [CtaCategoryController::class, 'destroy'])->name('cta-categories.destroy');

    // ── CTAs ──────────────────────────────────────────────────────────────────
    Route::get('/admin/ctas',                  [CtaController::class, 'index'])->name('ctas.index');
    Route::get('/admin/ctas/create',           [CtaController::class, 'create'])->name('ctas.create');
    Route::post('/admin/ctas',                 [CtaController::class, 'store'])->name('ctas.store');
    Route::get('/admin/ctas/{cta}',            [CtaController::class, 'show'])->name('ctas.show');
    Route::get('/admin/ctas/{cta}/edit',       [CtaController::class, 'edit'])->name('ctas.edit');
    Route::put('/admin/ctas/{cta}',            [CtaController::class, 'update'])->name('ctas.update');
    Route::delete('/admin/ctas/{cta}',         [CtaController::class, 'destroy'])->name('ctas.destroy');

    // ── Service Requests (Admin) ──────────────────────────────────────────────
    Route::get('/admin/service-requests',                           [RequestServiceController::class, 'index'])->name('service-requests.index');
    Route::patch('/admin/service-requests/{serviceRequest}/status', [RequestServiceController::class, 'updateStatus'])->name('service-requests.status');
    Route::delete('/admin/service-requests/{serviceRequest}',       [RequestServiceController::class, 'destroy'])->name('service-requests.destroy');
    });

});

require __DIR__.'/marketplace.php';