@extends('front.layouts.admin')
@section('content')

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3">Shop Products</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.shop-products.create') }}" class="btn btn-primary">
                + New Product
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($products->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Thumbnail</th>
                                <th>Product Name</th>
                                <th>Popular</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Price (RWF)</th>
                                <th>Working Days</th>
                                <th>Approval</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    
                                    {{-- Thumbnail --}}
                                    <td>
                                        @if($product->thumbnail)
                                            <img src="{{ asset('public/assets/' . $product->thumbnail) }}"
                                                 alt="{{ $product->product_name }}"
                                                 class="rounded"
                                                 style="width:50px;height:50px;object-fit:cover;">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    
                                    {{-- Product Name --}}
                                    <td><strong>{{ $product->product_name }}</strong></td>
                                    
                                    {{-- Popular Badge --}}
                                    <td>
                                        @if($product->is_popular)
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-star-fill"></i> Popular
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted">Regular</span>
                                        @endif
                                    </td>
                                    
                                    {{-- Category --}}
                                    <td>{{ $product->category?->name ?? '—' }}</td>
                                    
                                    {{-- Subcategory --}}
                                    <td>{{ $product->subcategory?->name ?? '—' }}</td>
                                    
                                    {{-- Price --}}
                                    <td>{{ number_format($product->price, 0, '.', ',') }}</td>
                                    
                                    {{-- Working Days --}}
                                    <td>{{ $product->working_days }} day(s)</td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $product->approval_status ?? 'pending') }}</span>
                                        <div class="small text-muted">{{ $product->publication_status ?? 'unpublished' }}</div>
                                        @if(auth()->user()->hasPermission('vendors.view') && $product->vendor)
                                            <div class="small">{{ $product->vendor->business_name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->status === 'in_stock')
                                            <span class="badge bg-success">In Stock</span>
                                        @else
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @endif
                                    </td>
                                    
                                    {{-- Actions --}}
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.shop-products.edit', $product) }}" class="btn btn-warning">Edit</a>
                                            <form action="{{ route('admin.shop-products.destroy', $product) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger"
                                                        onclick="return confirm('Delete this product?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <p class="text-muted">No products found.</p>
                    <a href="{{ route('admin.shop-products.create') }}" class="btn btn-primary btn-sm">
                        Create First Product
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection