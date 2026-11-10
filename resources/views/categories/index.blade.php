@extends('front.layouts.admin')

@section('title', 'All Categories')

@push('styles')
<style>
    .categories-listing {
        color: #475569;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .categories-listing .categories-list-heading {
        gap: 16px;
        margin-bottom: 20px !important;
    }

    .categories-listing .categories-list-title {
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .categories-listing .categories-table-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        box-shadow: none;
    }

    .categories-listing .categories-table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .categories-listing .categories-table {
        width: 100%;
        min-width: 1080px;
        margin-bottom: 0;
        table-layout: fixed;
        color: #475569;
        font-size: .875rem;
    }

    .categories-listing .categories-table thead th {
        padding: 12px 14px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1.4;
        white-space: nowrap;
        vertical-align: middle;
    }

    .categories-listing .categories-table tbody td {
        padding: 11px 14px;
        border-bottom: 1px solid #edf1f5;
        vertical-align: middle;
    }

    .categories-listing .categories-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .categories-listing .categories-table tbody tr {
        transition: background-color .15s ease;
    }

    .categories-listing .categories-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .categories-listing .categories-table > thead > tr > th:first-child,
    .categories-listing .categories-table > tbody > tr > .category-number {
        box-sizing: border-box;
        width: 60px;
        min-width: 60px;
        max-width: 60px;
        padding-right: 6px;
        padding-left: 6px;
        color: #64748b !important;
        font-variant-numeric: tabular-nums;
        text-align: center;
    }

    .categories-listing .category-title {
        color: #0f172a;
        font-weight: 600;
    }

    .categories-listing .categories-table code.badge,
    .categories-listing .categories-table td .badge {
        display: inline;
        padding: 0;
        border: 0 !important;
        border-radius: 0;
        background: transparent !important;
        color: #475569 !important;
        font-family: inherit;
        font-size: .8125rem;
        font-weight: 400;
        line-height: 1.5;
        text-align: left;
        white-space: normal;
        vertical-align: baseline;
    }

    .categories-listing .categories-table td .badge {
        margin-right: 8px;
    }

    .categories-listing .categories-table td .badge:last-child {
        margin-right: 0;
    }

    .categories-listing .categories-table td.text-muted.small {
        min-width: 150px;
        color: #64748b !important;
        font-size: .8125rem;
        line-height: 1.5;
    }

    .categories-listing .category-actions {
        white-space: nowrap;
    }

    .categories-listing .category-actions .btn {
        display: inline-flex;
        width: 32px;
        height: 32px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 6px;
    }

    .categories-listing .category-actions .btn-outline-secondary {
        border-color: #e2e8f0;
        color: #475569;
    }

    .categories-listing .category-actions .btn-outline-secondary:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .categories-listing .category-actions .btn-outline-primary:hover {
        background: #eff6ff;
    }

    .categories-listing .category-actions .btn-outline-danger:hover {
        background: #fef2f2;
    }

    .categories-listing .category-actions .btn:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .2);
        outline-offset: 2px;
    }

    @media (max-width: 767.98px) {
        .categories-listing .categories-list-heading {
            align-items: flex-start !important;
            flex-wrap: wrap;
        }

        .categories-listing .categories-list-title {
            font-size: 1.25rem;
        }

        .categories-listing .categories-table thead th,
        .categories-listing .categories-table tbody td {
            padding-right: 10px;
            padding-left: 10px;
        }
    }
</style>
@endpush

@section('content')
<div class="categories-listing">
<div class="categories-list-heading d-flex justify-content-between align-items-center mb-3">
    <h4 class="categories-list-title fw-bold mb-0"><i class="bi bi-tags-fill me-2 text-primary"></i>All Categories</h4>
    <a href="{{ route('categories.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Category
    </a>
</div>

<div class="card categories-table-card">
    <div class="card-body p-0">
        <div class="table-responsive categories-table-responsive">
            <table class="table table-hover align-middle mb-0 categories-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Classification</th>
                        <th>Subcategories</th>
                        <th>Description</th>
                        <th style="width:130px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td class="category-number text-muted small">{{ $category->id }}</td>
                        <td class="category-title fw-semibold">{{ $category->title }}</td>
                        <td><code class="badge bg-secondary">{{ $category->slug }}</code></td>

                        {{-- Classification: now a JSON array of strings --}}
                        <td>
                            @if(!empty($category->classification_name))
                                @foreach($category->classification_name as $name)
                                    <span class="badge bg-info text-dark me-1">{{ $name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Subcategories: now a JSON array of strings --}}
                        <td>
                            @if(!empty($category->subcategory))
                                @foreach($category->subcategory as $sub)
                                    <span class="badge bg-light text-dark border me-1">{{ $sub }}</span>
                                @endforeach
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        <td class="text-muted small">{{ Str::limit($category->description, 50) }}</td>
                        <td class="category-actions">
                            <a href="{{ route('categories.index', $category) }}"
                               class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('categories.edit', $category) }}"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('categories.destroy', $category) }}" method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete \'{{ addslashes($category->title) }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No categories found. <a href="{{ route('categories.create') }}">Create one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($categories->hasPages())
    <div class="mt-3 d-flex justify-content-end">{{ $categories->links() }}</div>
@endif
</div>
@endsection
