<?php

use App\Models\ShopProduct;
use App\Support\PublicProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/shop-products', function () {
    return ShopProduct::query()->publicCatalog()->with('category')->latest()->limit(48)->get()
        ->map(fn (ShopProduct $product) => PublicProduct::make($product));
});
