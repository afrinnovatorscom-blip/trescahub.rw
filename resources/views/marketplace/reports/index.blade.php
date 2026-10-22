@extends('front.layouts.admin')
@section('page-title', 'Reports')
@section('content')
<div class="container-fluid py-4">
    <h1 class="h3">Reports</h1>
    <form class="row g-2 my-3"><div class="col-auto"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div><div class="col-auto"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div><div class="col-auto"><button class="btn btn-dark">Filter</button></div></form>
    <div class="row g-3">
        <div class="col-md-4"><div class="dash-card p-3"><strong>Requests</strong><div>{{ $report['requests_total'] }}</div><div class="small">Converted {{ $report['converted'] }}</div></div></div>
        <div class="col-md-4"><div class="dash-card p-3"><strong>Late orders</strong><div>{{ $report['late_orders'] }}</div><div class="small">Cancelled {{ $report['cancelled_orders'] }}</div></div></div>
        <div class="col-md-4"><div class="dash-card p-3"><strong>Low stock</strong><div>{{ $report['low_stock'] }}</div><div class="small">Approvals recorded {{ $report['approval_hours'] }}</div></div></div>
        @if(isset($report['revenue']))<div class="col-md-4"><div class="dash-card p-3"><strong>Paid revenue</strong><div>RWF {{ number_format($report['revenue'], 0, '.', ',') }}</div></div></div>@endif
    </div>
    <h2 class="h6 mt-4">Orders by status</h2>
    <ul>@foreach($report['orders_by_status'] as $status => $total)<li>{{ $status }}: {{ $total }}</li>@endforeach</ul>
    <h2 class="h6">Products by approval</h2>
    <ul>@foreach($report['products_by_status'] as $status => $total)<li>{{ $status }}: {{ $total }}</li>@endforeach</ul>
    @if(isset($report['vendors']))
        <h2 class="h6">Vendor fulfillment</h2>
        <ul>@foreach($report['vendors'] as $vendor)<li>{{ $vendor->business_name }} — accepted items {{ $vendor->accepted_items }}</li>@endforeach</ul>
    @endif
</div>
@endsection
