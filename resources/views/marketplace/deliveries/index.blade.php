@extends('front.layouts.admin')
@section('page-title', 'Deliveries')
@push('styles')
<style>
    .delivery-pagination { align-items: center; display: flex; justify-content: flex-end; gap: 8px; }
    .delivery-pagination-button { align-items: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 7px; color: #475569; display: inline-flex; font-size: 13px; font-weight: 600; gap: 6px; min-height: 36px; padding: 6px 11px; text-decoration: none; }
    .delivery-pagination-button:hover { background: #f8fafc; border-color: #cbd5e1; color: #1d4ed8; }
    .delivery-pagination-button:focus-visible { outline: 3px solid rgba(37, 99, 235, .25); outline-offset: 2px; }
    .delivery-pagination-button.is-disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
    .delivery-pagination-page { color: #64748b; font-size: 12px; min-width: 64px; text-align: center; }
</style>
@endpush
@section('content')
<div class="container-fluid py-4 delivery-page">
    @include('marketplace.partials.flash')
    <div class="mb-4">
        <h1 class="h3 mb-1">Deliveries</h1>
        <p class="text-muted mb-0">Manage dispatch, shipment tracking and delivery completion.</p>
    </div>

    @include('marketplace.deliveries.partials.controls', [
        'indexRoute' => 'marketplace.deliveries.index',
        'searchPlaceholder' => 'Order, product or tracking reference',
    ])

    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table delivery-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Product / Shipment</th>
                        <th>Vendor</th>
                        <th>Delivery Method</th>
                        <th>Expected Delivery Date</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preparationOrders as $preparationOrder)
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none" href="{{ route('marketplace.orders.show', $preparationOrder) }}">
                                    {{ $preparationOrder->reference }}
                                </a>
                            </td>
                            <td>
                                <div class="small text-muted mt-1">
                                    @foreach($preparationOrder->items as $item)
                                        <span class="d-block">{{ $item->product_name }} × {{ $item->quantity }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>Admin handled</td>
                            <td>Not recorded</td>
                            <td>Not scheduled</td>
                            <td><span class="delivery-status delivery-status-assigned">Ready for Delivery</span></td>
                            <td>{{ $preparationOrder->updated_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-primary" href="{{ route('marketplace.deliveries.prepare.select', ['order_id' => $preparationOrder->id]) }}">Prepare Delivery</a>
                            </td>
                        </tr>
                    @endforeach
                    @forelse($tasks as $task)
                        @php
                            [$statusLabel, $statusClass] = match ($task->status) {
                                'assigned' => ['Pending', 'delivery-status-assigned'],
                                'in_transit' => ['In Transit', 'delivery-status-in-transit'],
                                'delivered' => ['Delivered', 'delivery-status-delivered'],
                                default => [\App\Support\CustomerTracking::deliveryLabel($task->status), ''],
                            };
                        @endphp
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none" href="{{ route('marketplace.deliveries.show', $task) }}">
                                    {{ $task->order?->reference ?? 'Order unavailable' }}
                                </a>
                                @if($task->order?->customer_name)
                                    <div class="small text-muted">{{ $task->order->customer_name }}</div>
                                @endif
                                @if($task->order?->phone)
                                    <div class="small text-muted">{{ $task->order->phone }}</div>
                                @endif
                                @if($task->order?->delivery_address)
                                    <div class="small text-muted">{{ $task->order->delivery_address }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="small text-muted mt-1">
                                    @if($task->item)
                                        <a class="text-decoration-none" href="{{ route('marketplace.deliveries.show', $task) }}">{{ $task->item->product_name }} × {{ $task->item->quantity }}</a>
                                    @else
                                        <span>Shipment details unavailable</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $task->vendor?->business_name ?? 'Not assigned' }}</td>
                            <td>{{ $task->delivery_method ? (config('marketplace.delivery_methods.'.$task->delivery_method) ?? $task->delivery_method) : 'Not recorded' }}</td>
                            <td>{{ $task->expected_delivery_at?->format('M j, Y') ?? 'Not scheduled' }}</td>
                            <td><span class="delivery-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $task->updated_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if($status === 'assigned')
                                    <a class="btn btn-sm btn-primary" href="{{ route('marketplace.deliveries.prepare.existing', $task) }}">Prepare Delivery</a>
                                @elseif($status === 'in_transit')
                                    <form method="POST" action="{{ route('marketplace.deliveries.delivered', $task) }}" class="d-inline" data-delivery-complete-form>
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Mark as Delivered</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        @if($preparationOrders->isEmpty())
                            <tr>
                                <td colspan="8">
                                    <div class="delivery-empty">
                                        @if(!empty($filters['q']) || !empty($filters['vendor_id']) || !empty($filters['delivery_method']) || !empty($filters['expected_date']))
                                            No delivery records match the selected filters.
                                        @else
                                            No {{ strtolower(match ($status) {
                                                'assigned' => 'pending',
                                                'in_transit' => 'in transit',
                                                'delivered' => 'delivered',
                                            }) }} deliveries.
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($status === 'delivered')
            <nav class="delivery-pagination px-3 py-3 border-top" aria-label="Delivered deliveries pagination">
                @if($tasks->currentPage() > 1)
                    <a class="delivery-pagination-button" href="{{ $tasks->url($tasks->currentPage() - 1) }}" rel="prev">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i><span>Previous</span>
                    </a>
                @else
                    <span class="delivery-pagination-button is-disabled" aria-disabled="true">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i><span>Previous</span>
                    </span>
                @endif
                <span class="delivery-pagination-page">Page {{ $tasks->currentPage() }} of {{ $tasks->lastPage() }}</span>
                @if($tasks->currentPage() < $tasks->lastPage())
                    <a class="delivery-pagination-button" href="{{ $tasks->url($tasks->currentPage() + 1) }}" rel="next">
                        <span>Next</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="delivery-pagination-button is-disabled" aria-disabled="true">
                        <span>Next</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </span>
                @endif
            </nav>
        @elseif($tasks->hasPages())
            <div class="card-body">{{ $tasks->links() }}</div>
        @endif
        @if($preparationOrders->hasPages())
            <div class="card-body border-top">{{ $preparationOrders->links() }}</div>
        @endif
    </section>
</div>
@include('marketplace.deliveries.partials.live-completion')
@endsection
