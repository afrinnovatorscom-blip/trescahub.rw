@extends('front.layouts.admin')

@section('title', 'Edit Category')

@section('content')
@php
    $classificationValues = old('classification_name', $category->classification_name ?? []);
    $subcategoryValues = old('subcategory', $category->subcategory ?? []);
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Category
        <small class="text-muted fw-normal fs-6 ms-2">{{ $category->title }}</small>
    </h4>
    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card">
    <div class="card-body p-4">
        <form action="{{ route('categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">

                {{-- Title --}}
                <div class="col-md-6">
                    <label for="title" class="form-label fw-semibold">
                        Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="title" name="title"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $category->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Slug --}}
                <div class="col-md-6">
                    <label for="slug" class="form-label fw-semibold">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                        <input type="text" id="slug" name="slug"
                               class="form-control @error('slug') is-invalid @enderror"
                               value="{{ old('slug', $category->slug) }}">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-text">Clear to auto-regenerate from title on save.</div>
                </div>

                {{-- Classification Name → plain text input, stored as array --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Classification <span class="text-danger">*</span>
                        <small class="text-muted fw-normal">(type &amp; press Add)</small>
                    </label>
                    <div class="input-group mb-2">
                        <input type="text" id="classification-input"
                               class="form-control @error('classification_name') is-invalid @enderror"
                               placeholder="e.g. Electronics">
                        <button type="button" class="btn btn-outline-primary" id="add-classification">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                    <div id="classification-tags" class="d-flex flex-wrap gap-2 mb-1"></div>
                    <div id="classification-inputs"></div>
                    @error('classification_name')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('classification_name.*')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Subcategories → plain text input, stored as array --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Subcategories <small class="text-muted fw-normal">(type &amp; press Add)</small>
                    </label>
                    <div class="input-group mb-2">
                        <input type="text" id="subcategory-input" class="form-control"
                               placeholder="e.g. Mobile Phones">
                        <button type="button" class="btn btn-outline-primary" id="add-subcategory">
                            <i class="bi bi-plus-lg"></i> Add
                        </button>
                    </div>
                    <div id="subcategory-tags" class="d-flex flex-wrap gap-2 mb-1"></div>
                    <div id="subcategory-inputs"></div>
                    @error('subcategory')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('subcategory.*')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Update Category
                </button>
                <a href="{{ route('categories.show', $category) }}" class="btn btn-outline-secondary px-4">Cancel</a>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const titleInput = document.getElementById('title');
const slugInput = document.getElementById('slug');
titleInput.addEventListener('input', () => {
    if (slugInput.value === '') {
        slugInput.value = titleInput.value.toLowerCase().trim()
            .replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/^-+|-+$/g, '');
    }
});

function makeTagManager({ inputId, addBtnId, tagsId, hiddenId, inputName, initialItems }) {
    const inputEl = document.getElementById(inputId);
    const addBtn = document.getElementById(addBtnId);
    const tagsDiv = document.getElementById(tagsId);
    const hiddenDiv = document.getElementById(hiddenId);
    const items = initialItems.filter(value => typeof value === 'string' && value.trim() !== '');

    function render() {
        tagsDiv.innerHTML = '';
        hiddenDiv.innerHTML = '';
        items.forEach((value, index) => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary d-flex align-items-center gap-1 px-2 py-2';
            badge.textContent = value;
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn-close btn-close-white ms-1';
            removeButton.style.fontSize = '.6rem';
            removeButton.setAttribute('aria-label', `Remove ${value}`);
            removeButton.addEventListener('click', () => {
                items.splice(index, 1);
                render();
            });
            badge.appendChild(removeButton);
            tagsDiv.appendChild(badge);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = inputName;
            hiddenInput.value = value;
            hiddenDiv.appendChild(hiddenInput);
        });
    }

    function addItem() {
        const value = inputEl.value.trim();
        if (!value || items.includes(value)) return;
        items.push(value);
        inputEl.value = '';
        render();
        inputEl.focus();
    }

    addBtn.addEventListener('click', addItem);
    inputEl.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addItem();
        }
    });
    render();
}

makeTagManager({
    inputId: 'classification-input',
    addBtnId: 'add-classification',
    tagsId: 'classification-tags',
    hiddenId: 'classification-inputs',
    inputName: 'classification_name[]',
    initialItems: @json($classificationValues),
});
makeTagManager({
    inputId: 'subcategory-input',
    addBtnId: 'add-subcategory',
    tagsId: 'subcategory-tags',
    hiddenId: 'subcategory-inputs',
    inputName: 'subcategory[]',
    initialItems: @json($subcategoryValues),
});
</script>
@endpush