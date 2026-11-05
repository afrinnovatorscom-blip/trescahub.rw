@extends('front.layouts.admin')
@section('page-title', 'Orders')
@push('styles')
<style>
.orders-workspace{color:#0f172a}.orders-tabs{display:flex;gap:18px;border-bottom:1px solid #e2e8f0}.orders-tab{padding:12px 4px;color:#64748b;text-decoration:none;font-size:.95rem;font-weight:600;border-bottom:3px solid transparent}.orders-tab.active{color:#2563eb;border-bottom-color:#2563eb}.orders-panel{background:#fff;border:1px solid #e2e8f0;border-radius:10px}.orders-filter{display:flex;gap:8px;align-items:center}.orders-filter>*{height:40px}.orders-filter .search{min-width:220px;max-width:320px;flex:1}.orders-table th{background:#f8fafc;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;padding:10px;border-bottom:1px solid #e2e8f0}.orders-table td{padding:10px;border-color:#f1f5f9;font-size:.84rem;vertical-align:middle}.orders-table th,.orders-table td{text-align:left}.orders-table .action{text-align:right}.orders-ref{color:#1d4ed8;font-weight:700}.review-btn,.send-btn{min-height:36px;border-radius:6px;font-size:.82rem;font-weight:700;padding:7px 12px}.review-btn{background:#2563eb;border-color:#2563eb;color:#fff}.review-btn:hover{background:#1d4ed8;border-color:#1d4ed8;color:#fff}.send-btn{background:#fff;border:1px solid #2563eb;color:#2563eb}.send-btn:hover{background:#eff6ff;color:#1d4ed8}.orders-empty{text-align:center!important;color:#64748b;padding:38px!important}@media(max-width:991px){.orders-filter{flex-wrap:wrap}.orders-filter>*{width:100%!important;max-width:none}.orders-table thead{display:none}.orders-table,.orders-table tbody,.orders-table tr,.orders-table td{display:block;width:100%}.orders-table tr{border-bottom:1px solid #e2e8f0;padding:8px}.orders-table td{border:0;padding:6px 8px}.orders-table td[data-label]:before{content:attr(data-label);display:block;color:#64748b;font-size:.68rem;font-weight:700;text-transform:uppercase;margin-bottom:2px}.orders-table .action{text-align:left}}
</style>
@endpush
@section('content')
<div class="container-fluid py-4 orders-workspace">
@include('marketplace.partials.flash')
@if(session('vendor_order_approval_success'))
<div id="vendor-order-approval-toast" class="toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3" role="status" aria-live="polite" aria-atomic="true">
  <div class="d-flex"><div class="toast-body"><i class="fa-solid fa-circle-check me-2"></i>{{ session('vendor_order_approval_success') }}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>
</div>
@endif
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3 mb-0">Orders</h1></div>
<nav class="orders-tabs mb-3" aria-label="Order tabs">
<a href="{{ route('vendor.orders.index',['tab'=>'pending']) }}" class="orders-tab {{ $tab==='pending'?'active':'' }}" @if($tab==='pending') aria-current="page" @endif><i class="fa-solid fa-clock me-1"></i>Pending Orders</a>
<a href="{{ route('vendor.orders.index',['tab'=>'approved']) }}" class="orders-tab {{ $tab==='approved'?'active':'' }}" @if($tab==='approved') aria-current="page" @endif><i class="fa-solid fa-circle-check me-1"></i>Approved Orders</a>
</nav>
<section class="orders-panel p-3 mb-3">
<form method="GET" action="{{ route('vendor.orders.index') }}" class="orders-filter">
<input type="hidden" name="tab" value="{{ $tab }}">
<label class="visually-hidden" for="vendor-order-search">Search</label><input id="vendor-order-search" class="form-control search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search order or customer">
<button class="btn btn-primary px-3" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Search</button>
<a class="btn btn-outline-secondary px-3" href="{{ route('vendor.orders.index',['tab'=>$tab]) }}">Clear</a>
</form>
</section>
<section class="orders-panel overflow-hidden">
<div class="table-responsive"><table class="table orders-table align-middle mb-0"><thead><tr><th>Order Number</th><th>Customer</th><th>{{ $tab==='pending'?'Order Date':'Approved Date' }}</th><th>Total Amount</th><th class="action">Action</th></tr></thead><tbody>
@forelse($orders as $order)
<tr><td data-label="Order Number"><span class="orders-ref">{{ $order->reference }}</span></td><td data-label="Customer">{{ $order->customer_name ?: ($order->customer?->name ?: 'Not provided') }}</td><td data-label="{{ $tab==='pending'?'Order Date':'Approved Date' }}">{{ $tab==='pending' ? $order->created_at?->format('M j, Y') : $order->updated_at?->format('M j, Y') }}</td><td data-label="Total Amount" class="fw-semibold">{{ number_format((float)$order->total,2,'.',',') }} RWF</td><td data-label="Action" class="action">@if($tab==='pending')<a href="{{ route('vendor.orders.show',$order->reference) }}" class="btn review-btn"><i class="fa-solid fa-eye me-1"></i>Review Order</a>@else<form method="POST" action="{{ route('vendor.orders.create-delivery',$order->reference) }}" class="d-inline send-delivery-form">@csrf<button type="submit" class="btn send-btn"><i class="fa-solid fa-truck me-1"></i>Send to Delivery</button></form>@endif</td></tr>
@empty
<tr><td colspan="5" class="orders-empty">No {{ $tab }} orders found.</td></tr>
@endforelse
</tbody></table></div>
<div class="p-3">{{ $orders->links() }}</div>
</section>
</div>
<script>(()=>{const approvalToast=document.getElementById('vendor-order-approval-toast');if(approvalToast&&window.bootstrap){bootstrap.Toast.getOrCreateInstance(approvalToast,{delay:6000}).show()}document.querySelectorAll('.send-delivery-form').forEach(form=>{form.addEventListener('submit',event=>{const reference=form.closest('tr')?.querySelector('.orders-ref')?.textContent.trim();if(!confirm(`Send ${reference || 'this order'} to Deliveries Pending?`)){event.preventDefault();}});});})();</script>
@endsection