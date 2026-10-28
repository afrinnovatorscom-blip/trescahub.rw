<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    // ─── PUBLIC INDEX ─────────────────────────────────────────────────────────

    public function publicIndex()
    {
        $popularServices = Service::where('is_popular', true)->latest()->get();

        $inStockServices = Service::where('is_in_stock', true)->latest()->get();

        $regularServices = Service::latest()->get(); // ALL services

        $categories = Category::with('services')->latest()->paginate(12);

        return view('front.pages.services', compact('categories', 'popularServices', 'inStockServices', 'regularServices'));
    }

    // ─── SHOW BY CATEGORY ─────────────────────────────────────────────────────

    public function showByCategory(Category $category)
    {
        $category->load('services');
        return view('front.pages.service-category', compact('category'));
    }

    // ─── PUBLIC SHOW ──────────────────────────────────────────────────────────

    public function publicShow(Service $service)
    {
        $service->load('category');

        $relatedServices = Service::with('category')
            ->where('is_in_stock', true)
            ->where('classification_name', $service->classification_name)
            ->where('id', '!=', $service->id)
            ->latest()
            ->limit(3)
            ->get();

        return view('services.show', compact('service', 'relatedServices'));
    }

    // ─── INDEX (Admin) ────────────────────────────────────────────────────────

    public function index()
    {
        $services   = Service::latest()->paginate(10)->withQueryString();
        $categories = Category::orderBy('title')->get()->keyBy('id');
        return view('services.index', compact('services', 'categories'));
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    public function create()
    {
        $classifications = Category::orderBy('title')->get();
        return view('services.create', compact('classifications'));
    }

    // ─── STORE ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'slug'                => ['nullable', 'string', 'max:255', 'unique:services,slug'],
            'description'         => ['nullable', 'string'],
            'price'               => ['required', 'numeric', 'min:0'],
            'working_day'         => ['nullable', 'string', 'max:255'],
            'what_included'       => ['nullable', 'array'],
            'what_included.*'     => ['string', 'max:255'],
            'how_service_works'   => ['nullable', 'array'],
            'how_service_works.*' => ['string'],
            'classification_name' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory'         => ['nullable', 'array'],
            'subcategory.*'       => ['string', 'max:255'],
            'thumbnail'           => ['nullable', 'image', 'max:2048'],
            'other_images'        => ['nullable', 'array'],
            'other_images.*'      => ['image', 'max:2048'],
            'is_popular'          => ['nullable', 'boolean'],
            'is_in_stock'         => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $file     = $request->file('thumbnail');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/services/thumbnails'), $filename);
            $validated['thumbnail'] = 'assets/services/thumbnails/' . $filename;
        }

        $otherImagePaths = [];
        if ($request->hasFile('other_images')) {
            foreach ($request->file('other_images') as $img) {
                $filename          = time() . '_' . $img->getClientOriginalName();
                $img->move(public_path('assets/services/images'), $filename);
                $otherImagePaths[] = 'assets/services/images/' . $filename;
            }
        }
        $validated['other_images'] = $otherImagePaths ?: null;

        if (empty($validated['slug'])) unset($validated['slug']);

        $validated['what_included']     = array_values(array_filter($validated['what_included'] ?? []));
        $validated['how_service_works'] = array_values(array_filter($validated['how_service_works'] ?? []));
        $validated['subcategory']       = !empty($validated['subcategory'])
            ? array_values(array_unique($validated['subcategory']))
            : null;

        $validated['is_popular']  = $request->has('is_popular');
        $validated['is_in_stock'] = $request->has('is_in_stock');

        $service = Service::create($validated);

        return redirect()->route('services.index')
            ->with('success', "Service \"{$service->title}\" created successfully.");
    }

    // ─── SHOW (Admin) ─────────────────────────────────────────────────────────

    public function show(Service $service)
    {
        $service->load('category');
        $relatedServices = Service::where('classification_name', $service->classification_name)
            ->where('id', '!=', $service->id)
            ->limit(3)
            ->get();

        return view('services.show', compact('service', 'relatedServices'));
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit(Service $service)
    {
        $classifications     = Category::orderBy('title')->get();
        $selectedSubcategory = $service->subcategory ?? [];

        return view('services.edit', compact('service', 'classifications', 'selectedSubcategory'));
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'title'               => ['required', 'string', 'max:255'],
            'slug'                => ['nullable', 'string', 'max:255',
                                      Rule::unique('services', 'slug')->ignore($service->id)],
            'description'         => ['nullable', 'string'],
            'price'               => ['required', 'numeric', 'min:0'],
            'working_day'         => ['nullable', 'string', 'max:255'],
            'what_included'       => ['nullable', 'array'],
            'what_included.*'     => ['string', 'max:255'],
            'how_service_works'   => ['nullable', 'array'],
            'how_service_works.*' => ['string'],
            'classification_name' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory'         => ['nullable', 'array'],
            'subcategory.*'       => ['string', 'max:255'],
            'thumbnail'           => ['nullable', 'image', 'max:2048'],
            'other_images'        => ['nullable', 'array'],
            'other_images.*'      => ['image', 'max:2048'],
            'is_popular'          => ['nullable', 'boolean'],
            'is_in_stock'         => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($service->thumbnail) {
                $oldPath = public_path($service->thumbnail);
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $file     = $request->file('thumbnail');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('assets/services/thumbnails'), $filename);
            $validated['thumbnail'] = 'assets/services/thumbnails/' . $filename;
        }

        $existingImages = $service->other_images ?? [];
        if ($request->hasFile('other_images')) {
            foreach ($request->file('other_images') as $img) {
                $filename         = time() . '_' . $img->getClientOriginalName();
                $img->move(public_path('assets/services/images'), $filename);
                $existingImages[] = 'assets/services/images/' . $filename;
            }
        }
        $validated['other_images'] = $existingImages ?: null;

        if (empty($validated['slug'])) unset($validated['slug']);

        $validated['what_included']     = array_values(array_filter($validated['what_included'] ?? []));
        $validated['how_service_works'] = array_values(array_filter($validated['how_service_works'] ?? []));
        $validated['subcategory']       = !empty($validated['subcategory'])
            ? array_values(array_unique($validated['subcategory']))
            : null;

        $validated['is_popular']  = $request->has('is_popular');
        $validated['is_in_stock'] = $request->has('is_in_stock')
            ? $request->boolean('is_in_stock')
            : $service->is_in_stock;

        $service->update($validated);

        return redirect()->route('services.index')
            ->with('success', "Service \"{$service->title}\" updated successfully.");
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────

    public function destroy(Service $service)
    {
        if ($service->thumbnail) {
            $path = public_path($service->thumbnail);
            if (file_exists($path)) unlink($path);
        }

        foreach ($service->other_images ?? [] as $img) {
            $path = public_path($img);
            if (file_exists($path)) unlink($path);
        }

        $title = $service->title;
        $service->delete();

        return redirect()->route('services.index')
            ->with('success', "Service \"{$title}\" deleted successfully.");
    }

    // ─── AJAX: subcategories ──────────────────────────────────────────────────

    public function subcategories(Request $request): JsonResponse
    {
        $category = Category::find($request->input('category_id'));

        if (!$category) {
            return response()->json([]);
        }

        $raw = $category->getRawOriginal('subcategory');

        if (empty($raw)) {
            return response()->json([]);
        }

        $subcategories = is_array($raw)
            ? $raw
            : (json_decode($raw, true) ?? []);

        return response()->json(array_values($subcategories));
    }
}