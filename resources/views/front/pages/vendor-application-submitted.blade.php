@extends('front.layouts.guest')

@section('content')
<main class="py-5" style="min-height: 65vh; background:#f9f9f6;">
    <div class="container py-4">
        <div class="mx-auto rounded-4 border bg-white p-4 p-md-5 text-center" style="max-width:680px;border-color:#e5e7eb !important;box-shadow:0 8px 30px rgba(17,24,39,.06);">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width:68px;height:68px;background:#ecfdf5;color:#15803d;font-size:1.8rem;" aria-hidden="true">
                <i class="bi bi-check2-circle"></i>
            </div>
            <h1 class="h2 fw-bold mb-3" style="color:#111827;">Application Submitted Successfully</h1>
            <p class="mb-4" style="color:#4b5563;line-height:1.75;">
                Thank you for registering with TRESCA Hub. Your business application has been received and is awaiting review by our administration team. We will notify you once your application has been reviewed. You will be able to start selling on TRESCA Hub after your application is approved.
            </p>
            <p class="small text-muted mb-4">Your application is pending review. Selling features become available after an administrator approves your application.</p>
            <a href="{{ route('login') }}" class="btn btn-success px-4 py-2 fw-semibold" style="background:#16a34a;border-color:#16a34a;">Sign in to your account</a>
        </div>
    </div>
</main>
@endsection
