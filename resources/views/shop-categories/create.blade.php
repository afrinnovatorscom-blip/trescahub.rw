@extends('front.layouts.admin')

@push('styles')
<style>
    .shop-category-create-page {
        color: #475569;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .shop-category-create-page .shop-category-create-content {
        max-width: 920px;
        margin-right: auto;
        margin-left: auto;
    }

    .shop-category-create-page .shop-category-breadcrumb {
        color: #64748b;
        font-size: .8125rem;
    }

    .shop-category-create-page .shop-category-breadcrumb a {
        color: #64748b;
        text-decoration: none;
    }

    .shop-category-create-page .shop-category-breadcrumb a:hover {
        color: #2563eb;
    }

    .shop-category-create-page .shop-category-page-title {
        margin-bottom: 4px;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .shop-category-create-page .shop-category-intro {
        color: #64748b;
        font-size: .875rem;
        line-height: 1.5;
    }

    .shop-category-create-page .shop-category-alert {
        border-color: #fecaca;
        border-radius: 8px;
        background: #fef2f2;
        color: #b91c1c;
        font-size: .875rem;
    }

    .shop-category-create-page .shop-category-form-section {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        box-shadow: none;
    }

    .shop-category-create-page .shop-category-section-header {
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc !important;
        color: #0f172a;
        padding: 14px 20px !important;
    }

    .shop-category-create-page .shop-category-section-header h2 {
        color: #0f172a;
        font-size: .9375rem;
        font-weight: 600;
    }

    .shop-category-create-page .shop-category-section-header p {
        color: #64748b;
        font-size: .8125rem;
        line-height: 1.45;
    }

    .shop-category-create-page .shop-category-section-body {
        padding: 20px !important;
    }

    .shop-category-create-page .form-label {
        margin-bottom: 7px;
        color: #0f172a;
        font-size: .8125rem;
        font-weight: 600 !important;
    }

    .shop-category-create-page .form-control,
    .shop-category-create-page .form-select {
        min-height: 42px;
        border-color: #e2e8f0;
        border-radius: 7px;
        color: #0f172a;
        font-size: .875rem;
    }

    .shop-category-create-page textarea.form-control {
        min-height: 88px;
        resize: vertical;
    }

    .shop-category-create-page .form-control:focus,
    .shop-category-create-page .form-select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .14);
    }

    .shop-category-create-page .form-control::placeholder {
        color: #94a3b8;
        opacity: 1;
    }

    .shop-category-create-page .form-text {
        margin-top: 6px;
        color: #64748b;
        font-size: .75rem;
        line-height: 1.45;
    }

    .shop-category-create-page .invalid-feedback {
        color: #b91c1c;
        font-size: .8125rem;
    }

    .shop-category-create-page .form-control.is-invalid,
    .shop-category-create-page .form-select.is-invalid {
        border-color: #b91c1c;
    }

    .shop-category-create-page .form-control.is-invalid:focus,
    .shop-category-create-page .form-select.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(185, 28, 28, .12);
    }

    .shop-category-create-page .subcategory-row {
        margin-bottom: 12px !important;
        padding: 16px !important;
        border-color: #e2e8f0 !important;
        border-radius: 7px !important;
        background: #fff;
    }

    .shop-category-create-page .subcategory-row:last-child {
        margin-bottom: 0 !important;
    }

    .shop-category-create-page .subcategory-row h3 {
        color: #0f172a;
        font-size: .875rem;
        font-weight: 600;
    }

    .shop-category-create-page .remove-subcategory {
        color: #b91c1c !important;
        font-size: .8125rem;
        font-weight: 500;
        text-decoration: none;
    }

    .shop-category-create-page .remove-subcategory:hover {
        color: #991b1b !important;
        text-decoration: underline;
    }

    .shop-category-create-page .btn {
        min-height: 40px;
        border-radius: 7px;
        font-size: .8125rem;
        font-weight: 600;
    }

    .shop-category-create-page .btn-primary {
        border-color: #2563eb;
        background-color: #2563eb;
    }

    .shop-category-create-page .btn-primary:hover {
        border-color: #1d4ed8;
        background-color: #1d4ed8;
    }

    .shop-category-create-page .btn-outline-primary {
        border-color: #bfdbfe;
        background: #fff;
        color: #1d4ed8;
    }

    .shop-category-create-page .btn-outline-primary:hover {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .shop-category-create-page .btn-outline-secondary {
        border-color: #cbd5e1;
        background: #fff;
        color: #475569;
    }

    .shop-category-create-page .btn-outline-secondary:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .shop-category-create-page .btn:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .2);
        outline-offset: 2px;
    }

    .shop-category-create-page .shop-category-form-actions {
        flex-wrap: wrap;
    }

    @media (max-width: 767.98px) {
        .shop-category-create-page .shop-category-page-title {
            font-size: 1.25rem;
        }

        .shop-category-create-page .shop-category-section-header {
            padding: 12px 16px !important;
        }

        .shop-category-create-page .shop-category-section-body {
            padding: 16px !important;
        }
    }

    @media (max-width: 575.98px) {
        .shop-category-create-page .shop-category-form-actions {
            flex-direction: column-reverse;
        }

        .shop-category-create-page .shop-category-form-actions > .btn {
            width: 100%;
        }

        .shop-category-create-page #addSubcategory {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')

@php
    $subcategoryRows = old('subcategories', []);
    $subcategoryRows = is_array($subcategoryRows) ? $subcategoryRows : [];
    $nextSubcategoryIndex = count($subcategoryRows)
        ? max(array_map('intval', array_keys($subcategoryRows))) + 1
        : 0;
@endphp

<div class="container-fluid py-4 shop-category-create-page">
    <div class="shop-category-create-content mx-auto" style="max-width: 920px;">
        <div class="mb-4">
            <div class="shop-category-breadcrumb mb-2">
                <a href="{{ route('shop-categories.index') }}">Shop Categories</a>
                <span class="mx-1">/</span>
                <span>Create</span>
            </div>
            <h1 class="shop-category-page-title">Create Product Category</h1>
            <p class="shop-category-intro mb-0">Add a shop category and optionally organize it with subcategories.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger shop-category-alert" role="alert">
                Please review the highlighted fields and correct the errors below.
            </div>
        @endif

        <form action="{{ route('shop-categories.store') }}" method="POST" id="categoryForm">
            @csrf

            <section class="card shop-category-form-section mb-4">
                <div class="card-header shop-category-section-header">
                    <h2 class="h5 mb-0">Category Information</h2>
                </div>
                <div class="card-body shop-category-section-body p-3 p-md-4">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               maxlength="255"
                               autocomplete="off"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('slug') is-invalid @enderror"
                               id="slug"
                               name="slug"
                               value="{{ old('slug') }}"
                               maxlength="255"
                               autocomplete="off"
                               required>
                        <div class="form-text">Generated from the category name. You can edit it if needed; it must be unique.</div>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  maxlength="2000"
                                  placeholder="Optional details about this category">{{ old('description') }}</textarea>
                        <div class="form-text">Optional, up to 2,000 characters.</div>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror"
                                id="status"
                                name="status"
                                required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <div class="form-text">Inactive categories and their subcategories are hidden from public shop browsing.</div>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="card shop-category-form-section mb-4">
                <div class="card-header shop-category-section-header d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <div>
                        <h2 class="h5 mb-0">Subcategories</h2>
                        <p class="small text-muted mb-0 mt-1">Optional. Each subcategory will belong to this category.</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addSubcategory">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Another Subcategory
                    </button>
                </div>
                <div class="card-body shop-category-section-body p-3 p-md-4">
                    <div id="subcategoriesContainer" aria-live="polite">
                        @foreach($subcategoryRows as $index => $subcategory)
                            <div class="subcategory-row border rounded p-3 mb-3" data-index="{{ $index }}">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h3 class="h6 mb-0">Subcategory</h3>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-subcategory">
                                        Remove
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <label for="subcategory-name-{{ $index }}" class="form-label fw-semibold">Subcategory Name <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('subcategories.'.$index.'.name') is-invalid @enderror subcategory-name"
                                           id="subcategory-name-{{ $index }}"
                                           name="subcategories[{{ $index }}][name]"
                                           value="{{ $subcategory['name'] ?? '' }}"
                                           maxlength="255"
                                           required>
                                    @error('subcategories.'.$index.'.name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label for="subcategory-slug-{{ $index }}" class="form-label fw-semibold">Slug</label>
                                    <input type="text"
                                           class="form-control subcategory-slug @error('subcategories.'.$index.'.slug') is-invalid @enderror"
                                           id="subcategory-slug-{{ $index }}"
                                           name="subcategories[{{ $index }}][slug]"
                                           value="{{ $subcategory['slug'] ?? '' }}"
                                           maxlength="255">
                                    <div class="form-text">Generated from the name; edit only if needed.</div>
                                    @error('subcategories.'.$index.'.slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div>
                                    <label for="subcategory-description-{{ $index }}" class="form-label fw-semibold">Description</label>
                                    <textarea class="form-control @error('subcategories.'.$index.'.description') is-invalid @enderror"
                                              id="subcategory-description-{{ $index }}"
                                              name="subcategories[{{ $index }}][description]"
                                              rows="2"
                                              maxlength="2000">{{ $subcategory['description'] ?? '' }}</textarea>
                                    @error('subcategories.'.$index.'.description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('subcategories')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                    <div id="emptySubcategoriesMessage" class="text-muted small {{ count($subcategoryRows) ? 'd-none' : '' }}">
                        No subcategories added.
                    </div>
                </div>
            </section>

            <div class="shop-category-form-actions d-flex flex-column-reverse flex-sm-row justify-content-end gap-2">
                <a href="{{ route('shop-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4" id="saveCategoryButton">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('categoryForm');
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const container = document.getElementById('subcategoriesContainer');
    const emptyMessage = document.getElementById('emptySubcategoriesMessage');
    const addButton = document.getElementById('addSubcategory');
    let rowIndex = {{ $nextSubcategoryIndex }};

    function toSlug(value) {
        return value.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function updateEmptyMessage() {
        emptyMessage.classList.toggle('d-none', container.children.length > 0);
    }

    function syncSlug(input, name) {
        const slug = input.closest(name ? '.subcategory-row' : 'form')
            .querySelector(name ? '.subcategory-slug' : '#slug');
        if (!slug.dataset.manuallyEdited) {
            slug.value = toSlug(input.value);
        }
    }

    function createSubcategoryRow(index) {
        const row = document.createElement('div');
        row.className = 'subcategory-row border rounded p-3 mb-3';
        row.dataset.index = index;
        row.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h3 class="h6 mb-0">Subcategory</h3>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-subcategory">Remove</button>
            </div>
            <div class="mb-3">
                <label for="subcategory-name-${index}" class="form-label fw-semibold">Subcategory Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control subcategory-name" id="subcategory-name-${index}" name="subcategories[${index}][name]" maxlength="255" required>
            </div>
            <div class="mb-3">
                <label for="subcategory-slug-${index}" class="form-label fw-semibold">Slug</label>
                <input type="text" class="form-control subcategory-slug" id="subcategory-slug-${index}" name="subcategories[${index}][slug]" maxlength="255">
                <div class="form-text">Generated from the name; edit only if needed.</div>
            </div>
            <div>
                <label for="subcategory-description-${index}" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="subcategory-description-${index}" name="subcategories[${index}][description]" rows="2" maxlength="2000"></textarea>
            </div>`;
        return row;
    }

    nameInput.addEventListener('input', function () {
        syncSlug(this, false);
    });
    slugInput.addEventListener('input', function () {
        this.dataset.manuallyEdited = 'true';
    });

    container.addEventListener('input', function (event) {
        if (event.target.matches('.subcategory-name')) {
            syncSlug(event.target, true);
        }
        if (event.target.matches('.subcategory-slug')) {
            event.target.dataset.manuallyEdited = 'true';
        }
    });

    container.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-subcategory');
        if (removeButton) {
            removeButton.closest('.subcategory-row').remove();
            updateEmptyMessage();
        }
    });

    addButton.addEventListener('click', function () {
        const row = createSubcategoryRow(rowIndex++);
        container.appendChild(row);
        updateEmptyMessage();
        row.querySelector('.subcategory-name').focus();
    });

    form.addEventListener('submit', function () {
        const button = document.getElementById('saveCategoryButton');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Saving...';
    });

    slugInput.dataset.manuallyEdited = slugInput.value.trim() ? 'true' : '';
    if (!slugInput.value && nameInput.value) {
        slugInput.value = toSlug(nameInput.value);
    }
    container.querySelectorAll('.subcategory-slug').forEach(function (input) {
        input.dataset.manuallyEdited = input.value.trim() ? 'true' : '';
    });
    updateEmptyMessage();
});
</script>

@endsection
