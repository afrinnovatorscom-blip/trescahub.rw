@extends('front.layouts.admin')
@section('content')

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="h3 mb-4">Create Shop Product</h1>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.shop-products.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- Product Name --}}
                        <div class="mb-3">
                            <label for="product_name" class="form-label fw-bold">Product Name *</label>
                            <input type="text"
                                   class="form-control @error('product_name') is-invalid @enderror"
                                   id="product_name" name="product_name"
                                   value="{{ old('product_name') }}"
                                   placeholder="e.g., Dell Laptop XPS 15"
                                   required>
                            @error('product_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            {{-- Status --}}
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label fw-bold">Status *</label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                        id="status" name="status" required>
                                    <option value="in_stock"    {{ old('status', 'in_stock') === 'in_stock'    ? 'selected' : '' }}>In Stock</option>
                                    <option value="out_of_stock" {{ old('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Price --}}
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label fw-bold">Price (RWF) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">RWF</span>
                                    <input type="number"
                                           class="form-control @error('price') is-invalid @enderror"
                                           id="price" name="price"
                                           value="{{ old('price') }}"
                                           placeholder="e.g., 500000"
                                           min="0" step="1" required>
                                </div>
                                @error('price')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Working Days --}}
                            <div class="col-md-4 mb-3">
                                <label for="working_days" class="form-label fw-bold">Working Days *</label>
                                <input type="number"
                                       class="form-control @error('working_days') is-invalid @enderror"
                                       id="working_days" name="working_days"
                                       value="{{ old('working_days', 1) }}"
                                       min="1" required>
                                @error('working_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Popular Product Checkbox --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="is_popular" 
                                       name="is_popular"
                                       value="1"
                                       {{ old('is_popular') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_popular">
                                    <i class="bi bi-star-fill" style="color: #ffc107;"></i>
                                    Mark as Popular Product
                                </label>
                                <small class="d-block text-muted mt-1">
                                    ✅ When checked: Shows in BOTH popular section AND shop page  
                                    ❌ When unchecked: Shows ONLY in shop page
                                </small>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description"
                                      rows="3"
                                      placeholder="Product description...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            {{-- Parent Category --}}
                            <div class="col-md-6 mb-3">
                                <label for="shop_category_id" class="form-label fw-bold">Parent Category *</label>
                                <select class="form-select @error('shop_category_id') is-invalid @enderror"
                                        id="shop_category_id" name="shop_category_id" required>
                                    <option value="">— Select Parent Category —</option>
                                    @foreach($parentCategories as $cat)
                                        <option value="{{ $cat->id }}"
                                                data-subcategories="{{ $cat->subcategories->toJson() }}"
                                                {{ old('shop_category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('shop_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Subcategory --}}
                            <div class="col-md-6 mb-3">
                                <label for="subcategory_id" class="form-label fw-bold">Subcategory</label>
                                <select class="form-select @error('subcategory_id') is-invalid @enderror"
                                        id="subcategory_id" name="subcategory_id">
                                    <option value="">— Select Subcategory —</option>
                                </select>
                                @error('subcategory_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Features --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Features
                                <span class="text-muted fw-normal">(e.g., Waterproof, Fast Charging)</span>
                            </label>
                            <div id="features-container">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control"
                                           name="features[]"
                                           placeholder="e.g., Fast Charging"
                                           value="{{ old('features.0') }}">
                                    <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFeature()">
                                + Add Feature
                            </button>
                        </div>

                        {{-- Specifications --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Specifications
                                <span class="text-muted fw-normal">(e.g., RAM → 8GB, Color → Black)</span>
                            </label>
                            <div id="specs-container">
                                <div class="spec-row d-flex gap-2 mb-2">
                                    <input type="text" class="form-control"
                                           name="specifications[0][label]"
                                           placeholder="Label (e.g., RAM)"
                                           value="{{ old('specifications.0.label') }}">
                                    <input type="text" class="form-control"
                                           name="specifications[0][value]"
                                           placeholder="Value (e.g., 8GB)"
                                           value="{{ old('specifications.0.value') }}">
                                    <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSpec()">
                                + Add Specification
                            </button>
                        </div>

                        {{-- Thumbnail --}}
                        <div class="mb-3">
                            <label for="thumbnail" class="form-label fw-bold">Thumbnail Image</label>
                            <input type="file"
                                   class="form-control @error('thumbnail') is-invalid @enderror"
                                   id="thumbnail" name="thumbnail"
                                   accept="image/*"
                                   onchange="previewThumbnail(this)">
                            <div id="thumbnail-preview" class="mt-2"></div>
                            @error('thumbnail')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Other Images --}}
                        <div class="mb-4">
                            <label for="images" class="form-label fw-bold">Other Images</label>
                            <input type="file"
                                   class="form-control @error('images') is-invalid @enderror"
                                   id="images" name="images[]"
                                   accept="image/*" multiple
                                   onchange="previewImages(this)">
                            <small class="text-muted">You can select multiple images</small>
                            <div id="images-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            @error('images')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex gap-2">
                        @include('shop-products.workflow-fields')

                            <button type="submit" class="btn btn-primary">Create Product</button>
                            <a href="{{ route('admin.shop-products.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ── Category → Subcategory ─────────────────────────────────────────────
    const categorySelect    = document.getElementById('shop_category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const oldSubcat         = "{{ old('subcategory_id') }}";

    function loadSubcategories() {
        const selected = categorySelect.options[categorySelect.selectedIndex];
        subcategorySelect.innerHTML = '<option value="">— Select Subcategory —</option>';

        if (!selected || !selected.dataset.subcategories) return;

        const subs = JSON.parse(selected.dataset.subcategories);
        subs.forEach(sub => {
            const opt       = document.createElement('option');
            opt.value       = sub.id;
            opt.textContent = sub.name;
            if (oldSubcat == sub.id) opt.selected = true;
            subcategorySelect.appendChild(opt);
        });
    }

    categorySelect.addEventListener('change', loadSubcategories);
    loadSubcategories();

    // ── Features ──────────────────────────────────────────────────────────
    function addFeature() {
        const container = document.getElementById('features-container');
        const group     = document.createElement('div');
        group.className = 'input-group mb-2';
        group.innerHTML = `
            <input type="text" class="form-control" name="features[]" placeholder="e.g., Fast Charging">
            <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                <i class="bi bi-trash"></i>
            </button>`;
        container.appendChild(group);
        group.querySelector('input').focus();
    }

    // ── Specifications ─────────────────────────────────────────────────────
    let specIndex = 1;
    function addSpec() {
        const container = document.getElementById('specs-container');
        const row       = document.createElement('div');
        row.className   = 'spec-row d-flex gap-2 mb-2';
        row.innerHTML   = `
            <input type="text" class="form-control" name="specifications[${specIndex}][label]" placeholder="Label (e.g., RAM)">
            <input type="text" class="form-control" name="specifications[${specIndex}][value]" placeholder="Value (e.g., 8GB)">
            <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                <i class="bi bi-trash"></i>
            </button>`;
        container.appendChild(row);
        row.querySelector('input').focus();
        specIndex++;
    }

    // ── Remove row ─────────────────────────────────────────────────────────
    function removeRow(btn) {
        btn.closest('.input-group, .spec-row').remove();
    }

    // ── Image Previews ─────────────────────────────────────────────────────
    function previewThumbnail(input) {
        const preview = document.getElementById('thumbnail-preview');
        preview.innerHTML = '';
        if (input.files && input.files[0]) {
            const img    = document.createElement('img');
            img.src      = URL.createObjectURL(input.files[0]);
            img.className = 'rounded';
            img.style    = 'width:100px;height:100px;object-fit:cover;';
            preview.appendChild(img);
        }
    }

    function previewImages(input) {
        const preview = document.getElementById('images-preview');
        preview.innerHTML = '';
        Array.from(input.files).forEach(file => {
            const img    = document.createElement('img');
            img.src      = URL.createObjectURL(file);
            img.className = 'rounded';
            img.style    = 'width:80px;height:80px;object-fit:cover;';
            preview.appendChild(img);
        });
    }
</script>

@endsection