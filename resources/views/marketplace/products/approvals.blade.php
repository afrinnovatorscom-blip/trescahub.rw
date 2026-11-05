@extends('front.layouts.admin')
@section('page-title', 'Approvals')
@section('content')
@php
    $imageUrl = fn ($path) => asset('assets/'.preg_replace('#^(public/)?assets/#', '', ltrim((string) $path, '/')));
@endphp
<style>
    .approval-queue { color: #0f172a; }
    .approval-table-wrap { overflow: hidden; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    .approval-table { margin-bottom: 0; font-size: .86rem; }
    .approval-table thead th { padding: 11px 14px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: .75rem; font-weight: 600; white-space: nowrap; }
    .approval-table tbody td { padding: 12px 14px; border-color: #eef2f7; color: #475569; vertical-align: middle; }
    .approval-table tbody tr:hover { background: #f8fafc; }
    .approval-product { display: flex; min-width: 220px; align-items: center; gap: 10px; }
    .approval-product-image { position: absolute; inset: 0; width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
    .approval-product-thumbnail { position: relative; display: inline-flex; width: 42px; height: 42px; flex: 0 0 42px; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 8px; background: #f1f5f9; color: #64748b; font-size: .85rem; font-weight: 600; }
    .approval-product-initial { position: relative; z-index: 0; }
    .approval-product-link { color: #1d4ed8; font-weight: 600; text-decoration: none; }
    .approval-product-link:hover { color: #1e40af; text-decoration: underline; }
    .approval-review-button { border-radius: 6px; background: #2563eb; border-color: #2563eb; font-weight: 600; }
    .approval-review-button:hover { background: #1d4ed8; border-color: #1d4ed8; }
</style>
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <div class="approval-queue">
        <h1 class="h3 mb-3">Product approval queue</h1>
        <div class="approval-table-wrap table-responsive">
            <table class="table table-hover align-middle approval-table">
                <thead class="table-light"><tr><th>Product</th><th>Category</th><th>Price</th><th>Initial Stock</th>@if(auth()->user()->hasPermission('vendors.view'))<th>Vendor</th>@endif<th>Submitted</th><th></th></tr></thead>
                <tbody>
                @forelse($products as $product)
                    @php
                        $rawThumbnail = $product->pending_payload['thumbnail'] ?? $product->thumbnail ?? '';
                        $thumbnail = preg_replace('#^(public/)?assets/#', '', ltrim((string) $rawThumbnail, '/'));
                        $initial = mb_strtoupper(mb_substr(trim((string) $product->product_name), 0, 1));
                    @endphp
                    <tr>
                        <td>
                            <div class="approval-product">
                                <span class="approval-product-thumbnail" aria-hidden="true">
                                    <span class="approval-product-initial">{{ $initial !== '' ? $initial : 'P' }}</span>
                                    @if($thumbnail)<img class="approval-product-image" src="{{ $imageUrl($thumbnail) }}" alt="" onerror="this.style.display='none'">@endif
                                </span>
                                <span>
                                    <a class="approval-product-link" href="{{ route('admin.shop-products.show', $product) }}">{{ $product->product_name }}</a>
                                    <span class="d-block small text-muted">{{ $product->description }}</span>
                                </span>
                            </div>
                        </td>
                        <td>{{ $product->category->name ?? '—' }}</td>
                        <td>{{ number_format($product->price, 0, '.', ',') }}</td>
                        <td>
                            @if($product->tracks_inventory && array_key_exists('initial_stock_quantity', $product->pending_payload ?? []))
                                {{ number_format((int) $product->pending_payload['initial_stock_quantity']) }} {{ $product->stock_unit ?: 'units' }}
                            @else
                                —
                            @endif
                        </td>
                        @if(auth()->user()->hasPermission('vendors.view'))<td>{{ $product->vendor?->business_name ?? '—' }}</td>@endif
                        <td>{{ $product->submitter->name ?? '—' }}<div class="small">{{ $product->submitted_at }}</div></td>
                        <td><a class="btn btn-sm btn-primary approval-review-button" href="{{ route('admin.shop-products.show', $product) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->hasPermission('vendors.view') ? 7 : 6 }}" class="text-muted py-4">Nothing is waiting for review.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $products->links() }}
</div>
@endsection
