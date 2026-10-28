@extends('front.layouts.admin')
@section('content')

@php
    $subcategoryRows = old('subcategories');
    $subcategoryRows = is_array($subcategoryRows)
        ? $subcategoryRows
        : $shopCategory->subcategories->map(fn ($subcategory) => [
            'id' => $subcategory->id,
            'name' => $subcategory->name,
            'slug' => $subcategory->slug,
            'description' => $subcategory->description,
        ])->all();
    $nextSubcategoryIndex = count($subcategoryRows)
        ? max(array_map('intval', array_keys($subcategoryRows))) + 1
        : 0;
@endphp

<div class="container-fluid py-4">
    <div class="mx-auto" style="max-width: 920px;">
        <div class="mb-4">
            <div class="text-muted small mb-2">
                <a href="{{ route('shop-categories.index') }}" class="text-decoration-none text-muted">Shop Categories</a>
                <span class="mx-1">/</span>
                <span>Edit</span>
            </div>
            <h1 class="h3 mb-1">Edit Product Category</h1>
            <p class="text-muted mb-0">Update this category and its existing subcategories.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                Please review the highlighted fields and correct the errors below.
            </div>
        @endif

        <form action="{{ route('shop-categories.update', $shopCategory) }}" method="POST" id="categoryForm">
            @csrf
            @method('PUT')

            <section class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">Category Information</h2>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $shopCategory->name) }}"
                               maxlength="255"
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
                               value="{{ old('slug', $shopCategory->slug) }}"
                               maxlength="255"
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
                                  maxlength="2000">{{ old('description', $shopCategory->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="active" {{ old('status', $shopCategory->active ? 'active' : 'inactive') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $shopCategory->active ? 'active' : 'inactive') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <div class="form-text">Inactive categories and their subcategories are hidden from public shop browsing.</div>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="card mb-4">
                <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                    <div>
                        <h2 class="h5 mb-0">Subcategories</h2>
                        <p class="small text-muted mb-0 mt-1">Existing subcategories are retained when this form is saved.</p>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addSubcategory">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Another Subcategory
                    </button>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div id="subcategoriesContainer" aria-live="polite">
                        @foreach($subcategoryRows as $index => $subcategory)
                            <div class="subcategory-row border rounded p-3 mb-3" data-index="{{ $index }}">
                                @if(!empty($subcategory['id']))
                                    <input type="hidden" name="subcategories[{{ $index }}][id]" value="{{ $subcategory['id'] }}">
                                @endif
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h3 class="h6 mb-0">Subcategory</h3>
                                    @if(empty($subcategory['id']))
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-subcategory">Remove</button>
                                    @endif
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

            <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2">
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

    function syncSlug(input, isSubcategory) {
        const holder = input.closest(isSubcategory ? '.subcategory-row' : 'form');
        const slug = holder.querySelector(isSubcategory ? '.subcategory-slug' : '#slug');
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

    slugInput.dataset.manuallyEdited = toSlug(nameInput.value) === slugInput.value ? '' : 'true';
    container.querySelectorAll('.subcategory-row').forEach(function (row) {
        const name = row.querySelector('.subcategory-name');
        const slug = row.querySelector('.subcategory-slug');
        slug.dataset.manuallyEdited = toSlug(name.value) === slug.value ? '' : 'true';
    });
    updateEmptyMessage();
});
</script>

@endsection
