@extends('front.layouts.admin')

@section('title', 'Edit partner')

@section('content')
    <div class="container-fluid px-4 px-lg-5 py-4 py-lg-5 partner-admin">
        <div class="mb-4">
            <a href="{{ route('partners.index') }}" class="partner-back-link">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Partners
            </a>
            <h1 class="partner-page-title mt-3 mb-1">Edit partner</h1>
            <p class="partner-page-description mb-0">Update partner details and website visibility.</p>
        </div>

        @include('partners._form', ['partner' => $partner])
    </div>

    @include('partners._form_styles')
@endsection
