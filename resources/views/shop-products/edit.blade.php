@extends('front.layouts.admin')
@section('content')

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="h3 mb-4">Edit Shop Product</h1>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.shop-products.update', $shopProduct) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Product Name --}}
                        <div class="mb-3">
                            <label for="product_name" class="form-label fw-bold">Product Name *</label>
                            <input type="text"
                                   class="form-control @error('product_name') is-invalid @enderror"
                                   id="product_name" name="product_name"
                                   value="{{ old('product_name', $shopProduct->product_name) }}"
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
                                    <option value="in_stock"     {{ old('status', $shopProduct->status) === 'in_stock'     ? 'selected' : '' }}>In Stock</option>
                                    <option value="out_of_stock" {{ old('status', $shopProduct->status) === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
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
                                           value="{{ old('price', $shopProduct->price) }}"
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
                                       value="{{ old('working_days', $shopProduct->working_days) }}"
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
                                       {{ old('is_popular', $shopProduct->is_popular) ? 'checked' : '' }}>
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
                                      rows="3">{{ old('description', $shopProduct->description) }}</textarea>
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
                                                {{ old('shop_category_id', $shopProduct->shop_category_id) == $cat->id ? 'selected' : '' }}>
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
                            <label class="form-label fw-bold">Features</label>
                            <div id="features-container">
                                @forelse(old('features', $shopProduct->features ?? []) as $feature)
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control"
                                               name="features[]"
                                               value="{{ $feature }}"
                                               placeholder="e.g., Fast Charging">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @empty
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="features[]" placeholder="e.g., Fast Charging">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFeature()">
                                + Add Feature
                            </button>
                        </div>

                        {{-- Specifications --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Specifications</label>
                            <div id="specs-container">
                                @php $specs = old('specifications', $shopProduct->specifications ?? []); @endphp
                                @forelse($specs as $i => $spec)
                                    <div class="spec-row d-flex gap-2 mb-2">
                                        <input type="text" class="form-control"
                                               name="specifications[{{ $i }}][label]"
                                               placeholder="Label (e.g., RAM)"
                                               value="{{ $spec['label'] ?? '' }}">
                                        <input type="text" class="form-control"
                                               name="specifications[{{ $i }}][value]"
                                               placeholder="Value (e.g., 8GB)"
                                               value="{{ $spec['value'] ?? '' }}">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @empty
                                    <div class="spec-row d-flex gap-2 mb-2">
                                        <input type="text" class="form-control" name="specifications[0][label]" placeholder="Label (e.g., RAM)">
                                        <input type="text" class="form-control" name="specifications[0][value]" placeholder="Value (e.g., 8GB)">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSpec()">
                                + Add Specification
                            </button>
                        </div>

                        {{-- Thumbnail --}}
                        <div class="mb-3">
                            <label for="thumbnail" class="form-label fw-bold">Thumbnail Image</label>
                            @if($shopProduct->thumbnail)
                                <div class="mb-2">
                                    <img src="{{ asset('public/assets/' . $shopProduct->thumbnail) }}"
                                         class="rounded"
                                         style="width:100px;height:100px;object-fit:cover;"
                                         alt="Current Thumbnail">
                                    <small class="text-muted d-block mt-1">Current thumbnail — upload new to replace</small>
                                </div>
                            @endif
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
                            @if(!empty($shopProduct->images))
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach($shopProduct->images as $img)
                                        <img src="{{ asset('public/assets/' . $img) }}"
                                             class="rounded"
                                             style="width:80px;height:80px;object-fit:cover;"
                                             alt="Product Image">
                                    @endforeach
                                </div>
                                <small class="text-muted d-block mb-2">Upload new images to replace all existing ones</small>
                            @endif
                            <input type="file"
                                   class="form-control @error('images') is-invalid @enderror"
                                   id="images" name="images[]"
                                   accept="image/*" multiple
                                   onchange="previewImages(this)">
                            <div id="images-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                            @error('images')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Timestamps --}}
                        <div class="alert alert-light mb-4">
                            <small class="text-muted">
                                Created: {{ $shopProduct->created_at->format('M d, Y') }} |
                                Updated: {{ $shopProduct->updated_at->format('M d, Y') }}
                            </small>
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex gap-2">
                        @include('shop-products.workflow-fields', ['product' => $shopProduct])

                            <button type="submit" class="btn btn-primary">Update Product</button>
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
    const oldSubcat         = "{{ old('subcategory_id', $shopProduct->subcategory_id) }}";

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
    let specIndex = {{ count($shopProduct->specifications ?? []) ?: 1 }};

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

    function removeRow(btn) {
        btn.closest('.input-group, .spec-row').remove();
    }

    // ── Image Previews ─────────────────────────────────────────────────────
    function previewThumbnail(input) {
        const preview = document.getElementById('thumbnail-preview');
        preview.innerHTML = '';
        if (input.files && input.files[0]) {
            const img     = document.createElement('img');
            img.src       = URL.createObjectURL(input.files[0]);
            img.className = 'rounded';
            img.style     = 'width:100px;height:100px;object-fit:cover;';
            preview.appendChild(img);
        }
    }

    function previewImages(input) {
        const preview = document.getElementById('images-preview');
        preview.innerHTML = '';
        Array.from(input.files).forEach(file => {
            const img     = document.createElement('img');
            img.src       = URL.createObjectURL(file);
            img.className = 'rounded';
            img.style     = 'width:80px;height:80px;object-fit:cover;';
            preview.appendChild(img);
        });
    }
</script>

@endsection