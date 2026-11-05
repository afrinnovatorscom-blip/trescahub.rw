@extends('front.layouts.admin')
@section('title', 'Shopping & Orders — Settings')
@section('page-title', 'Shopping & Orders')

@push('styles')
    @include('settings._styles')
@endpush

@section('content')
<div class="settings-page py-3">

    {{-- Back to Settings --}}
    <a href="{{ route('admin.settings.index') }}" class="settings-back-link">
        <i class="bi bi-arrow-left"></i> Back to Settings
    </a>

    {{-- Page Header --}}
    <div class="settings-header">
        <h1 class="settings-title">Shopping & Orders</h1>
        <p class="settings-subtitle">Shop operations, order fulfillment workflow, and customer request states.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Order Lifecycle Workflow</h2>
            <p class="settings-card-desc">Standard state transition matrix governing customer orders across fulfillment.</p>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 35%;">Order Status</th>
                        <th>Allowed Next Transitions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orderTransitions as $status => $transitions)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">
                                    {{ str_replace('_', ' ', $status) }}
                                </span>
                            </td>
                            <td>
                                @if(count($transitions) > 0)
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($transitions as $next)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 0.72rem;">
                                                {{ str_replace('_', ' ', $next) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted small fst-italic">Terminal state (no further transitions)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
            <a href="{{ route('marketplace.orders.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-bag-check me-1"></i> Open Orders Queue
            </a>
            <a href="{{ route('marketplace.requests.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="bi bi-inbox me-1"></i> Open Product Requests
            </a>
            <a href="{{ route('shop-categories.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="bi bi-tag me-1"></i> Shop Categories
            </a>
        </div>
    </div>

</div>
@endsection

