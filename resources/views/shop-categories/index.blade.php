@extends('front.layouts.admin')

@push('styles')
<style>
    .shop-categories-list {
        color: #475569;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .shop-categories-list .shop-categories-heading {
        align-items: center;
        gap: 16px;
        margin-bottom: 20px !important;
    }

    .shop-categories-list .shop-categories-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .shop-categories-list .shop-categories-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        box-shadow: none;
    }

    .shop-categories-list .shop-categories-card-body {
        padding: 0;
    }

    .shop-categories-list .shop-categories-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .shop-categories-list .shop-categories-table {
        width: 100%;
        min-width: 900px;
        margin-bottom: 0;
        table-layout: fixed;
        color: #475569;
        font-size: .875rem;
    }

    .shop-categories-list .shop-categories-table th:nth-child(1),
    .shop-categories-list .shop-categories-table td:nth-child(1) {
        width: 64px;
    }

    .shop-categories-list .shop-categories-table th:nth-child(2),
    .shop-categories-list .shop-categories-table td:nth-child(2) {
        width: 25%;
    }

    .shop-categories-list .shop-categories-table th:nth-child(3),
    .shop-categories-list .shop-categories-table td:nth-child(3) {
        width: 38%;
    }

    .shop-categories-list .shop-categories-table th:nth-child(4),
    .shop-categories-list .shop-categories-table td:nth-child(4) {
        width: 15%;
    }

    .shop-categories-list .shop-categories-table th:nth-child(5),
    .shop-categories-list .shop-categories-table td:nth-child(5) {
        width: 170px;
    }

    .shop-categories-list .shop-categories-table thead th {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1.4;
        white-space: nowrap;
        vertical-align: middle;
    }

    .shop-categories-list .shop-categories-table tbody td {
        padding: 13px 16px;
        border-bottom: 1px solid #edf1f5;
        vertical-align: middle;
    }

    .shop-categories-list .shop-categories-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .shop-categories-list .shop-categories-table tbody tr {
        transition: background-color .15s ease;
    }

    .shop-categories-list .shop-categories-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .shop-categories-list .shop-category-number {
        width: 56px;
        color: #64748b;
        font-variant-numeric: tabular-nums;
    }

    .shop-categories-list .shop-category-name {
        min-width: 200px;
        color: #0f172a;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .shop-categories-list .shop-category-subcategories {
        line-height: 1.5;
    }

    .shop-categories-list .shop-category-count {
        margin-right: 6px;
        color: #0369a1;
        font-size: .8125rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .shop-categories-list .shop-category-subcategory-names {
        display: block;
        margin-top: 3px;
        color: #64748b;
        font-size: .8125rem;
    }

    .shop-categories-list .shop-category-empty {
        color: #64748b;
    }

    .shop-categories-list .shop-category-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: .8125rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .shop-categories-list .shop-category-status::before {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
        content: "";
    }

    .shop-categories-list .shop-category-status-active {
        color: #15803d;
    }

    .shop-categories-list .shop-category-status-inactive {
        color: #64748b;
    }

    .shop-categories-list .shop-category-actions {
        white-space: nowrap;
    }

    .shop-categories-list .shop-category-actions .btn {
        min-height: 32px;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1.25;
    }

    .shop-categories-list .shop-category-edit {
        border-color: #bfdbfe;
        background: #fff;
        color: #1d4ed8;
    }

    .shop-categories-list .shop-category-edit:hover {
        background: #eff6ff;
    }

    .shop-categories-list .shop-category-delete {
        border-color: #fecaca;
        background: #fff;
        color: #b91c1c;
    }

    .shop-categories-list .shop-category-delete:hover {
        background: #fef2f2;
        color: #991b1b;
    }

    .shop-categories-list .shop-category-actions .btn:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .2);
        outline-offset: 2px;
    }

    .shop-categories-list .shop-categories-pagination {
        padding: 16px 20px;
        border-top: 1px solid #edf1f5;
    }

    .shop-categories-list .alert-success {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }

    .shop-categories-list .shop-categories-empty {
        padding: 48px 20px;
        color: #64748b;
    }

    @media (max-width: 767.98px) {
        .shop-categories-list .shop-categories-heading {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .shop-categories-list .shop-categories-title {
            font-size: 1.25rem;
        }

        .shop-categories-list .shop-categories-table thead th,
        .shop-categories-list .shop-categories-table tbody td {
            padding-right: 12px;
            padding-left: 12px;
        }
    }
</style>
@endpush

@section('content')

<div class="shop-categories-list">
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="shop-categories-title">Shop Categories</h1>
        </div>
        <div class="shop-categories-heading col-md-4 d-flex justify-content-end">
            <a href="{{ route('shop-categories.create') }}" class="btn btn-primary">
                New Category
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shop-categories-card">
        <div class="card-body shop-categories-card-body">
            @if($categories->count() > 0)
                <div class="table-responsive shop-categories-responsive">
                    <table class="table table-hover align-middle mb-0 shop-categories-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Parent Category</th>
                                <th>Subcategories</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                                <tr>
                                    <td class="shop-category-number">{{ $loop->iteration }}</td>
                                    <td class="shop-category-name">{{ $category->name }}</td>
                                    <td class="shop-category-subcategories">
                                        @if($category->subcategories && $category->subcategories->count() > 0)
                                            <span class="shop-category-count">{{ $category->subcategories->count() }}</span>
                                            <small class="shop-category-subcategory-names">
                                                {{ implode(', ', $category->subcategories->pluck('name')->take(2)->toArray()) }}
                                                @if($category->subcategories->count() > 2)
                                                    ...
                                                @endif
                                            </small>
                                        @else
                                            <span class="shop-category-empty">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($category->active)
                                            <span class="shop-category-status shop-category-status-active">Active</span>
                                        @else
                                            <span class="shop-category-status shop-category-status-inactive">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="shop-category-actions">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Actions for {{ $category->name }}">
                                            <a href="{{ route('shop-categories.edit', $category) }}" class="btn shop-category-edit">
                                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>
                                                Edit
                                            </a>
                                            <form action="{{ route('shop-categories.destroy', $category) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn shop-category-delete" onclick="return confirm('Sure?')">
                                                    <i class="bi bi-trash me-1" aria-hidden="true"></i>
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

                <div class="shop-categories-pagination d-flex justify-content-center">
                    {{ $categories->links() }}
                </div>
            @else
                <div class="shop-categories-empty text-center">
                    <p class="text-muted">No categories found.</p>
                    <a href="{{ route('shop-categories.create') }}" class="btn btn-primary btn-sm">
                        Create First Category
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
</div>

@endsection