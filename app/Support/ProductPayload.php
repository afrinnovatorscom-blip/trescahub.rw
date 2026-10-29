<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\ShopProduct;

class ProductPayload
{
    public static function rules(bool $creating = false): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,in_stock,out_of_stock,finished'],
            'is_popular' => ['nullable', 'boolean'],
            'discount_enabled' => ['nullable', 'boolean'],
            'original_price' => ['exclude_unless:discount_enabled,1', 'required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price_variations_enabled' => ['nullable', 'boolean'],
            'price' => ['exclude_if:price_variations_enabled,1', 'required', 'numeric', 'min:0'],
            'price_variations' => ['exclude_unless:price_variations_enabled,1', 'required', 'array', 'min:1', 'max:50'],
            'price_variations.*.id' => ['nullable', 'integer'],
            'price_variations.*.feature_label' => ['required', 'string', 'max:255'],
            'price_variations.*.variation_value' => ['required', 'string', 'max:255'],
            'price_variations.*.price' => ['required', 'numeric', 'min:0'],
            'price_variations.*.is_default' => ['required', 'boolean'],
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

    public static function validate(Request $request, bool $creating = false, ?ShopProduct $product = null): array
    {
        $validator = Validator::make($request->all(), self::rules($creating));
        $validator->after(function ($validator) use ($request, $product): void {
            $variationsEnabled = $request->boolean('price_variations_enabled');
            $variations = $request->input('price_variations', []);
            $defaultPrice = $request->input('price');

            if ($variationsEnabled && is_array($variations)) {
                if ($request->boolean('discount_enabled')) {
                    $validator->errors()->add(
                        'discount_enabled',
                        'Product-level discounts cannot be combined with price variations.'
                    );
                }

                $defaultCount = collect($variations)->filter(
                    static fn ($variation): bool => is_array($variation) && filter_var($variation['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN)
                )->count();

                if ($defaultCount !== 1) {
                    $validator->errors()->add('price_variations', 'Select exactly one default variation.');
                }

                foreach ($variations as $index => $variation) {
                    if (! is_array($variation) || empty($variation['id'])) {
                        continue;
                    }

                    if (! $product || ! $product->variations()->whereKey($variation['id'])->exists()) {
                        $validator->errors()->add("price_variations.{$index}.id", 'This variation does not belong to this product.');
                    }
                }

                $defaultPrice = collect($variations)->first(
                    static fn ($variation): bool => is_array($variation) && filter_var($variation['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN)
                )['price'] ?? null;
            }

            if ($request->boolean('discount_enabled')
                && is_numeric($request->input('original_price'))
                && is_numeric($defaultPrice)
                && (float) $request->input('original_price') <= (float) $defaultPrice) {
                $validator->errors()->add('original_price', 'The original price must be higher than the selling price.');
            }
        });

        return $validator->validate();
    }

    public static function normalize(array $validated): array
    {
        $validated['price_variations_enabled'] = (bool) ($validated['price_variations_enabled'] ?? false);
        $validated['price_variations'] = $validated['price_variations_enabled']
            ? array_values(array_map(static function (array $variation): array {
                return [
                    'id' => $variation['id'] ?? null,
                    'feature_label' => trim($variation['feature_label']),
                    'variation_value' => trim($variation['variation_value']),
                    'price' => $variation['price'],
                    'is_default' => (bool) $variation['is_default'],
                ];
            }, $validated['price_variations'] ?? []))
            : [];
        if ($validated['price_variations_enabled']) {
            $default = collect($validated['price_variations'])->firstWhere('is_default', true);
            $validated['price'] = $default['price'];
        }
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
