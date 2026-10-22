@extends('front.layouts.admin')
@section('page-title', 'My products')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <div class="d-flex justify-content-between"><h1 class="h3">My products</h1><a class="btn btn-primary" href="{{ route('vendor.products.create') }}">Submit product</a></div>
    <table class="table"><thead><tr><th>Name</th><th>Approval</th><th>Publication</th><th></th></tr></thead>
        <tbody>@forelse($products as $product)<tr>
            <td>{{ $product->product_name }}</td>
            <td>{{ str_replace('_',' ',$product->approval_status) }}@if($product->revision_status)<div class="small">Changes pending review</div>@endif</td>
            <td>{{ $product->publication_status }}</td>
            <td><a href="{{ route('vendor.products.edit', $product->slug) }}">Edit</a></td>
        </tr>@empty<tr><td colspan="4" class="text-muted">You have not submitted a product yet.</td></tr>@endforelse</tbody></table>
    {{ $products->links() }}
</div>
@endsection
