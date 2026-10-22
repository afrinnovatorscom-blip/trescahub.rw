@extends('front.layouts.admin')
@section('page-title', 'Vendor overview')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3 mb-3">Your workspace</h1>
    <div class="row g-3">
        <div class="col-md-3"><div class="dash-card p-3"><div class="h4 mb-0">{{ $products }}</div><div class="small text-muted">Products</div></div></div>
        <div class="col-md-3"><div class="dash-card p-3"><div class="h4 mb-0">{{ $pending }}</div><div class="small text-muted">Awaiting approval</div></div></div>
        <div class="col-md-3"><div class="dash-card p-3"><div class="h4 mb-0">{{ $requests }}</div><div class="small text-muted">Requests to answer</div></div></div>
        <div class="col-md-3"><div class="dash-card p-3"><div class="h4 mb-0">{{ $orders }}</div><div class="small text-muted">Assigned orders</div></div></div>
    </div>
</div>
@endsection
