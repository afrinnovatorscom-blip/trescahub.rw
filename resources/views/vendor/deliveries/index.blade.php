@extends('front.layouts.admin')
@section('page-title', 'Deliveries')
@section('content')
<div class="container-fluid py-4 delivery-page">
    @include('marketplace.partials.flash')
    <div class="mb-4">
        <h1 class="h3 mb-1">Deliveries</h1>
        <p class="text-muted mb-0">Manage dispatch, shipment tracking and delivery completion.</p>
    </div>

    @include('marketplace.deliveries.partials.controls', [
        'indexRoute' => 'vendor.deliveries.index',
        'searchPlaceholder' => 'Order, product or tracking reference',
    ])

    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table delivery-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Product / Shipment</th>
                        <th>Delivery Method</th>
                        <th>Expected Delivery Date</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        @php
                            $deliveryItems = $task->item
                                ? collect([$task->item])
                                : ($task->order?->items ?? collect());
                            [$statusLabel, $statusClass] = match ($task->status) {
                                'assigned' => ['Pending', 'delivery-status-assigned'],
                                'in_transit' => ['In Transit', 'delivery-status-in-transit'],
                                'delivered' => ['Delivered', 'delivery-status-delivered'],
                                default => [\App\Support\CustomerTracking::deliveryLabel($task->status), ''],
                            };
                        @endphp
                        <tr>
                            <td>
                                <a class="fw-semibold text-decoration-none" href="{{ route('vendor.deliveries.show', $task->reference) }}">
                                    {{ $task->order?->reference ?? 'Order unavailable' }}
                                </a>
                            </td>
                            <td>
                                <div class="small text-muted mt-1">
                                    @forelse($deliveryItems as $item)
                                        <span class="d-block">{{ $item->product_name }} × {{ $item->quantity }}</span>
                                    @empty
                                        <span>Product details unavailable</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>{{ $task->delivery_method ? (config('marketplace.delivery_methods.'.$task->delivery_method) ?? $task->delivery_method) : 'Not recorded' }}</td>
                            <td>{{ $task->expected_delivery_at?->format('M j, Y') ?? 'Not scheduled' }}</td>
                            <td><span class="delivery-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $task->updated_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if($task->status === 'assigned' && (int) $task->vendor_id === (int) auth()->user()->vendor_id)
                                    <a class="btn btn-sm btn-primary" href="{{ route('vendor.deliveries.prepare.existing', $task->reference) }}">Prepare Delivery</a>
                                @elseif($task->status === 'in_transit')
                                    <form method="POST" action="{{ route('vendor.deliveries.delivered', $task->reference) }}" class="d-inline" data-delivery-complete-form>
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Mark as Delivered</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="delivery-empty">
                                    @if(!empty($filters['q']) || !empty($filters['delivery_method']) || !empty($filters['expected_date']))
                                        No delivery records match the selected filters.
                                    @else
                                        No {{ strtolower(match ($status) {
                                            'assigned' => 'pending',
                                            'in_transit' => 'in transit',
                                            'delivered' => 'delivered',
                                        }) }} deliveries have been assigned to your business.
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tasks->hasPages())
            <div class="card-body">{{ $tasks->links() }}</div>
        @endif
    </section>
</div>
@include('marketplace.deliveries.partials.live-completion')
@endsection
