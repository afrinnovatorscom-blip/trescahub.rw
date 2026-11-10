@extends('front.layouts.admin')

@section('title', 'Create Service')

@push('styles')
<style>
    .services-create-page {
        --service-form-border: #e2e8f0;
        --service-form-heading: #0f172a;
        --service-form-body: #475569;
        --service-form-muted: #64748b;
        --service-form-surface: #fff;
        --service-form-subtle: #f8fafc;
        color: var(--service-form-body);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .services-create-page .services-create-heading {
        gap: 16px;
        margin-bottom: 20px !important;
    }

    .services-create-page .services-create-title {
        color: var(--service-form-heading);
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .services-create-page .services-create-form .card {
        overflow: hidden;
        border: 1px solid var(--service-form-border);
        border-radius: 12px;
        background: var(--service-form-surface);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .services-create-page .services-create-form .card-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--service-form-border);
        background: var(--service-form-subtle) !important;
        color: var(--service-form-heading);
        font-size: .9375rem;
        font-weight: 600 !important;
    }

    .services-create-page .services-create-form .card-body {
        padding: 20px;
    }

    .services-create-page .services-create-form .form-label {
        margin-bottom: 7px;
        color: var(--service-form-heading);
        font-size: .8125rem;
        font-weight: 600 !important;
    }

    .services-create-page .services-create-form .form-control,
    .services-create-page .services-create-form .form-select {
        min-height: 42px;
        border-color: var(--service-form-border);
        border-radius: 8px;
        color: var(--service-form-heading);
        font-size: .875rem;
    }

    .services-create-page .services-create-form textarea.form-control {
        min-height: 112px;
        resize: vertical;
    }

    .services-create-page .services-create-form .form-control::placeholder {
        color: #94a3b8;
        opacity: 1;
    }

    .services-create-page .services-create-form .form-control:focus,
    .services-create-page .services-create-form .form-select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .14);
    }

    .services-create-page .services-create-form .form-control:disabled,
    .services-create-page .services-create-form .form-select:disabled {
        background-color: var(--service-form-subtle);
        color: var(--service-form-muted);
    }

    .services-create-page .services-create-form .input-group-text {
        min-height: 42px;
        border-color: var(--service-form-border);
        background: var(--service-form-subtle);
        color: var(--service-form-muted);
        font-size: .875rem;
    }

    .services-create-page .services-create-form .input-group > .form-control,
    .services-create-page .services-create-form .input-group > .form-select {
        min-width: 0;
    }

    .services-create-page .services-create-form .form-check {
        min-height: auto;
    }

    .services-create-page .services-create-form .form-check-input {
        border-color: #cbd5e1;
        cursor: pointer;
    }

    .services-create-page .services-create-form .form-check-input:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .14);
    }

    .services-create-page .services-create-form .form-check-input:checked {
        border-color: #2563eb;
        background-color: #2563eb;
    }

    .services-create-page .services-create-form .form-check-label {
        color: var(--service-form-heading);
        font-size: .875rem;
    }

    .services-create-page .services-create-form small.text-muted,
    .services-create-page .services-create-form .text-muted {
        color: var(--service-form-muted) !important;
    }

    .services-create-page .services-create-form .btn {
        min-height: 38px;
        border-radius: 8px;
        font-size: .8125rem;
        font-weight: 600;
    }

    .services-create-page .services-create-form .btn-primary {
        border-color: #2563eb;
        background-color: #2563eb;
    }

    .services-create-page .services-create-form .btn-primary:hover {
        border-color: #1d4ed8;
        background-color: #1d4ed8;
    }

    .services-create-page .services-create-form .btn-outline-primary {
        border-color: #bfdbfe;
        color: #1d4ed8;
    }

    .services-create-page .services-create-form .btn-outline-primary:hover {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .services-create-page .services-create-form .btn-outline-danger {
        border-color: #fecaca;
        color: #b91c1c;
    }

    .services-create-page .services-create-form .btn-outline-danger:hover {
        border-color: #b91c1c;
        background: #fef2f2;
        color: #991b1b;
    }

    .services-create-page .services-create-form .form-control[type="file"] {
        height: auto;
        min-height: 44px;
        padding: 6px 8px;
        color: var(--service-form-muted);
    }

    .services-create-page .services-create-form .form-control[type="file"]::file-selector-button {
        min-height: 30px;
        margin: 0 10px 0 0;
        padding: 4px 10px;
        border: 1px solid var(--service-form-border);
        border-radius: 6px;
        background: var(--service-form-subtle);
        color: var(--service-form-heading);
        font-size: .8125rem;
        font-weight: 500;
    }

    .services-create-page .services-create-form .step-num {
        min-width: 42px;
        justify-content: center;
        font-variant-numeric: tabular-nums;
    }

    .services-create-page .services-create-actions {
        flex-wrap: wrap;
    }

    .services-create-page .services-create-form .services-create-actions .btn {
        min-height: 42px;
        padding-right: 18px !important;
        padding-left: 18px !important;
    }

    .services-create-page #thumbnail-preview img,
    .services-create-page #other-images-preview img {
        border: 1px solid var(--service-form-border);
        border-radius: 8px !important;
        object-fit: cover;
    }

    @media (max-width: 767.98px) {
        .services-create-page .services-create-heading {
            align-items: flex-start !important;
            flex-wrap: wrap;
        }

        .services-create-page .services-create-title {
            font-size: 1.25rem;
        }

        .services-create-page .services-create-form .card-body {
            padding: 16px;
        }
    }

    @media (max-width: 420px) {
        .services-create-page .services-create-form .input-group > .btn {
            padding-right: 10px;
            padding-left: 10px;
        }

        .services-create-page .services-create-actions > .btn {
            flex: 1 1 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="services-create-page">
    <div class="services-create-heading d-flex justify-content-between align-items-center">
        <h4 class="services-create-title fw-bold mb-0"><i class="bi bi-plus-circle-fill me-2 text-primary"></i>Create Service</h4>
        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

<form action="{{ route('services.store') }}" method="POST" enctype="multipart/form-data" class="services-create-form">
@csrf

{{-- ── BASIC INFO ──────────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Basic Information</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">
                    Slug <small class="text-muted fw-normal">(auto-generated if blank)</small>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                    <input type="text" name="slug" id="slug"
                           class="form-control @error('slug') is-invalid @enderror"
                           value="{{ old('slug') }}">
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">RWF</span>
                    <input type="number" name="price" step="0.01" min="0"
                           class="form-control @error('price') is-invalid @enderror"
                           value="{{ old('price', '0.00') }}" required>
                    @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Working Day</label>
                <input type="text" name="working_day"
                       class="form-control @error('working_day') is-invalid @enderror"
                       value="{{ old('working_day') }}" placeholder="e.g. Mon - Fri or 3-5 Business Days">
                @error('working_day')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Describe this service…">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>
</div>

{{-- ── STATUS FLAGS ────────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Service Status</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="is_popular" id="is_popular" value="1"
                           {{ old('is_popular') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="is_popular">
                        <i class="bi bi-fire text-warning me-1"></i> Mark as Popular
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    Popular services appear in the <code>popular-service</code> section when viewed.
                </small>
            </div>

            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="is_in_stock" id="is_in_stock" value="1"
                           {{ old('is_in_stock') ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="is_in_stock">
                        <i class="bi bi-check-circle text-success me-1"></i> Mark as In Stock
                    </label>
                </div>
                <small class="text-muted d-block mt-1">
                    In-stock services appear in the <code>our-services</code> section when viewed.
                </small>
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
                            {{ old('classification_name') == $cat->id ? 'selected' : '' }}>
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
            @if(old('what_included'))
                @foreach(old('what_included') as $item)
                    <div class="input-group mb-2 what-included-row">
                        <input type="text" name="what_included[]" class="form-control"
                               value="{{ $item }}" placeholder="e.g. Free WiFi">
                        <button type="button" class="btn btn-outline-danger remove-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                @endforeach
            @else
                <div class="input-group mb-2 what-included-row">
                    <input type="text" name="what_included[]" class="form-control"
                           placeholder="e.g. Free WiFi">
                    <button type="button" class="btn btn-outline-danger remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @endif
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
            @if(old('how_service_works'))
                @foreach(old('how_service_works') as $i => $step)
                    <div class="input-group mb-2 how-works-row">
                        <span class="input-group-text step-num">{{ $i + 1 }}</span>
                        <input type="text" name="how_service_works[]" class="form-control"
                               value="{{ $step }}" placeholder="Describe this step…">
                        <button type="button" class="btn btn-outline-danger remove-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                @endforeach
            @else
                <div class="input-group mb-2 how-works-row">
                    <span class="input-group-text step-num">1</span>
                    <input type="text" name="how_service_works[]" class="form-control"
                           placeholder="Describe this step…">
                    <button type="button" class="btn btn-outline-danger remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @endif
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="add-how-works">
            <i class="bi bi-plus-lg me-1"></i> Add Step
        </button>
    </div>
</div>

{{-- ── IMAGES ──────────────────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Images</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold">Thumbnail</label>
                <input type="file" name="thumbnail" accept="image/*"
                       class="form-control @error('thumbnail') is-invalid @enderror"
                       id="thumbnail-input">
                @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="mt-2" id="thumbnail-preview"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Other Images <small class="text-muted">(multiple)</small></label>
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

<div class="services-create-actions d-flex gap-2 mb-5">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i> Save Service
    </button>
    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
</div>

</form>
</div>
@endsection

@push('scripts')
<script>
// ── Slug auto-generate ──────────────────────────────────────────────────────
const titleEl = document.getElementById('title');
const slugEl  = document.getElementById('slug');
titleEl.addEventListener('input', () => {
    if (slugEl.dataset.manual) return;
    slugEl.value = titleEl.value.toLowerCase().trim()
        .replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/^-+|-+$/g, '');
});
slugEl.addEventListener('input', () => { slugEl.dataset.manual = slugEl.value ? '1' : ''; });

// ── Mutual exclusivity: popular & in_stock can't both be checked ────────────
const isPopularEl  = document.getElementById('is_popular');
const isInStockEl  = document.getElementById('is_in_stock');

isPopularEl.addEventListener('change', () => {
    if (isPopularEl.checked) isInStockEl.checked = false;
});
isInStockEl.addEventListener('change', () => {
    if (isInStockEl.checked) isPopularEl.checked = false;
});

// ── Classification → load subcategories via AJAX ────────────────────────────
const classificationSelect = document.getElementById('classification-select');
const subcategorySelect    = document.getElementById('subcategory-select');
const addSubBtn            = document.getElementById('add-subcategory');
const subcategoryTags      = document.getElementById('subcategory-tags');
const subcategoryInputs    = document.getElementById('subcategory-inputs');
const selectedSubs         = new Map();

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

classificationSelect.addEventListener('change', async function () {
    const categoryId = this.value;

    subcategorySelect.innerHTML = '<option value="">Loading…</option>';
    subcategorySelect.disabled  = true;
    addSubBtn.disabled          = true;
    selectedSubs.clear();
    renderSubTags();

    if (!categoryId) {
        subcategorySelect.innerHTML = '<option value="">— Select classification first —</option>';
        return;
    }

    try {
        const res  = await fetch(`/admin/services/subcategories?category_id=${categoryId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error('Server error');

        const data = await res.json();

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

    } catch (e) {
        subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
    }
});

addSubBtn.addEventListener('click', () => {
    const opt = subcategorySelect.options[subcategorySelect.selectedIndex];
    if (!opt || !opt.value) return;
    selectedSubs.set(opt.value, opt.value);
    renderSubTags();
});

// ── Restore old() on validation failure ────────────────────────────────────
@if(old('subcategory') && old('classification_name'))
    (async () => {
        classificationSelect.value = "{{ old('classification_name') }}";
        classificationSelect.dispatchEvent(new Event('change'));
        await new Promise(r => setTimeout(r, 700));
        const oldSubs = @json(old('subcategory', []));
        oldSubs.forEach(val => selectedSubs.set(val, val));
        renderSubTags();
    })();
@endif

// ── Dynamic row: What's Included ───────────────────────────────────────────
document.getElementById('add-what-included').addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'input-group mb-2 what-included-row';
    row.innerHTML = `<input type="text" name="what_included[]" class="form-control" placeholder="e.g. Free WiFi">
        <button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>`;
    document.getElementById('what-included-list').appendChild(row);
    bindRemove(row);
});

// ── Dynamic row: How Service Works ─────────────────────────────────────────
document.getElementById('add-how-works').addEventListener('click', () => {
    const list  = document.getElementById('how-works-list');
    const count = list.querySelectorAll('.how-works-row').length + 1;
    const row   = document.createElement('div');
    row.className = 'input-group mb-2 how-works-row';
    row.innerHTML = `<span class="input-group-text step-num">${count}</span>
        <input type="text" name="how_service_works[]" class="form-control" placeholder="Describe this step…">
        <button type="button" class="btn btn-outline-danger remove-row"><i class="bi bi-trash"></i></button>`;
    list.appendChild(row);
    bindRemove(row);
    renumberSteps();
});

function bindRemove(row) {
    row.querySelector('.remove-row').addEventListener('click', () => {
        row.remove();
        renumberSteps();
    });
}

function renumberSteps() {
    document.querySelectorAll('#how-works-list .step-num').forEach((el, i) => {
        el.textContent = i + 1;
    });
}

document.querySelectorAll('.remove-row').forEach(btn => {
    btn.addEventListener('click', () => {
        btn.closest('.input-group').remove();
        renumberSteps();
    });
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