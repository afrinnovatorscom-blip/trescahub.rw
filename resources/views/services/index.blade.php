@extends('front.layouts.admin')

@section('title', 'All Services')

@push('styles')
<style>
    .services-index-page {
        --services-border: #e2e8f0;
        --services-heading: #0f172a;
        --services-body: #334155;
        --services-muted: #64748b;
        --services-surface: #fff;
        --services-subtle: #f8fafc;
        color: var(--services-body);
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .services-index-page .services-page-heading {
        gap: 16px;
        margin-bottom: 20px !important;
    }

    .services-index-page .services-page-title {
        color: var(--services-heading);
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .services-index-page .services-table-card {
        overflow: hidden;
        border: 1px solid var(--services-border);
        border-radius: 12px;
        background: var(--services-surface);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .services-index-page .services-table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .services-index-page .services-table {
        min-width: 1080px;
        color: var(--services-body);
        font-size: .875rem;
    }

    .services-index-page .services-table thead th {
        padding: 13px 14px;
        border-bottom: 1px solid var(--services-border);
        background: var(--services-subtle);
        color: var(--services-muted);
        font-size: .75rem;
        font-weight: 600;
        line-height: 1.35;
        white-space: nowrap;
        vertical-align: middle;
    }

    .services-index-page .services-table tbody td {
        padding: 12px 14px;
        border-bottom-color: #edf1f5;
        vertical-align: middle;
    }

    .services-index-page .services-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .services-index-page .services-table tbody tr {
        transition: background-color .15s ease;
    }

    .services-index-page .services-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .services-index-page .service-row-number {
        color: var(--services-muted) !important;
        font-variant-numeric: tabular-nums;
    }

    .services-index-page .service-thumbnail,
    .services-index-page .service-thumbnail-placeholder {
        display: flex;
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--services-border);
        border-radius: 8px;
        object-fit: cover;
    }

    .services-index-page .service-thumbnail-placeholder {
        background: var(--services-subtle);
        color: var(--services-muted);
    }

    .services-index-page .service-title-cell {
        min-width: 210px;
        max-width: 280px;
    }

    .services-index-page .service-title {
        overflow: hidden;
        color: var(--services-heading);
        font-size: .875rem;
        font-weight: 600;
        line-height: 1.4;
        text-overflow: ellipsis;
    }

    .services-index-page .service-slug {
        display: block;
        margin-top: 3px;
        color: var(--services-muted);
        font-family: inherit;
        font-size: .75rem;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .services-index-page .service-price {
        white-space: nowrap;
    }

    .services-index-page .service-price-heading {
        text-align: left;
        white-space: nowrap;
    }

    .services-index-page .service-price {
        color: #15803d;
        font-size: .875rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .services-index-page .service-badge {
        display: inline-flex;
        max-width: 220px;
        align-items: center;
        padding: 5px 9px;
        border: 1px solid transparent;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 500;
        line-height: 1.25;
        white-space: normal;
    }

    .services-index-page .service-badge-subcategory {
        margin: 2px 4px 2px 0;
        border-color: var(--services-border);
        background: var(--services-subtle);
        color: #475569;
    }

    .services-index-page .service-badge-popular {
        border-color: #fde68a;
        background: #fffbeb;
        color: #b45309;
    }

    .services-index-page .service-badge-stock {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .services-index-page .service-badge-default {
        border-color: var(--services-border);
        background: var(--services-subtle);
        color: var(--services-muted);
    }

    .services-index-page .service-working-day {
        color: #475569;
        font-size: .8125rem;
        white-space: nowrap;
    }

    .services-index-page .service-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .services-index-page .service-action-button {
        display: inline-flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-color: var(--services-border);
        border-radius: 8px;
        background: #fff;
    }

    .services-index-page .service-action-view {
        color: #475569;
    }

    .services-index-page .service-action-view:hover {
        border-color: #cbd5e1;
        background: var(--services-subtle);
        color: #0f172a;
    }

    .services-index-page .service-action-edit {
        color: #2563eb;
    }

    .services-index-page .service-action-edit:hover {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .services-index-page .service-action-delete {
        color: #b91c1c;
    }

    .services-index-page .service-action-delete:hover {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .services-index-page .service-action-button:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .25);
        outline-offset: 2px;
    }

    @media (max-width: 576px) {
        .services-index-page .services-page-heading {
            align-items: flex-start !important;
            flex-wrap: wrap;
        }

        .services-index-page .services-page-title {
            font-size: 1.25rem;
        }
    }
</style>
@endpush

@section('content')
<div class="services-index-page">
    <div class="services-page-heading d-flex justify-content-between align-items-center">
        <h4 class="services-page-title fw-bold mb-0"><i class="bi bi-briefcase-fill me-2 text-primary"></i>All Services</h4>
        <a href="{{ route('services.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Service
        </a>
    </div>

    <div class="card services-table-card">
        <div class="card-body p-0">
            <div class="table-responsive services-table-responsive">
                <table class="table table-hover align-middle mb-0 services-table">
                    <thead>
                        <tr>
                            <th scope="col" style="width:50px">#</th>
                            <th scope="col" style="width:70px">Thumb</th>
                            <th scope="col">Title</th>
                            <th scope="col" class="service-price-heading">Price</th>
                            <th scope="col">Classification</th>
                            <th scope="col">Subcategories</th>
                            <th scope="col">Working Day</th>
                            <th scope="col">Status</th>
                            <th scope="col" style="width:130px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                        <tr>
                            <td class="service-row-number small">{{ $service->id }}</td>

                            <td>
                                @if($service->thumbnail)
                                    <img src="{{ asset('' . $service->thumbnail) }}"
                                         class="service-thumbnail"
                                         alt="{{ $service->title }}">
                                @else
                                    <div class="service-thumbnail-placeholder" aria-hidden="true">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </td>

                            <td class="service-title-cell">
                                <div class="service-title" title="{{ $service->title }}">{{ $service->title }}</div>
                                <code class="service-slug">{{ $service->slug }}</code>
                            </td>

                            <td class="service-price">
                                RWF {{ number_format($service->price, 2) }}
                            </td>

                            <td>
                                @if($service->classification_name && isset($categories[$service->classification_name]))
                                    {{ $categories[$service->classification_name]->title }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td>
                                @if(!empty($service->subcategory))
                                    @foreach($service->subcategory as $sub)
                                        <span class="service-badge service-badge-subcategory">{{ $sub }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <td class="service-working-day">{{ $service->working_day ?: '—' }}</td>

                            {{-- Status: Popular / In Stock / Default badges --}}
                            <td>
                                @if($service->is_popular)
                                    <span class="service-badge service-badge-popular">
                                        <i class="bi bi-fire me-1" aria-hidden="true"></i> Popular
                                    </span>
                                @elseif($service->is_in_stock)
                                    <span class="service-badge service-badge-stock">
                                        <i class="bi bi-check-circle me-1" aria-hidden="true"></i> In Stock
                                    </span>
                                @else
                                    <span class="service-badge service-badge-default">
                                        <i class="bi bi-dash-circle me-1" aria-hidden="true"></i> Default
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div class="service-actions">
                                    <a href="{{ route('services.show', $service) }}"
                                       class="btn btn-sm service-action-button service-action-view"
                                       title="View" aria-label="View {{ $service->title }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <a href="{{ route('services.edit', $service) }}"
                                       class="btn btn-sm service-action-button service-action-edit"
                                       title="Edit" aria-label="Edit {{ $service->title }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <form action="{{ route('services.destroy', $service) }}" method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete \'{{ addslashes($service->title) }}\'?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm service-action-button service-action-delete"
                                                title="Delete" aria-label="Delete {{ $service->title }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No services found. <a href="{{ route('services.create') }}">Create one</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($services->hasPages())
        <div class="mt-3">{{ $services->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection