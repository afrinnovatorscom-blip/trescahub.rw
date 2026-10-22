@extends('front.layouts.admin')
@section('page-title', 'Deliveries')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">Deliveries</h1>
    <table class="table"><thead><tr><th>Reference</th><th>Order</th><th>Status</th><th>Officer</th></tr></thead>
        <tbody>@forelse($tasks as $task)<tr><td><a href="{{ route('marketplace.deliveries.show', $task) }}">{{ $task->reference }}</a></td><td>{{ $task->order->reference ?? '' }}</td><td>{{ $task->status }}</td><td>{{ $task->officer->name ?? 'Unassigned' }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No deliveries.</td></tr>@endforelse</tbody></table>
    {{ $tasks->links() }}
</div>
@endsection
