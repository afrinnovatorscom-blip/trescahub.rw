<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ProductPayload
{
    public static function rules(?int $productId = null): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('shop_products', 'sku')->ignore($productId)],
            'status' => ['required', 'in:in_stock,out_of_stock'],
            'is_popular' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'working_days' => ['required', 'integer', 'min:1'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'tracks_inventory' => ['nullable', 'boolean'],
            'product_type' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string', 'max:100'],
            'internal_notes' => ['nullable', 'string'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.label' => ['nullable', 'string', 'max:255'],
            'specifications.*.value' => ['nullable', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'shop_category_id' => ['required', 'integer', 'exists:shop_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:shop_categories,id'],
            'approve_now' => ['nullable', 'boolean'],
            'publish_now' => ['nullable', 'boolean'],
            'submit_for_review' => ['nullable', 'boolean'],
        ];
    }

    public static function normalize(array $validated): array
    {
        $validated['features'] = array_values(array_filter($validated['features'] ?? [], fn ($feature) => trim((string) $feature) !== ''));
        $validated['specifications'] = array_values(array_filter(
            $validated['specifications'] ?? [],
            fn ($row) => ! empty($row['label']) && ! empty($row['value'])
        ));
        $validated['is_popular'] = (bool) ($validated['is_popular'] ?? false);
        $validated['tracks_inventory'] = (bool) ($validated['tracks_inventory'] ?? false);
        $validated['sku'] = isset($validated['sku']) && $validated['sku'] !== '' ? $validated['sku'] : null;

        return $validated;
    }
}
