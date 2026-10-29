<?php

namespace App\Services;

use App\Models\ProductVariation;
use App\Models\ShopProduct;

class ProductVariationService
{
    public function sync(ShopProduct $product, bool $enabled, array $variations): void
    {
        if (! $enabled) {
            $product->variations()->delete();

            return;
        }

        $retainedIds = [];

        foreach ($variations as $attributes) {
            $variation = null;
            if (! empty($attributes['id'])) {
                $variation = ProductVariation::query()
                    ->where('shop_product_id', $product->id)
                    ->whereKey($attributes['id'])
                    ->firstOrFail();
                $retainedIds[] = $variation->id;
            }

            $values = [
                'feature_label' => $attributes['feature_label'],
                'variation_value' => $attributes['variation_value'],
                'price' => $attributes['price'],
                'is_default' => (bool) $attributes['is_default'],
            ];

            if ($variation) {
                $variation->update($values);
            } else {
                $variation = $product->variations()->create($values);
                $retainedIds[] = $variation->id;
            }
        }

        $product->variations()->whereNotIn('id', $retainedIds)->delete();
    }
}
