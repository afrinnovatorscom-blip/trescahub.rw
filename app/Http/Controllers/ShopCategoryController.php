<?php

namespace App\Http\Controllers;

use App\Models\Shopcategory;
use Illuminate\Http\Request;
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
        // Slugify parent slug before validation
        $request->merge(['slug' => Str::slug($request->input('slug'))]);

        $validated = $request->validate([
            'slug'                 => ['required', 'string', 'max:255', 'unique:shop_categories,slug'],
            'active'               => ['nullable', 'boolean'],
            'subcategories'        => ['nullable', 'array'],
            'subcategories.*.name' => ['nullable', 'string', 'max:255'],
            'subcategories.*.slug' => ['nullable', 'string', 'max:255'],
        ]);

        $category = Shopcategory::create([
            'name'      => Str::title(str_replace('-', ' ', $validated['slug'])),
            'slug'      => $validated['slug'],
            'parent_id' => null,
            'active'    => $request->boolean('active', true),
        ]);

        if (!empty($validated['subcategories'])) {
            foreach ($validated['subcategories'] as $sub) {
                if (empty(trim($sub['name'] ?? ''))) {
                    continue;
                }

                $subSlug = !empty($sub['slug'])
                    ? Str::slug($sub['slug'])
                    : Str::slug($sub['name']);

                // Make slug unique by appending suffix if duplicate
                $originalSubSlug = $subSlug;
                $counter = 1;
                while (Shopcategory::where('slug', $subSlug)->exists()) {
                    $subSlug = $originalSubSlug . '-' . $counter++;
                }

                Shopcategory::create([
                    'name'      => $sub['name'],
                    'slug'      => $subSlug,
                    'parent_id' => $category->id,
                    'active'    => $category->active,
                ]);
            }
        }

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
        // Slugify parent slug before validation
        $request->merge(['slug' => Str::slug($request->input('slug'))]);

        $validated = $request->validate([
            'slug'                 => ['required', 'string', 'max:255',
                                        Rule::unique('shop_categories', 'slug')->ignore($shopCategory->id)],
            'active'               => ['nullable', 'boolean'],
            'subcategories'        => ['nullable', 'array'],
            'subcategories.*.name' => ['nullable', 'string', 'max:255'],
            'subcategories.*.slug' => ['nullable', 'string', 'max:255'],
        ]);

        $shopCategory->update([
            'name'      => Str::title(str_replace('-', ' ', $validated['slug'])),
            'slug'      => $validated['slug'],
            'parent_id' => null,
            'active'    => $request->boolean('active', $shopCategory->active),
        ]);

        // Delete old subcategories then re-create
        $shopCategory->subcategories()->delete();

        if (!empty($validated['subcategories'])) {
            foreach ($validated['subcategories'] as $sub) {
                if (empty(trim($sub['name'] ?? ''))) {
                    continue;
                }

                $subSlug = !empty($sub['slug'])
                    ? Str::slug($sub['slug'])
                    : Str::slug($sub['name']);

                // Make slug unique by appending suffix if duplicate
                $originalSubSlug = $subSlug;
                $counter = 1;
                while (Shopcategory::where('slug', $subSlug)->exists()) {
                    $subSlug = $originalSubSlug . '-' . $counter++;
                }

                Shopcategory::create([
                    'name'      => $sub['name'],
                    'slug'      => $subSlug,
                    'parent_id' => $shopCategory->id,
                    'active'    => $shopCategory->active,
                ]);
            }
        }

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

    private function authorizeCatalog(string $permission): void
    {
        abort_unless(auth()->user()?->hasPermission($permission), 403);
    }
}