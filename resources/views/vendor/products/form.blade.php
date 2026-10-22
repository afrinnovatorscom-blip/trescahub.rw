@extends('front.layouts.admin')
@section('page-title', 'Product')
@section('content')
<div class="container py-4" style="max-width: 860px;">
    @include('marketplace.partials.flash')
    <h1 class="h3 mb-3">{{ $product->exists ? 'Edit product' : 'Submit a product' }}</h1>
    @if($product->exists)
        <div class="alert alert-light">Status: {{ str_replace('_',' ',$product->approval_status) }}. Publication: {{ $product->publication_status }}. You cannot approve or publish this yourself.</div>
    @endif
    <form method="POST" action="{{ $product->exists ? route('vendor.products.update', $product->slug) : route('vendor.products.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        @if($product->exists) @method('PUT') @endif
        <div class="col-12"><label class="form-label">Name</label><input name="product_name" class="form-control" required value="{{ old('product_name', $product->product_name) }}"></div>
        <div class="col-md-4"><label class="form-label">Price (RWF)</label><input name="price" type="number" min="0" class="form-control" required value="{{ old('price', $product->price) }}"></div>
        <div class="col-md-4"><label class="form-label">Working days</label><input name="working_days" type="number" min="1" class="form-control" required value="{{ old('working_days', $product->working_days ?: 1) }}"></div>
        <div class="col-md-4"><label class="form-label">Availability</label><select name="status" class="form-select"><option value="in_stock">In stock</option><option value="out_of_stock" @selected(old('status', $product->status)==='out_of_stock')>Out of stock</option></select></div>
        <div class="col-md-6"><label class="form-label">Category</label><select name="shop_category_id" class="form-select" required>@foreach($parentCategories as $category)<option value="{{ $category->id }}" @selected(old('shop_category_id', $product->shop_category_id)==$category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control">{{ old('description', $product->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Thumbnail</label><input type="file" name="thumbnail" class="form-control" accept="image/*"></div>
        <div class="col-md-6"><label class="form-label">Gallery</label><input type="file" name="images[]" class="form-control" accept="image/*" multiple></div>
        <div class="col-12 form-check"><input class="form-check-input" type="checkbox" name="submit_for_review" value="1" id="submit_for_review" checked><label class="form-check-label" for="submit_for_review">Submit for review</label></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Save</button></div>
    </form>
</div>
@endsection
