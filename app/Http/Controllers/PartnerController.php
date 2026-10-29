<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Support\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class PartnerController extends Controller
{
    // ── Public ─────────────────────────────────────────────────────────────

    public function publicIndex()
    {
        $partners = Partner::latest()->get();
        return view('front.pages.for-partners', compact('partners'));
    }

    // ── Admin ──────────────────────────────────────────────────────────────

    /**
     * Display a listing of partners.
     */
    public function index()
    {
        $partners = Partner::latest()->paginate(15);
        return view('partners.index', compact('partners'));
    }

    /**
     * Show the form for creating a new partner.
     */
    public function create()
    {
        return view('partners.create');
    }

    /**
     * Store a newly created partner in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:500'],
            'website_url'      => ['nullable', 'url:http,https', 'max:2048'],
            'logo'             => ['nullable', 'image', 'max:2048'],
            'show_on_website'  => ['required', 'boolean'],
            'status'           => ['required', 'in:active,inactive'],
        ]);

        $validated['description'] = $validated['description'] ?? '';
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        unset($validated['logo']);

        $logoPath = $request->hasFile('logo')
            ? PublicAsset::store($request->file('logo'), 'partners')
            : null;
        $validated['logo_path'] = $logoPath;

        try {
            Partner::create($validated);
        } catch (Throwable $exception) {
            PublicAsset::delete($logoPath);
            throw $exception;
        }

        return redirect()->route('partners.index')
            ->with('success', 'Partner created successfully!');
    }

    /**
     * Display the specified partner.
     */
    public function show(Partner $partner)
    {
        return view('partners.show', compact('partner'));
    }

    /**
     * Show the form for editing the specified partner.
     */
    public function edit(Partner $partner)
    {
        return view('partners.edit', compact('partner'));
    }

    /**
     * Update the specified partner in storage.
     */
    public function update(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:500'],
            'website_url'      => ['nullable', 'url:http,https', 'max:2048'],
            'logo'             => ['nullable', 'image', 'max:2048'],
            'show_on_website'  => ['required', 'boolean'],
            'status'           => ['required', 'in:active,inactive'],
        ]);

        $validated['description'] = $validated['description'] ?? '';
        unset($validated['logo']);

        $oldLogoPath = $partner->logo_path;
        $newLogoPath = $request->hasFile('logo')
            ? PublicAsset::store($request->file('logo'), 'partners')
            : null;

        if ($newLogoPath) {
            $validated['logo_path'] = $newLogoPath;
        }

        try {
            $partner->update($validated);
        } catch (Throwable $exception) {
            PublicAsset::delete($newLogoPath);
            throw $exception;
        }

        if ($newLogoPath && $oldLogoPath !== $newLogoPath) {
            PublicAsset::delete($oldLogoPath);
        }

        return redirect()->route('partners.index')
            ->with('success', 'Partner updated successfully!');
    }

    /**
     * Remove the specified partner from storage.
     */
    public function destroy(Partner $partner)
    {
        $title = $partner->title;
        $logoPath = $partner->logo_path;
        $partner->delete();
        PublicAsset::delete($logoPath);

        return redirect()->route('partners.index')
            ->with('success', "Partner '{$title}' deleted successfully!");
    }

    private function uniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title) ?: 'partner';
        $slug = $baseSlug;
        $suffix = 2;

        while (Partner::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}