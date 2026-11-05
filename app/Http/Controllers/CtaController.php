<?php

namespace App\Http\Controllers;

use App\Models\Cta;
use App\Models\CtaCategory;
use Illuminate\Http\Request;

class CtaController extends Controller
{
    // ─── PUBLIC SHOW ──────────────────────────────────────────
    // Display a single CTA detail page
    public function publicShow(Cta $cta)
    {
        $cta->load('category');
        return view('ctas.show', compact('cta'));
    }

    // ─── INDEX ────────────────────────────────────────────────
    public function index()
    {
        $ctas = Cta::with('category')->latest()->paginate(15);
        return view('ctas.index', compact('ctas'));
    }

    // ─── CREATE ───────────────────────────────────────────────
    public function create()
    {
        $categories = CtaCategory::orderBy('title')->get();
        return view('ctas.create', compact('categories'));
    }

    // ─── STORE ────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'cta_category_title'    => ['required', 'string', 'exists:cta_categories,title'],
            'items'                 => ['nullable', 'array'],
            'items.*.title'         => ['required', 'string', 'max:255'],
            'items.*.description'   => ['nullable', 'string'],
        ]);

        // Clean and filter empty items
        $items = collect($request->input('items', []))
            ->filter(fn($item) => !empty($item['title']))
            ->values()
            ->map(fn($item) => [
                'title'       => $item['title'],
                'description' => $item['description'] ?? null,
            ])
            ->toArray();

        Cta::create([
            'cta_category_title' => $request->cta_category_title,
            'items'              => $items ?: null,
        ]);

        return redirect()->route('ctas.index')
            ->with('success', 'CTA created successfully!');
    }

    // ─── SHOW ─────────────────────────────────────────────────
    public function show(Cta $cta)
    {
        $cta->load('category');
        return view('ctas.show', compact('cta'));
    }

    // ─── EDIT ─────────────────────────────────────────────────
    public function edit(Cta $cta)
    {
        $categories = CtaCategory::orderBy('title')->get();
        return view('ctas.edit', compact('cta', 'categories'));
    }

    // ─── UPDATE ───────────────────────────────────────────────
    public function update(Request $request, Cta $cta)
    {
        $request->validate([
            'cta_category_title'    => ['required', 'string', 'exists:cta_categories,title'],
            'items'                 => ['nullable', 'array'],
            'items.*.title'         => ['required', 'string', 'max:255'],
            'items.*.description'   => ['nullable', 'string'],
        ]);

        $items = collect($request->input('items', []))
            ->filter(fn($item) => !empty($item['title']))
            ->values()
            ->map(fn($item) => [
                'title'       => $item['title'],
                'description' => $item['description'] ?? null,
            ])
            ->toArray();

        $cta->update([
            'cta_category_title' => $request->cta_category_title,
            'items'              => $items ?: null,
        ]);

        return redirect()->route('ctas.index')
            ->with('success', 'CTA updated successfully!');
    }

    // ─── DESTROY ──────────────────────────────────────────────
    public function destroy(Cta $cta)
    {
        $cta->delete();

        return redirect()->route('ctas.index')
            ->with('success', 'CTA deleted successfully!');
    }
}