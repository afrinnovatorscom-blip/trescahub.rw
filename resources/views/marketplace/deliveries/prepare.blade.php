@extends('front.layouts.admin')
@section('page-title', 'Prepare Delivery')

@push('styles')
<style>
    .delivery-preparation { color: #0f172a; }
    .delivery-preparation .preparation-description { color: #64748b; }
    .delivery-preparation .preparation-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .delivery-preparation .preparation-card-header {
        border-bottom: 1px solid #e2e8f0;
        padding: 16px 20px;
    }
    .delivery-preparation .preparation-card-body { padding: 20px; }
    .delivery-preparation .summary-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        height: 100%;
        padding: 12px 14px;
        overflow-wrap: anywhere;
    }
    .delivery-preparation .summary-label {
        color: #64748b;
        display: block;
        font-size: .75rem;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .delivery-preparation .summary-value { color: #0f172a; font-size: .9rem; }
    .delivery-preparation .product-summary {
        border-bottom: 1px solid #f1f5f9;
        padding: 10px 0;
    }
    .delivery-preparation .product-summary:first-child { padding-top: 0; }
    .delivery-preparation .product-summary:last-child { border-bottom: 0; padding-bottom: 0; }
    .delivery-preparation .form-label { color: #334155; font-size: .84rem; font-weight: 600; }
    .delivery-preparation .form-control { min-height: 42px; }
    .delivery-preparation textarea.form-control { min-height: auto; }
    .delivery-preparation .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .15);
    }
    .delivery-preparation .status-pill {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 999px;
        color: #92400e;
        display: inline-flex;
        font-size: .75rem;
        font-weight: 600;
        padding: 4px 10px;
    }
    .delivery-preparation .preparation-actions {
        border-top: 1px solid #e2e8f0;
        padding-top: 16px;
    }
    @media (max-width: 575.98px) {
        .delivery-preparation .preparation-card-header { padding: 14px 16px; }
        .delivery-preparation .preparation-card-body { padding: 16px; }
        .delivery-preparation .preparation-actions > a,
        .delivery-preparation .preparation-actions > button { flex: 1 1 100%; }
    }
</style>
@endpush

@section('content')
@php($deliveryTask = $deliveryTask ?? null)
<div class="container-fluid py-4 delivery-preparation">
    @include('marketplace.partials.flash')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Prepare Delivery</h1>
            <p class="preparation-description mb-0">
                @if($deliveryTask)
                    Update preparation details for {{ $deliveryTask->reference }}. Saving a Pending delivery moves it to In Transit.
                @else
                    Assign a driver and record dispatch details for this order.
                @endif
            </p>
        </div>
        <a href="{{ route('marketplace.orders.show', $order) }}" class="btn btn-outline-secondary">View Order</a>
    </div>

    @error('delivery')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ $deliveryTask ? route('marketplace.deliveries.prepare.update', $deliveryTask) : route('marketplace.orders.deliveries.store', $order) }}" enctype="multipart/form-data" data-preparation-form>
        @csrf

        <section class="preparation-card mb-3" aria-labelledby="delivery-order-information">
            <header class="preparation-card-header">
                <h2 id="delivery-order-information" class="h5 mb-0">Order Information</h2>
                <p class="small text-muted mb-0 mt-1">Saved order details are shown for reference and cannot be changed here.</p>
            </header>
            <div class="preparation-card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3">
                        <div class="summary-item">
                            <span class="summary-label">Order number</span>
                            <span class="summary-value fw-semibold">{{ $order->reference }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="summary-item">
                            <span class="summary-label">Customer</span>
                            <span class="summary-value">{{ $order->customer_name ?: ($order->accountCustomer?->name ?? 'Not recorded') }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="summary-item">
                            <span class="summary-label">Customer phone</span>
                            <span class="summary-value">{{ $order->phone ?: ($order->accountCustomer?->phone ?? 'Not recorded') }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="summary-item">
                            <span class="summary-label">Customer email</span>
                            <span class="summary-value">{{ $order->email ?: ($order->accountCustomer?->email ?? 'Not recorded') }}</span>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="summary-item">
                            <span class="summary-label">Products and quantities</span>
                            @forelse($items as $item)
                                <div class="product-summary">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <span class="summary-value fw-semibold">{{ $item->product_name }}</span>
                                        <span class="summary-value">Qty {{ number_format($item->quantity) }}{{ $item->product?->stock_unit ? ' '.$item->product->stock_unit : '' }}</span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <span>{{ $item->product?->category?->name ?? 'Category not recorded' }}</span>
                                        @if($item->product?->subcategory?->name)
                                            <span> · {{ $item->product->subcategory->name }}</span>
                                        @endif
                                        @if($item->product?->product_type)
                                            <span> · {{ \Illuminate\Support\Str::headline($item->product->product_type) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <span class="summary-value">No specific order item is linked to this delivery record.</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="summary-item">
                            <span class="summary-label">Delivery address</span>
                            <span class="summary-value">{{ $order->delivery_address ?: 'No delivery address recorded' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="summary-item">
                            <span class="summary-label">Order status</span>
                            <span class="summary-value">{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="summary-item">
                            <span class="summary-label">Delivery status</span>
                            @if($deliveryTask)
                                <span class="status-pill">{{ $deliveryTask->status === 'assigned' ? 'Pending' : \Illuminate\Support\Str::headline($deliveryTask->status) }}</span>
                            @else
                                <span class="status-pill">Not prepared</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="preparation-card mb-3" aria-labelledby="delivery-assignment">
            <header class="preparation-card-header">
                <h2 id="delivery-assignment" class="h5 mb-0">Delivery Assignment</h2>
            </header>
            <div class="preparation-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="courier_name">Driver/Courier Name</label>
                        <input class="form-control @error('courier_name') is-invalid @enderror" id="courier_name" name="courier_name" value="{{ old('courier_name', $deliveryTask?->courier_name) }}" maxlength="150" placeholder="Enter the driver or courier name">
                        @error('courier_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="delivery_phone">Driver/Courier Phone Number</label>
                        <input class="form-control @error('delivery_phone') is-invalid @enderror" id="delivery_phone" name="delivery_phone" type="tel" value="{{ old('delivery_phone', $deliveryTask?->delivery_phone) }}" maxlength="40" placeholder="e.g. +250 7XX XXX XXX">
                        @error('delivery_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="vehicle_registration">Vehicle Registration Number</label>
                        <input class="form-control @error('vehicle_registration') is-invalid @enderror" id="vehicle_registration" name="vehicle_registration" value="{{ old('vehicle_registration', $deliveryTask?->vehicle_registration) }}" maxlength="40" placeholder="Enter the vehicle registration">
                        @error('vehicle_registration')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="expected_delivery_at">Expected Delivery Date</label>
                        <input class="form-control @error('expected_delivery_at') is-invalid @enderror" id="expected_delivery_at" name="expected_delivery_at" type="date" min="{{ now()->toDateString() }}" value="{{ old('expected_delivery_at', $deliveryTask?->expected_delivery_at?->toDateString()) }}">
                        @error('expected_delivery_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tracking_reference">Tracking Number or Dispatch Reference</label>
                        <input class="form-control @error('tracking_reference') is-invalid @enderror" id="tracking_reference" name="tracking_reference" value="{{ old('tracking_reference', $deliveryTask?->tracking_reference) }}" maxlength="100" placeholder="Enter a tracking or dispatch reference">
                        @error('tracking_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="preparation-card mb-3" aria-labelledby="dispatch-documents">
            <header class="preparation-card-header">
                <h2 id="dispatch-documents" class="h5 mb-0">Dispatch Documents and Notes</h2>
            </header>
            <div class="preparation-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="dispatch_proof">Dispatch Proof / Courier Ticket</label>
                        <input class="form-control @error('dispatch_proof') is-invalid @enderror" id="dispatch_proof" name="dispatch_proof" type="file" accept=".jpg,.jpeg,.png,.pdf">
                        @error('dispatch_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">PDF, JPG or PNG up to 4 MB. This is dispatch documentation, not proof of completed delivery.</div>
                        @if($deliveryTask?->dispatch_proof_path)
                            <div class="small mt-2"><a href="{{ route('marketplace.deliveries.dispatch-proof', $deliveryTask) }}">Download the currently saved dispatch document</a></div>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="delivery_notes">Delivery Notes</label>
                        <textarea class="form-control @error('delivery_notes') is-invalid @enderror" id="delivery_notes" name="delivery_notes" rows="3" maxlength="2000" placeholder="Add delivery instructions or other relevant notes">{{ old('delivery_notes', $deliveryTask?->delivery_notes) }}</textarea>
                        @error('delivery_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex flex-wrap justify-content-end gap-2 preparation-actions mt-4">
                    <a href="{{ $deliveryTask ? route('marketplace.deliveries.show', $deliveryTask) : route('marketplace.deliveries.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" data-preparation-submit>
                        {{ $deliveryTask ? 'Save Changes' : 'Save & Prepare Delivery' }}
                    </button>
                </div>
            </div>
        </section>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-preparation-form]').forEach((form) => {
        const submit = form.querySelector('[data-preparation-submit]');
        form.addEventListener('submit', () => {
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Saving...';
            }
        });
    });
</script>
@endpush
