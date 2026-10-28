{{-- resources/views/welcome.blade.php --}}
@extends('front.layouts.guest')

@section('content')
@include('front.section.hero-section')
@include('front.section.our-services')
@include('front.section.popular-service')
@include('front.section.popular-product')
@include('front.section.works')
@include('front.section.cta')
@include('front.section.client-say')
@include('front.section.our-partners')
@endsection