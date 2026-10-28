<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ProductPayload
{
    public static function rules(bool $creating = false): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,in_stock,out_of_stock,finished'],
            'is_popular' => ['nullable', 'boolean'],
            'discount_enabled' => ['nullable', 'boolean'],
            'original_price' => ['exclude_unless:discount_enabled,1', 'required', 'numeric', 'gt:price'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'working_days' => ['required', 'integer', 'min:1'],
            'initial_stock_quantity' => $creating
                ? ['exclude_unless:tracks_inventory,1', 'required_if:tracks_inventory,1', 'integer', 'min:0', 'max:2147483647']
                : ['prohibited'],
            'stock_unit' => ['nullable', 'string', 'max:32'],
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
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_thumbnail' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'gallery_order' => ['nullable', 'array', 'max:8'],
            'gallery_order.*' => ['string', 'max:700'],
            'gallery_order_present' => ['nullable', 'boolean'],
            'shop_category_id' => ['required', 'integer', 'exists:shop_categories,id'],
            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('shop_categories', 'id')->where('parent_id', request()->input('shop_category_id')),
            ],
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
        $validated['discount_enabled'] = (bool) ($validated['discount_enabled'] ?? false);
        if (! $validated['discount_enabled']) {
            $validated['original_price'] = null;
        }
        $validated['is_finished'] = ($validated['status'] ?? null) === 'finished';
        $validated['status'] = ($validated['status'] ?? null) === 'out_of_stock'
            ? 'out_of_stock'
            : 'in_stock';
        $validated['tracks_inventory'] = (bool) ($validated['tracks_inventory'] ?? false);
        if (! $validated['tracks_inventory']) {
            unset($validated['initial_stock_quantity']);
        }
        $validated['remove_thumbnail'] = (bool) ($validated['remove_thumbnail'] ?? false);

        return $validated;
    }
}
