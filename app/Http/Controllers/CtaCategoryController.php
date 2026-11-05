<?php

namespace App\Http\Controllers;

use App\Models\Cta;
use App\Models\CtaCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CtaCategoryController extends Controller
{
    // ─── PUBLIC INDEX ─────────────────────────────────────────
    public function publicIndex()
    {
        $ctas = Cta::with('category')->get();
        return view('front.section.cta', compact('ctas'));
    }

    // ─── INDEX (Admin) ────────────────────────────────────────
    public function index()
    {
        $categories = CtaCategory::withCount('ctas')->latest()->paginate(15);
        return view('cta-categories.index', compact('categories'));
    }

    // ─── CREATE ───────────────────────────────────────────────
    public function create()
    {
        return view('cta-categories.create');
    }

    // ─── STORE ────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255', 'unique:cta_categories,title'],
            'slug'        => ['nullable', 'string', 'max:255', 'unique:cta_categories,slug'],
            'description' => ['nullable', 'string'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        CtaCategory::create($validated);

        return redirect()->route('cta-categories.index')
            ->with('success', 'CTA Category created successfully!');
    }

    // ─── EDIT ─────────────────────────────────────────────────
    public function edit(CtaCategory $ctaCategory)
    {
        return view('cta-categories.edit', compact('ctaCategory'));
    }

    // ─── UPDATE ───────────────────────────────────────────────
    public function update(Request $request, CtaCategory $ctaCategory)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255',
                              'unique:cta_categories,title,' . $ctaCategory->id],
            'slug'        => ['nullable', 'string', 'max:255',
                              'unique:cta_categories,slug,' . $ctaCategory->id],
            'description' => ['nullable', 'string'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $ctaCategory->update($validated);

        return redirect()->route('cta-categories.index')
            ->with('success', 'CTA Category updated successfully!');
    }

    // ─── DESTROY ──────────────────────────────────────────────
    public function destroy(CtaCategory $ctaCategory)
    {
        $ctaCategory->delete();

        return redirect()->route('cta-categories.index')
            ->with('success', 'CTA Category deleted successfully!');
    }
}