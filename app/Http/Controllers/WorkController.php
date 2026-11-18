<?php

namespace App\Http\Controllers;

use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class WorkController extends Controller
{
    // ─── Public ───────────────────────────────────────────────────────────────

    public function publicIndex()
    {
        $works = Work::latest()->get();

        return view('front.section.works', compact('works'));
    }

    // ─── Admin: Index ─────────────────────────────────────────────────────────

    public function index()
    {
        $works = Work::query()->orderBy('id')->paginate(12);

        return view('works.index', compact('works'));
    }

    // ─── Admin: Create ────────────────────────────────────────────────────────

    public function create()
    {
        return view('works.create');
    }

    // ─── Admin: Store ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail'   => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $this->saveImage($request->file('thumbnail'));
        }

        Work::create($validated);

        return redirect()->route('works.index')
            ->with('success', 'Work created successfully!');
    }

    // ─── Admin: Edit ──────────────────────────────────────────────────────────

    public function edit(Work $work)
    {
        return view('works.edit', compact('work'));
    }

    // ─── Admin: Update ────────────────────────────────────────────────────────

    public function update(Request $request, Work $work)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail'   => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $this->deleteImage($work->thumbnail);
            $validated['thumbnail'] = $this->saveImage($request->file('thumbnail'));
        }

        $work->update($validated);

        return redirect()->route('works.index')
            ->with('success', 'Work updated successfully!');
    }

    // ─── Admin: Destroy ───────────────────────────────────────────────────────

    public function destroy(Work $work)
    {
        $this->deleteImage($work->thumbnail);
        $title = $work->title;
        $work->delete();

        return redirect()->route('works.index')
            ->with('success', "Work \"{$title}\" deleted successfully!");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function saveImage($file): string
    {
        $destination = public_path('assets/works');

        if (!File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'works/' . $filename;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && File::exists(public_path('assets/' . $path))) {
            File::delete(public_path('assets/' . $path));
        }
    }
}