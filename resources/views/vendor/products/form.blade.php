@extends('front.layouts.admin')

@section('page-title', 'Product')

@section('content')
    @include('marketplace.partials.flash')
    @include('shop-products.form', [
        'shopProduct' => $product,
        'vendorMode' => true,
    ])
@endsection
