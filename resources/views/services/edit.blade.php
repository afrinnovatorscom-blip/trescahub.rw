@extends('front.layouts.admin')

@section('title', 'Edit Service')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Service
        <small class="text-muted fw-normal fs-6 ms-2">{{ $service->title }}</small>
    </h4>
    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('services.update', $service) }}" method="POST" enctype="multipart/form-data">
@csrf @method('PUT')

{{-- ── BASIC INFO ──────────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Basic Information</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $service->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Slug</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                    <input type="text" name="slug" id="slug"
                           class="form-control @error('slug') is-invalid @enderror"
                           value="{{ old('slug', $service->slug) }}">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-text">Clear to auto-regenerate from title.</div>
            </div>

            {{-- Price in FRW --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">FRW</span>
                    <input type="number" name="price" step="0.01" min="0"
                           class="form-control @error('price') is-invalid @enderror"
                           value="{{ old('price', $service->price) }}" required>
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Working Day</label>
                <input type="text" name="working_day"
                       class="form-control @error('working_day') is-invalid @enderror"
                       value="{{ old('working_day', $service->working_day) }}"
                       placeholder="e.g. Mon - Fri">
                @error('working_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $service->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>
</div>

{{-- ── CLASSIFICATION & SUBCATEGORY ───────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Classification & Subcategory</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Classification <small class="text-muted fw-normal">(Category)</small>
                </label>
                <select name="classification_name" id="classification-select"
                        class="form-select @error('classification_name') is-invalid @enderror">
                    <option value="">— Select classification —</option>
                    @foreach($classifications as $cat)
                        <option value="{{ $cat->id }}"
                            {{ old('classification_name', $service->classification_name) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->title }}
                        </option>
                    @endforeach
                </select>
                @error('classification_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Subcategories <small class="text-muted fw-normal">(loaded from classification)</small>
                </label>

                <div class="input-group mb-2">
                    <select id="subcategory-select" class="form-select" disabled>
                        <option value="">— Select classification first —</option>
                    </select>
                    <button type="button" class="btn btn-outline-primary" id="add-subcategory" disabled>
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>

                <div id="subcategory-tags" class="d-flex flex-wrap gap-2 mb-1"></div>
                <div id="subcategory-inputs"></div>

                @error('subcategory')<div class="text-danger small">{{ $message }}</div>@enderror
                @error('subcategory.*')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>
</div>

{{-- ── WHAT'S INCLUDED ─────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">What's Included</div>
    <div class="card-body">
        <div id="what-included-list">
            @php $items = old('what_included', $service->what_included ?? []); @endphp
            @forelse($items as $item)
                <div class="input-group mb-2 what-included-row">
                    <input type="text" name="what_included[]" class="form-control"
                           value="{{ $item }}" placeholder="e.g. Free WiFi">
                    <button type="button" class="btn btn-outline-danger remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @empty
                <div class="input-group mb-2 what-included-row">
                    <input type="text" name="what_included[]" class="form-control"
                           placeholder="e.g. Free WiFi">
                    <button type="button" class="btn btn-outline-danger remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @endforelse
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="add-what-included">
            <i class="bi bi-plus-lg me-1"></i> Add Item
        </button>
    </div>
</div>

{{-- ── HOW SERVICE WORKS ───────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">How Service Works</div>
    <div class="card-body">
        <div id="how-works-list">
            @php $steps = old('how_service_works', $service->how_service_works ?? []); @endphp
            @forelse($steps as $i => $step)
                <div class="input-group mb-2 how-works-row">
                    <span class="input-group-text step-num">{{ $i + 1 }}</span>
                    <input type="text" name="how_service_works[]" class="form-control"
                           value="{{ $step }}" placeholder="Describe this step…">
                    <button type="button" class="btn btn-outline-danger remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @empty
                <div class="input-group mb-2 how-works-row">
                    <span class="input-group-text step-num">1</span>
                    <input type="text" name="how_service_works[]" class="form-control"
                           placeholder="Describe this step…">
                    <button type="button" class="btn btn-outline-danger remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @endforelse
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="add-how-works">
            <i class="bi bi-plus-lg me-1"></i> Add Step
        </button>
    </div>
</div>

{{-- ── SERVICE STATUS ──────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Service Status</div>
    <div class="card-body">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch"
                   name="is_popular" id="is_popular" value="1"
                   {{ old('is_popular', $service->is_popular) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="is_popular">
                <i class="bi bi-fire text-warning me-1"></i> Mark as Popular
            </label>
        </div>
    </div>
</div>

{{-- ── IMAGES ──────────────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Images</div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Thumbnail --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Thumbnail</label>
                @if($service->thumbnail)
                    <div class="mb-2">
                        <img src="{{ asset('public/' . $service->thumbnail) }}"
                             class="rounded"
                             style="width:100px;height:100px;object-fit:cover"
                             alt="{{ $service->title }}">
                        <div class="form-text">Upload a new image to replace it.</div>
                    </div>
                @endif
                <input type="file" name="thumbnail" accept="image/*"
                       class="form-control @error('thumbnail') is-invalid @enderror"
                       id="thumbnail-input">
                @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="mt-2" id="thumbnail-preview"></div>
            </div>

            {{-- Other images --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Other Images <small class="text-muted">(new uploads append to existing)</small>
                </label>
                @if(!empty($service->other_images))
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @foreach($service->other_images as $img)
                            <img src="{{ asset('public/' . $img) }}"
                                 class="rounded"
                                 style="width:80px;height:80px;object-fit:cover"
                                 alt="Service image">
                        @endforeach
                    </div>
                @endif
                <input type="file" name="other_images[]" accept="image/*" multiple
                       class="form-control @error('other_images') is-invalid @enderror"
                       id="other-images-input">
                @error('other_images')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('other_images.*')<div class="text-danger small">{{ $message }}</div>@enderror
                <div class="mt-2 d-flex flex-wrap gap-2" id="other-images-preview"></div>
            </div>

        </div>
    </div>
</div>

<div class="d-flex gap-2 mb-5">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i> Update Service
    </button>
    <a href="{{ route('services.show', $service) }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>

</form>
@endsection

@push('scripts')
<script>
// ── Slug auto-generate ──────────────────────────────────────────────────────
const titleEl = document.getElementById('title');
const slugEl  = document.getElementById('slug');
titleEl.addEventListener('input', () => {
    if (slugEl.value === '') {
        slugEl.value = titleEl.value.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/^-+|-+$/g, '');
    }
});

// ── Classification → Subcategory AJAX ──────────────────────────────────────
const classificationSelect = document.getElementById('classification-select');
const subcategorySelect    = document.getElementById('subcategory-select');
const addSubBtn            = document.getElementById('add-subcategory');
const subcategoryTags      = document.getElementById('subcategory-tags');
const subcategoryInputs    = document.getElementById('subcategory-inputs');
const selectedSubs         = new Map(); // string → string

// Existing saved subcategories (plain strings)
const existingSubs = @json($selectedSubcategory);

function renderSubTags() {
    subcategoryTags.innerHTML   = '';
    subcategoryInputs.innerHTML = '';
    selectedSubs.forEach((name, value) => {
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary d-flex align-items-center gap-1 px-2 py-2';
        badge.innerHTML = `${name}
            <button type="button" class="btn-close btn-close-white ms-1"
                style="font-size:.6rem" data-val="${value}"></button>`;
        badge.querySelector('.btn-close').addEventListener('click', () => {
            selectedSubs.delete(value);
            renderSubTags();
        });
        subcategoryTags.appendChild(badge);

        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'subcategory[]';
        inp.value = value;
        subcategoryInputs.appendChild(inp);
    });
}

async function loadSubcategories(categoryId, preSelect = []) {
    subcategorySelect.innerHTML = '<option value="">Loading…</option>';
    subcategorySelect.disabled  = true;
    addSubBtn.disabled          = true;

    if (!categoryId) {
        subcategorySelect.innerHTML = '<option value="">— Select classification first —</option>';
        return;
    }

    try {
        const res  = await fetch(`/admin/services/subcategories?category_id=${categoryId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error('Server error');

        const data = await res.json(); // array of strings

        subcategorySelect.innerHTML = '<option value="">— Select subcategory —</option>';

        if (!data.length) {
            subcategorySelect.innerHTML = '<option value="">No subcategories found</option>';
            return;
        }

        data.forEach(sub => {
            const opt       = document.createElement('option');
            opt.value       = sub;
            opt.textContent = sub;
            subcategorySelect.appendChild(opt);
        });

        subcategorySelect.disabled = false;
        addSubBtn.disabled         = false;

        // Pre-select existing saved subcategories
        preSelect.forEach(val => selectedSubs.set(val, val));
        renderSubTags();

    } catch (e) {
        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
    }
}

// On page load: auto-load subcategories for the already-selected classification
const initCategoryId = classificationSelect.value;
if (initCategoryId) {
    loadSubcategories(initCategoryId, existingSubs);
}

// On classification change: clear & reload
classificationSelect.addEventListener('change', function () {
    selectedSubs.clear();
    renderSubTags();
    loadSubcategories(this.value);
});

addSubBtn.addEventListener('click', () => {
    const opt = subcategorySelect.options[subcategorySelect.selectedIndex];
    if (!opt || !opt.value) return;
    selectedSubs.set(opt.value, opt.value);
    renderSubTags();
});

// ── What's Included ────────────────────────────────────────────────────────
document.getElementById('add-what-included').addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'input-group mb-2 what-included-row';
    row.innerHTML = `<input type="text" name="what_included[]" class="form-control" placeholder="e.g. Free WiFi">
        <button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>`;
    document.getElementById('what-included-list').appendChild(row);
    row.querySelector('.remove-row').addEventListener('click', () => row.remove());
});

// ── How Service Works ──────────────────────────────────────────────────────
document.getElementById('add-how-works').addEventListener('click', () => {
    const list  = document.getElementById('how-works-list');
    const count = list.querySelectorAll('.how-works-row').length + 1;
    const row   = document.createElement('div');
    row.className = 'input-group mb-2 how-works-row';
    row.innerHTML = `<span class="input-group-text step-num">${count}</span>
        <input type="text" name="how_service_works[]" class="form-control" placeholder="Describe this step…">
        <button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>`;
    list.appendChild(row);
    row.querySelector('.remove-row').addEventListener('click', () => { row.remove(); renumberSteps(); });
    renumberSteps();
});

function renumberSteps() {
    document.querySelectorAll('#how-works-list .step-num').forEach((el, i) => el.textContent = i + 1);
}

document.querySelectorAll('.remove-row').forEach(btn => {
    btn.addEventListener('click', () => { btn.closest('.input-group').remove(); renumberSteps(); });
});

// ── Image preview ──────────────────────────────────────────────────────────
document.getElementById('thumbnail-input').addEventListener('change', function () {
    const preview = document.getElementById('thumbnail-preview');
    preview.innerHTML = '';
    if (this.files[0]) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(this.files[0]);
        img.className = 'rounded';
        img.style = 'width:100px;height:100px;object-fit:cover';
        preview.appendChild(img);
    }
});

document.getElementById('other-images-input').addEventListener('change', function () {
    const preview = document.getElementById('other-images-preview');
    preview.innerHTML = '';
    [...this.files].forEach(file => {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'rounded';
        img.style = 'width:80px;height:80px;object-fit:cover';
        preview.appendChild(img);
    });
});
</script>
@endpush