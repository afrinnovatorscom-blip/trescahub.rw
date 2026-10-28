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
        @if($tasks->hasPages())
            <div class="card-body">{{ $tasks->links() }}</div>
        @endif
        @if($preparationOrders->hasPages())
            <div class="card-body border-top">{{ $preparationOrders->links() }}</div>
        @endif
    </section>
</div>
@endsection
