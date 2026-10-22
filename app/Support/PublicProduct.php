<?php

namespace App\Support;

use App\Models\ShopProduct;

class PublicProduct
{
    public static function make(ShopProduct $product): array
    {
        return [
            'name' => $product->product_name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'currency' => 'RWF',
            'availability' => $product->customerAvailability() === 'in_stock' ? 'in_stock' : 'out_of_stock',
            'delivery_estimate_days' => $product->working_days,
            'category' => $product->category?->name,
            'specifications' => $product->specifications ?? [],
            'features' => $product->features ?? [],
            'thumbnail' => $product->thumbnail ? url('assets/'.ltrim($product->thumbnail, '/')) : null,
            'url' => route('shop-products.show', $product->slug),
        ];
    }
}
