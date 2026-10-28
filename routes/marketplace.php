<?php

use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\Marketplace\AdminAccessController;
use App\Http\Controllers\Marketplace\CatalogOperationsController;
use App\Http\Controllers\Marketplace\CommerceController;
use App\Http\Controllers\Marketplace\StorefrontCommerceController;
use App\Http\Controllers\Marketplace\VendorAdminController;
use App\Http\Controllers\Marketplace\VendorWorkspaceController;
use Illuminate\Support\Facades\Route;

$auth = [
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
];

$customerAuth = ['auth:customers'];

Route::get('/cart', [StorefrontCommerceController::class, 'cart'])->name('cart');
Route::post('/cart', [StorefrontCommerceController::class, 'add'])->name('cart.add');
Route::patch('/cart', [StorefrontCommerceController::class, 'updateCart'])->name('cart.update');
Route::get('/checkout', [StorefrontCommerceController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [StorefrontCommerceController::class, 'place'])->middleware('throttle:marketplace-sensitive')->name('checkout.place');
Route::get('/checkout/complete/{reference}', [StorefrontCommerceController::class, 'done'])->name('checkout.done');

Route::get('/track-order', [StorefrontCommerceController::class, 'trackForm'])->name('track-order');
Route::post('/track-order', [StorefrontCommerceController::class, 'track'])->middleware('throttle:marketplace-sensitive')->name('track-order.submit');
Route::get('/track-order/result', [StorefrontCommerceController::class, 'trackResult'])->name('track-order.result');

Route::get('/product-requests', [StorefrontCommerceController::class, 'requestForm'])->name('product-requests.create');
Route::post('/product-requests', [StorefrontCommerceController::class, 'storeRequest'])->middleware('throttle:marketplace-sensitive')->name('product-requests.store');
Route::get('/product-requests/{reference}/received', [StorefrontCommerceController::class, 'requestDone'])->name('product-requests.done');

Route::middleware($customerAuth)->group(function () {
    Route::get('/account/profile', [CustomerAccountController::class, 'profile'])->name('account.profile');
    Route::put('/account/profile', [CustomerAccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [CustomerAccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/account/addresses', [CustomerAccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::put('/account/addresses/{address}', [CustomerAccountController::class, 'updateAddress'])->whereNumber('address')->name('account.addresses.update');
    Route::delete('/account/addresses/{address}', [CustomerAccountController::class, 'destroyAddress'])->whereNumber('address')->name('account.addresses.destroy');
    Route::get('/account/requests', [CustomerAccountController::class, 'requests'])->name('account.requests');

    Route::get('/account/orders', [StorefrontCommerceController::class, 'accountOrders'])->name('account.orders');
    Route::get('/account/orders/{reference}', [StorefrontCommerceController::class, 'accountOrder'])->name('account.orders.show');
    Route::get('/account/orders/{reference}/tracking', [StorefrontCommerceController::class, 'accountOrderTracking'])->name('account.orders.tracking');
    Route::post('/account/orders/{reference}/cancel', [StorefrontCommerceController::class, 'cancel'])->name('account.orders.cancel');
});

Route::middleware($auth)->group(function () {
    Route::middleware('vendor.active')->prefix('vendor')->name('vendor.')->group(function () {
        Route::get('/', [VendorWorkspaceController::class, 'dashboard'])->name('dashboard');
        Route::get('/products', [VendorWorkspaceController::class, 'productsIndex'])->name('products.index');
        Route::get('/products/create', [VendorWorkspaceController::class, 'createProduct'])->name('products.create');
        Route::post('/products', [VendorWorkspaceController::class, 'storeProduct'])->name('products.store');
        Route::get('/products/{slug}/edit', [VendorWorkspaceController::class, 'editProduct'])->name('products.edit');
        Route::put('/products/{slug}', [VendorWorkspaceController::class, 'updateProduct'])->name('products.update');
        Route::get('/requests', [VendorWorkspaceController::class, 'requestsIndex'])->name('requests.index');
        Route::get('/requests/{reference}', [VendorWorkspaceController::class, 'showRequest'])->name('requests.show');
        Route::post('/requests/{reference}', [VendorWorkspaceController::class, 'respondRequest'])->name('requests.respond');
        Route::get('/orders', [VendorWorkspaceController::class, 'ordersIndex'])->name('orders.index');
        Route::get('/orders/{reference}', [VendorWorkspaceController::class, 'showOrder'])->name('orders.show');
        Route::post('/orders/{reference}/ready-for-delivery', [VendorWorkspaceController::class, 'markOrderReadyForDelivery'])->name('orders.ready-for-delivery');
        Route::get('/orders/{reference}/deliveries/prepare', [VendorWorkspaceController::class, 'prepareOrderDelivery'])->name('orders.deliveries.prepare');
        Route::post('/orders/{reference}/deliveries/prepare', [VendorWorkspaceController::class, 'createOrderDelivery'])->name('orders.deliveries.store');
        Route::post('/orders/{reference}/create-delivery', [VendorWorkspaceController::class, 'createOrderDelivery'])->name('orders.create-delivery');
        Route::post('/order-items/{item}/respond', [VendorWorkspaceController::class, 'respondOrder'])->name('orders.respond');
        Route::post('/order-items/{item}/progress', [VendorWorkspaceController::class, 'progressOrder'])->name('orders.progress');
        Route::get('/deliveries', [VendorWorkspaceController::class, 'deliveriesIndex'])->name('deliveries.index');
        Route::get('/deliveries/{reference}/prepare', [VendorWorkspaceController::class, 'prepareExistingDelivery'])->name('deliveries.prepare.existing');
        Route::post('/deliveries/{reference}/prepare', [VendorWorkspaceController::class, 'updateExistingDelivery'])->name('deliveries.prepare.update');
        Route::get('/deliveries/{reference}', [VendorWorkspaceController::class, 'showDelivery'])->name('deliveries.show');
        Route::get('/deliveries/{reference}/dispatch-proof', [VendorWorkspaceController::class, 'downloadDispatchProof'])->name('deliveries.dispatch-proof');
        Route::get('/inventory', [VendorWorkspaceController::class, 'inventory'])->name('inventory');
        Route::post('/inventory/{slug}', [VendorWorkspaceController::class, 'adjust'])->name('inventory.adjust');
        Route::get('/profile', [VendorWorkspaceController::class, 'profile'])->name('profile');
        Route::put('/profile', [VendorWorkspaceController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [VendorWorkspaceController::class, 'updatePassword'])->name('profile.password');
    });

    Route::prefix('admin/marketplace')->name('marketplace.')->group(function () {
        Route::get('/approvals', [CatalogOperationsController::class, 'approvals'])->name('approvals.index');
        Route::post('/products/{shopProduct}/approve', [CatalogOperationsController::class, 'approve'])->name('products.approve');
        Route::post('/products/{shopProduct}/reject', [CatalogOperationsController::class, 'reject'])->name('products.reject');
        Route::post('/products/{shopProduct}/changes', [CatalogOperationsController::class, 'requestChanges'])->name('products.changes');
        Route::post('/products/{shopProduct}/publish', [CatalogOperationsController::class, 'publish'])->name('products.publish');
        Route::post('/products/{shopProduct}/unpublish', [CatalogOperationsController::class, 'unpublish'])->name('products.unpublish');
        Route::post('/products/{shopProduct}/archive', [CatalogOperationsController::class, 'archive'])->name('products.archive');
        Route::post('/products/{shopProduct}/vendor', [CatalogOperationsController::class, 'assignVendor'])->name('products.vendor');
        Route::post('/products/{shopProduct}/locations', [CatalogOperationsController::class, 'storeLocation'])->name('locations.store');
        Route::delete('/locations/{location}', [CatalogOperationsController::class, 'destroyLocation'])->name('locations.destroy');
        Route::get('/inventory', [CatalogOperationsController::class, 'inventory'])->name('inventory.index');
        Route::get('/inventory/categories/{categoryId}/products', [CatalogOperationsController::class, 'inventoryProductsForCategory'])->whereNumber('categoryId')->name('inventory.categories.products');
        Route::post('/inventory', [CatalogOperationsController::class, 'addInventory'])->name('inventory.store');
        Route::post('/inventory/remove', [CatalogOperationsController::class, 'removeInventory'])->name('inventory.remove');
        Route::patch('/inventory/{shopProduct}', [CatalogOperationsController::class, 'updateInventory'])->name('inventory.update');
        Route::post('/inventory/{shopProduct}', [CatalogOperationsController::class, 'adjust'])->name('inventory.adjust');
        Route::post('/inventory-requests/{inventoryAdjustmentRequest}/approve', [CatalogOperationsController::class, 'approveInventoryRequest'])->name('inventory-requests.approve');
        Route::post('/inventory-requests/{inventoryAdjustmentRequest}/reject', [CatalogOperationsController::class, 'rejectInventoryRequest'])->name('inventory-requests.reject');

        Route::get('/vendors', [VendorAdminController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/create', [VendorAdminController::class, 'create'])->name('vendors.create');
        Route::post('/vendors', [VendorAdminController::class, 'store'])->name('vendors.store');
        Route::get('/vendors/{vendor}', [VendorAdminController::class, 'show'])->name('vendors.show');
        Route::get('/vendors/{vendor}/edit', [VendorAdminController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{vendor}', [VendorAdminController::class, 'update'])->name('vendors.update');
        Route::patch('/vendors/{vendor}/status', [VendorAdminController::class, 'status'])->name('vendors.status');
        Route::patch('/vendors/{vendor}/publication', [VendorAdminController::class, 'publication'])->name('vendors.publication');
        Route::post('/vendors/{vendor}/terminate', [VendorAdminController::class, 'terminate'])->name('vendors.terminate');

        Route::get('/requests', [CommerceController::class, 'requests'])->name('requests.index');
        Route::get('/requests/{productRequest}', [CommerceController::class, 'showRequest'])->name('requests.show');
        Route::put('/requests/{productRequest}', [CommerceController::class, 'updateRequest'])->name('requests.update');
        Route::post('/requests/{productRequest}/allocate', [CommerceController::class, 'allocateRequest'])->name('requests.allocate');
        Route::post('/requests/{productRequest}/convert', [CommerceController::class, 'convertRequest'])->name('requests.convert');

        Route::get('/orders', [CommerceController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{order}', [CommerceController::class, 'showOrder'])->name('orders.show');
        Route::post('/orders/{order}/review', [CommerceController::class, 'reviewOrder'])->name('orders.review');
        Route::post('/orders/{order}/save-changes', [CommerceController::class, 'saveOrderChanges'])->name('orders.save-changes');
        Route::post('/orders/{order}/status', [CommerceController::class, 'updateOrder'])->name('orders.status');
        Route::post('/orders/{order}/vendors/{vendor}/ready-for-delivery', [CommerceController::class, 'markVendorAssignmentReady'])->name('orders.vendor-ready-for-delivery');
        Route::post('/orders/{order}/delivery-request', [CommerceController::class, 'requestVendorDelivery'])->name('orders.delivery-request');
        Route::get('/orders/{order}/deliveries/prepare', [CommerceController::class, 'prepareDelivery'])->name('orders.deliveries.prepare');
        Route::post('/orders/{order}/deliveries/prepare', [CommerceController::class, 'storePreparedDelivery'])->name('orders.deliveries.store');
        Route::post('/orders/{order}/staff', [CommerceController::class, 'assignOrderStaff'])->name('orders.staff');

        Route::get('/deliveries', [CommerceController::class, 'deliveries'])->name('deliveries.index');
        Route::get('/deliveries/prepare', [CommerceController::class, 'selectDeliveryOrder'])->name('deliveries.prepare.select');
        Route::get('/deliveries/{deliveryTask}', [CommerceController::class, 'showDelivery'])->name('deliveries.show');
        Route::get('/deliveries/{deliveryTask}/prepare', [CommerceController::class, 'prepareExistingDelivery'])->name('deliveries.prepare.existing');
        Route::post('/deliveries/{deliveryTask}/prepare', [CommerceController::class, 'updatePreparedDelivery'])->name('deliveries.prepare.update');
        Route::post('/deliveries/{deliveryTask}', [CommerceController::class, 'updateDelivery'])->name('deliveries.update');
        Route::post('/deliveries/{deliveryTask}/attempts', [CommerceController::class, 'attempt'])->name('deliveries.attempt');
        Route::post('/deliveries/{deliveryTask}/proof', [CommerceController::class, 'proof'])->name('deliveries.proof');
        Route::get('/deliveries/{deliveryTask}/proof', [CommerceController::class, 'downloadProof'])->name('deliveries.proof.download');
        Route::get('/deliveries/{deliveryTask}/dispatch-proof', [CommerceController::class, 'downloadDispatchProof'])->name('deliveries.dispatch-proof');
        Route::post('/deliveries/{deliveryTask}/reassign', [CommerceController::class, 'reassignDelivery'])->name('deliveries.reassign');

        Route::get('/reports', [AdminAccessController::class, 'reports'])->name('reports.index');
        Route::get('/reports/export', [AdminAccessController::class, 'exportPdf'])->name('reports.export');
        Route::get('/audit', [AdminAccessController::class, 'audit'])->name('audit.index');
        Route::get('/roles', [AdminAccessController::class, 'roles'])->name('roles.index');
        Route::post('/roles', [AdminAccessController::class, 'storeRole'])->name('roles.store');
        Route::get('/roles/{role}', [AdminAccessController::class, 'editRole'])->name('roles.edit');
        Route::put('/roles/{role}', [AdminAccessController::class, 'updateRole'])->name('roles.update');
        Route::get('/staff', [AdminAccessController::class, 'users'])->name('users.index');
        Route::get('/staff/create', [AdminAccessController::class, 'createUser'])->name('users.create');
        Route::post('/staff', [AdminAccessController::class, 'storeUser'])->name('users.store');
        Route::put('/staff/{staff}', [AdminAccessController::class, 'updateUser'])->name('users.update');
        Route::get('/notifications', [AdminAccessController::class, 'notifications'])->name('notifications.index');
        Route::post('/notifications/{id}/read', [AdminAccessController::class, 'readNotification'])->name('notifications.read');
    });
});
