@extends('front.layouts.admin')
@section('page-title', 'My Products')

@push('styles')
<style>
    .vendor-products-page { max-width:1440px; margin:0 auto; }
    .vendor-products-header { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; }
    .vendor-products-title { margin:0 0 4px; color:#0f172a; font-size:26px; line-height:1.2; font-weight:700; }
    .vendor-products-description { margin:0; color:#64748b; font-size:14px; }
    .vendor-products-submit { height:42px; display:inline-flex; align-items:center; border-radius:8px; font-size:14px; font-weight:600; white-space:nowrap; }
    .vendor-products-toolbar { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #e2e8f0; border-radius:10px 10px 0 0; padding:12px 16px; }
    .vendor-products-search { flex:1 1 auto; min-width:0; height:40px; }
    .vendor-products-select { flex:0 1 190px; width:190px; min-width:150px; height:40px; }
    .vendor-products-table-card { overflow:hidden; background:#fff; border:1px solid #e2e8f0; border-top:0; border-radius:0 0 10px 10px; }
    .vendor-products-table { width:100%; table-layout:fixed; margin:0; font-size:13px; }
    .vendor-products-table thead th { height:42px; padding:8px 12px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:12px; font-weight:600; vertical-align:middle; }
    .vendor-products-table tbody td { height:64px; padding:10px 12px; border-bottom:1px solid #edf2f7; color:#334155; vertical-align:middle; }
    .vendor-products-table tbody tr:last-child td { border-bottom:0; }
    .vendor-products-table tbody tr:hover { background:#f8fafc; }
    .vendor-product-col{width:38%}.vendor-price-col{width:16%}.vendor-approval-col{width:18%}.vendor-publication-col{width:18%}.vendor-action-col{width:10%}
    .vendor-product-identity { display:flex; align-items:center; gap:10px; min-width:0; }
    .vendor-product-thumbnail { width:40px; height:40px; flex:0 0 40px; position:relative; border-radius:7px; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; background:#e2e8f0; color:#475569; font-size:14px; font-weight:700; }
    .vendor-product-thumbnail img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .vendor-product-name { min-width:0; color:#0f172a; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .vendor-status-badge { display:inline-flex; align-items:center; border-radius:999px; padding:4px 9px; font-size:12px; font-weight:600; white-space:nowrap; }
    .vendor-status-approved,.vendor-status-published { color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; }
    .vendor-status-pending_approval,.vendor-status-unpublished { color:#b45309; background:#fef3c7; border:1px solid #fde68a; }
    .vendor-status-rejected { color:#b91c1c; background:#fee2e2; border:1px solid #fecaca; }
    .vendor-status-neutral { color:#475569; background:#f8fafc; border:1px solid #e2e8f0; }
    .vendor-product-edit { color:#2563eb; font-size:13px; font-weight:600; text-decoration:none; }
    .vendor-product-edit:hover { color:#1d4ed8; }
    .vendor-products-empty { height:120px !important; color:#64748b; text-align:center; }
    .vendor-products-pagination { padding:12px 16px; border-top:1px solid #e2e8f0; }
    @media(max-width:991.98px){.vendor-products-toolbar{flex-wrap:wrap}.vendor-products-search{flex-basis:100%}.vendor-products-select{flex:1 1 calc(50% - 4px);width:auto}}
    @media(max-width:575.98px){.vendor-products-header{align-items:flex-start}.vendor-products-submit{height:40px}.vendor-products-toolbar{flex-direction:column;align-items:stretch}.vendor-products-search,.vendor-products-select{width:100%;flex-basis:auto}.vendor-products-table-card{overflow:hidden}.vendor-products-table{table-layout:auto}.vendor-product-col{width:auto}.vendor-product-name{white-space:normal}.vendor-products-table thead th,.vendor-products-table tbody td{padding-left:7px;padding-right:7px}.vendor-products-table{font-size:12px}}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 vendor-products-page">
    @include('marketplace.partials.flash')

    <div class="vendor-products-header">
        <div><h1 class="vendor-products-title">My Products</h1><p class="vendor-products-description">Manage your submitted products and their approval and publication status.</p></div>
        <a class="btn btn-primary vendor-products-submit" href="{{ route('vendor.products.create') }}">Submit Product</a>
    </div>

    <form method="GET" action="{{ route('vendor.products.index') }}" class="vendor-products-toolbar">
        <label class="visually-hidden" for="product-search">Search Products</label>
        <input id="product-search" name="q" value="{{ request('q') }}" class="form-control vendor-products-search" placeholder="Search Products" autocomplete="off">
        <label class="visually-hidden" for="approval-status">Approval Status</label>
        <select id="approval-status" name="approval_status" class="form-select vendor-products-select" onchange="this.form.submit()">
            <option value="">All approvals</option>
            @foreach(['pending_approval' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                <option value="{{ $value }}" @selected(request('approval_status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="visually-hidden" for="publication-status">Publication Status</label>
        <select id="publication-status" name="publication_status" class="form-select vendor-products-select" onchange="this.form.submit()">
            <option value="">All publications</option>
            @foreach(['published' => 'Published', 'unpublished' => 'Unpublished'] as $value => $label)
                <option value="{{ $value }}" @selected(request('publication_status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="vendor-products-table-card">
        <table class="table vendor-products-table">
            <thead><tr><th class="vendor-product-col text-start">Product</th><th class="vendor-price-col text-start">Price</th><th class="vendor-approval-col text-center">Approval</th><th class="vendor-publication-col text-center">Publication</th><th class="vendor-action-col text-center">Actions</th></tr></thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $rawThumbnail = (string) ($product->thumbnail ?? $product->pending_payload['thumbnail'] ?? '');
                        $thumbnail = preg_replace('#^(public/)?assets/#', '', ltrim($rawThumbnail, '/'));
                        $initial = mb_strtoupper(mb_substr(trim((string) $product->product_name), 0, 1));
                        $approvalClass = match ($product->approval_status) {
                            'approved' => 'vendor-status-approved',
                            'pending_approval' => 'vendor-status-pending_approval',
                            'rejected' => 'vendor-status-rejected',
                            default => 'vendor-status-neutral',
                        };
                        $publicationClass = $product->publication_status === 'published' ? 'vendor-status-published' : 'vendor-status-neutral';
                    @endphp
                    <tr>
                        <td><div class="vendor-product-identity"><span class="vendor-product-thumbnail" aria-label="{{ $product->product_name }}"><span>{{ $initial !== '' ? $initial : 'P' }}</span>@if($thumbnail)<img src="{{ asset('assets/'.$thumbnail) }}" alt="{{ $product->product_name }}" onerror="this.style.display='none'">@endif</span><span class="vendor-product-name" title="{{ $product->product_name }}">{{ $product->product_name }}</span></div></td>
                        <td>RWF {{ number_format((float) $product->price, 0, '.', ',') }}</td>
                        <td class="text-center"><span class="vendor-status-badge {{ $approvalClass }}">{{ str_replace('_', ' ', ucfirst($product->approval_status)) }}</span></td>
                        <td class="text-center"><span class="vendor-status-badge {{ $publicationClass }}">{{ ucfirst($product->publication_status ?: 'unpublished') }}</span></td>
                        <td class="text-center"><a class="vendor-product-edit" href="{{ route('vendor.products.edit', $product->slug) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="vendor-products-empty">You have not submitted a product yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())<div class="vendor-products-pagination">{{ $products->links() }}</div>@endif
    </div>
</div>
@endsection
