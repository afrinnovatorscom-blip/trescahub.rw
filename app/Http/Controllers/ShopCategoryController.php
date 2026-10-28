<?php

namespace App\Http\Controllers;

use App\Models\Shopcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShopCategoryController extends Controller
{
    public function index()
    {
        $this->authorizeCatalog('products.view');
        $categories = Shopcategory::with('subcategories')
            ->whereNull('parent_id')
            ->latest()
            ->paginate(15);

        return view('shop-categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorizeCatalog('products.edit');

        return view('shop-categories.create');
    }

    public function store(Request $request)
    {
        $this->authorizeCatalog('products.edit');

        $request->merge([
            'slug' => $this->slugFrom($request->input('slug'), $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:shop_categories,slug'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'subcategories' => ['nullable', 'array'],
            'subcategories.*.name' => ['required', 'string', 'max:255'],
            'subcategories.*.slug' => ['nullable', 'string', 'max:255'],
            'subcategories.*.description' => ['nullable', 'string', 'max:2000'],
        ]);

        $active = $validated['status'] === 'active';

        DB::transaction(function () use ($validated, $active) {
            $category = Shopcategory::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'parent_id' => null,
                'active' => $active,
            ]);

            foreach ($validated['subcategories'] ?? [] as $subcategory) {
                $category->subcategories()->create([
                    'name' => $subcategory['name'],
                    'slug' => $this->uniqueSlug($subcategory['slug'] ?? null, $subcategory['name']),
                    'description' => $subcategory['description'] ?? null,
                    'active' => $active,
                ]);
            }
        });

        return redirect()->route('shop-categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function show(Shopcategory $shopCategory)
    {
        $this->authorizeCatalog('products.view');
        $shopCategory->load(['subcategories', 'parent']);

        return view('shop-categories.show', compact('shopCategory'));
    }

    public function edit(Shopcategory $shopCategory)
    {
        $this->authorizeCatalog('products.edit');
        $shopCategory->load(['subcategories', 'parent']);

        return view('shop-categories.edit', compact('shopCategory'));
    }

    public function update(Request $request, Shopcategory $shopCategory)
    {
        $this->authorizeCatalog('products.edit');

        $request->merge([
            'slug' => $this->slugFrom($request->input('slug'), $request->input('name')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shop_categories', 'slug')->ignore($shopCategory->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'subcategories' => ['nullable', 'array'],
            'subcategories.*.id' => [
                'nullable',
                'integer',
                Rule::exists('shop_categories', 'id')->where('parent_id', $shopCategory->id),
            ],
            'subcategories.*.name' => ['required', 'string', 'max:255'],
            'subcategories.*.slug' => ['nullable', 'string', 'max:255'],
            'subcategories.*.description' => ['nullable', 'string', 'max:2000'],
        ]);

        $active = $validated['status'] === 'active';

        DB::transaction(function () use ($shopCategory, $validated, $active) {
            $shopCategory->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'parent_id' => null,
                'active' => $active,
            ]);

            $shopCategory->subcategories()->update(['active' => $active]);

            foreach ($validated['subcategories'] ?? [] as $subcategory) {
                $child = ! empty($subcategory['id'])
                    ? $shopCategory->subcategories()->findOrFail($subcategory['id'])
                    : null;
                $slug = $this->uniqueSlug($subcategory['slug'] ?? null, $subcategory['name'], $child?->id);
                $attributes = [
                    'name' => $subcategory['name'],
                    'slug' => $slug,
                    'description' => $subcategory['description'] ?? null,
                    'active' => $active,
                ];

                if ($child) {
                    $child->update($attributes);
                } else {
                    $shopCategory->subcategories()->create($attributes);
                }
            }
        });

        return redirect()->route('shop-categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(Shopcategory $shopCategory)
    {
        $this->authorizeCatalog('products.delete');
        $name = $shopCategory->name;

        $shopCategory->subcategories()->delete();
        $shopCategory->delete();

        return redirect()->route('shop-categories.index')
            ->with('success', "Category '{$name}' deleted successfully!");
    }

    private function slugFrom(mixed $slug, mixed $name): string
    {
        $value = is_string($slug) && filled($slug)
            ? $slug
            : (is_string($name) ? $name : '');

        return Str::limit(Str::slug($value), 255, '');
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit($this->slugFrom($slug, $name) ?: 'category', 240, '');
        $candidate = $base;
        $suffix = 1;

        while (Shopcategory::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function authorizeCatalog(string $permission): void
    {
        abort_unless(auth()->user()?->hasPermission($permission), 403);
    }
}
