@extends('front.layouts.admin')
@section('page-title', 'Orders')

@push('styles')
<style>
.orders-workspace{color:#0f172a}.orders-tabs{display:flex;gap:18px;border-bottom:1px solid #e2e8f0}.orders-tab{padding:12px 4px;color:#64748b;text-decoration:none;font-weight:700;border-bottom:3px solid transparent}.orders-tab.active{color:#2563eb;border-bottom-color:#2563eb}.orders-panel{background:#fff;border:1px solid #e2e8f0;border-radius:10px}.orders-filter{display:flex;gap:8px;align-items:center}.orders-filter>*{height:40px}.orders-filter .search{min-width:220px;max-width:320px;flex:1}.orders-table{width:100%;table-layout:fixed}.orders-table th{background:#f8fafc;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;padding:8px 7px;border-bottom:1px solid #e2e8f0;overflow-wrap:anywhere}.orders-table td{padding:8px 7px;border-color:#f1f5f9;font-size:.84rem;vertical-align:middle;overflow-wrap:anywhere}.orders-table th,.orders-table td{text-align:left}.orders-table .action{text-align:right}.pending-orders-table-wrap,.approved-orders-table-wrap{width:100%;max-width:100%;min-width:0}.pending-orders-table,.approved-orders-table{width:100%;max-width:100%;min-width:0}.pending-orders-table th,.pending-orders-table td{padding-right:7px;padding-left:7px}.approved-orders-table th,.approved-orders-table td{padding:8px 6px;text-align:center}.approved-orders-table th.action,.approved-orders-table td.action{text-align:center}.orders-ref{color:#1d4ed8;font-weight:700}.availability-pill{display:inline-flex;align-items:center;max-width:100%;border:1px solid #e2e8f0;border-radius:999px;background:#f8fafc;color:#475569;padding:4px 8px;font-size:.72rem;font-weight:700;line-height:1.2;white-space:normal}.availability-pill.pending{border-color:#fde68a;background:#fffbeb;color:#a16207}.availability-pill.confirmed{border-color:#bbf7d0;background:#f0fdf4;color:#15803d}.availability-pill.action-required{border-color:#fecaca;background:#fef2f2;color:#b91c1c}.review-btn,.send-btn{min-height:36px;border-radius:6px;font-size:.82rem;font-weight:700;padding:7px 12px;white-space:nowrap}.review-btn{background:#2563eb;border-color:#2563eb;color:#fff}.review-btn:hover{background:#1d4ed8;border-color:#1d4ed8;color:#fff}.send-btn{background:#fff;border:1px solid #2563eb;color:#2563eb}.send-btn:hover{background:#eff6ff;color:#1d4ed8}.orders-empty{text-align:center!important;color:#64748b;padding:38px!important}@media(max-width:991px){.orders-filter{flex-wrap:wrap}.orders-filter>*{width:100%!important;max-width:none}.orders-table{table-layout:auto}.orders-table thead{display:none}.orders-table,.orders-table tbody,.orders-table tr,.orders-table td{display:block;width:100%}.orders-table tr{border-bottom:1px solid #e2e8f0;padding:8px}.orders-table td{border:0;padding:6px 8px}.orders-table td[data-label]:before{content:attr(data-label);display:block;color:#64748b;font-size:.68rem;font-weight:700;text-transform:uppercase;margin-bottom:2px}.orders-table .action{text-align:left}.approved-orders-table td,.approved-orders-table td.action{text-align:center;padding:6px 8px}}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 orders-workspace">
@include('marketplace.partials.flash')
@if(session('allocation_success'))<div id="allocation-success-toast" class="toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3" role="status" aria-live="polite" aria-atomic="true"><div class="d-flex"><div class="toast-body"><i class="fa-solid fa-circle-check me-2"></i>{{ session('allocation_success') }}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>@endif
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Orders</h1></div>
<nav class="orders-tabs mb-3" aria-label="Order tabs">
<a href="{{ route('marketplace.orders.index',['tab'=>'pending']) }}" class="orders-tab {{ $tab==='pending'?'active':'' }}" @if($tab==='pending') aria-current="page" @endif>Pending Orders</a>
<a href="{{ route('marketplace.orders.index',['tab'=>'approved']) }}" class="orders-tab {{ $tab==='approved'?'active':'' }}" @if($tab==='approved') aria-current="page" @endif>Approved Orders</a>
</nav>
<section class="orders-panel p-3 mb-3">
<form method="GET" action="{{ route('marketplace.orders.index') }}" class="orders-filter">
<input type="hidden" name="tab" value="{{ $tab }}">
<input id="order-search" class="form-control search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search order or customer">
<input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" aria-label="From date">
<input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" aria-label="To date">
@if(auth()->user()->hasPermission('vendors.view'))
<select class="form-select" name="vendor" aria-label="Assigned vendor"><option value="">All assignees</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected((string)($filters['vendor']??'') === (string)$vendor->id)>{{ $vendor->business_name }}</option>@endforeach</select>
@endif
<button class="btn btn-primary px-3" type="submit">Search</button>
<a class="btn btn-outline-secondary px-3" href="{{ route('marketplace.orders.index',['tab'=>$tab]) }}">Clear</a>
</form>
</section>
<section class="orders-panel overflow-hidden">
<div class="{{ $tab==='pending'?'pending-orders-table-wrap':'approved-orders-table-wrap' }}"><table class="table orders-table {{ $tab==='pending'?'pending-orders-table':'approved-orders-table' }} align-middle mb-0"><colgroup>@if($tab==='pending')<col style="width:18%"><col style="width:18%"><col style="width:14%"><col style="width:16%"><col style="width:20%"><col style="width:14%">@else<col style="width:18%"><col style="width:22%"><col style="width:13%"><col style="width:14%"><col style="width:15%"><col style="width:18%">@endif</colgroup><thead><tr>@if($tab==='pending')<th>Order Number</th><th>Customer</th><th>Order Date</th><th>Total Amount</th><th>Product Availability</th><th class="action">Action</th>@else<th>Order Number</th><th>Customer</th><th>Total Amount</th><th>Approved Date</th><th>Product Availability</th><th class="action">Action</th>@endif</tr></thead><tbody>
@forelse($orders as $order)
@php
$availabilityClass=match($order->product_availability_status){'Pending Confirmation'=>'pending','Availability Confirmed'=>'confirmed','Action Required'=>'action-required',default=>''};
@endphp
<tr>
@if($tab==='pending')
<td data-label="Order Number"><span class="orders-ref">{{ $order->reference }}</span></td><td data-label="Customer">{{ $order->customer_name ?: ($order->customer?->name ?: 'Not provided') }}</td><td data-label="Order Date">{{ $order->created_at?->format('M j, Y') }}</td><td data-label="Total Amount" class="fw-semibold">{{ number_format((float)$order->total,2,'.',',') }} RWF</td><td data-label="Product Availability"><span class="availability-pill {{ $availabilityClass }}">{{ $order->product_availability_status }}</span></td><td data-label="Action" class="action"><a href="{{ route('marketplace.orders.show',['order'=>$order,'allocate'=>1]) }}" class="btn review-btn">Review Order</a></td>
@else
<td data-label="Order Number"><span class="orders-ref">{{ $order->reference }}</span></td><td data-label="Customer">{{ $order->customer_name ?: ($order->customer?->name ?: 'Not provided') }}</td><td data-label="Total Amount" class="fw-semibold">{{ number_format((float)$order->total,2,'.',',') }} RWF</td><td data-label="Approved Date">{{ $order->updated_at?->format('M j, Y') }}</td><td data-label="Product Availability"><span class="availability-pill {{ $availabilityClass }}">{{ $order->product_availability_status }}</span></td><td data-label="Action" class="action"><form method="POST" action="{{ route('marketplace.orders.send-delivery',$order) }}" class="d-inline send-delivery-form">@csrf<button type="submit" class="btn send-btn">Send to Delivery</button></form></td>
@endif
</tr>
@empty
<tr><td colspan="{{ $tab==='pending'?6:7 }}" class="orders-empty">No {{ strtolower($tab) }} orders found.</td></tr>
@endforelse
</tbody></table></div>
<div class="p-3">{{ $orders->links() }}</div>
</section>
</div>
<script>(()=>{document.querySelectorAll('.send-delivery-form').forEach(form=>{form.addEventListener('submit',event=>{const row=form.closest('tr');const reference=row?.querySelector('.orders-ref')?.textContent.trim();if(!confirm(`Send ${reference || 'this order'} to Deliveries Pending?`)){event.preventDefault();}});});})();</script>
@endsection
@push('scripts')
<script>(()=>{const allocationToast=document.getElementById('allocation-success-toast');if(allocationToast&&window.bootstrap){bootstrap.Toast.getOrCreateInstance(allocationToast,{delay:6000}).show()}})();</script>
@endpush