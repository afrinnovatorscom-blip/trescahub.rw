@extends('front.layouts.admin')
@section('page-title', $vendor->exists ? 'Edit vendor' : 'Register vendor')
@section('content')
<div class="container py-4" style="max-width: 900px;">
    @include('marketplace.partials.flash')
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <h1 class="h3 mb-3">{{ $vendor->exists ? 'Edit vendor' : 'Register vendor' }}</h1>
    <form method="POST" enctype="multipart/form-data" action="{{ $vendor->exists ? route('marketplace.vendors.update', $vendor) : route('marketplace.vendors.store') }}" class="d-flex flex-column gap-4">
        @csrf
        @if($vendor->exists) @method('PUT') @endif

        <section class="border rounded p-3" aria-labelledby="business-logo-heading">
            <h2 class="h5 mb-3" id="business-logo-heading">Business Logo</h2>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <img
                    id="business-logo-preview"
                    src="{{ $vendor->business_logo_path ? asset('assets/'.$vendor->business_logo_path) : '' }}"
                    alt="{{ $vendor->business_name ? $vendor->business_name.' logo preview' : 'Business logo preview' }}"
                    class="rounded-circle border bg-light"
                    style="width: 88px; height: 88px; object-fit: contain; {{ $vendor->business_logo_path ? '' : 'display: none;' }}"
                >
                <div class="flex-grow-1">
                    <label for="business_logo" class="form-label">Upload business logo</label>
                    <input id="business_logo" name="business_logo" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control">
                    <div class="form-text">Optional image, up to 5 MB. The original image is preserved.</div>
                    @if($vendor->business_logo_path)
                        <label class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                            <span class="form-check-label">Remove current logo</span>
                        </label>
                    @endif
                </div>
            </div>
        </section>

        <section class="border rounded p-3" aria-labelledby="business-information-heading">
            <h2 class="h5 mb-3" id="business-information-heading">Business Information</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
                    <input id="business_name" name="business_name" class="form-control" required value="{{ old('business_name', $vendor->business_name) }}">
                </div>
                <div class="col-md-6">
                    <label for="legal_name" class="form-label">Legal Name</label>
                    <input id="legal_name" name="legal_name" class="form-control" value="{{ old('legal_name', $vendor->legal_name) }}">
                </div>
                <div class="col-md-6">
                    <label for="reference_number" class="form-label">Reference</label>
                    <input id="reference_number" name="reference_number" class="form-control" value="{{ old('reference_number', $vendor->reference_number) }}">
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label">Vendor Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        @foreach(['pending', 'active', 'suspended', 'inactive'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $vendor->status)===$status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <section class="border rounded p-3" aria-labelledby="contact-location-heading">
            <h2 class="h5 mb-3" id="contact-location-heading">Contact and Location</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email / Login Email <span class="text-danger">*</span></label>
                    <input id="email" name="email" type="email" class="form-control" required value="{{ old('email', $vendor->email) }}" autocomplete="email">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input id="phone" name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}">
                </div>
                <div class="col-md-6">
                    <label for="district" class="form-label">District</label>
                    <input id="district" name="district" class="form-control" value="{{ old('district', $vendor->district) }}">
                </div>
                <div class="col-md-6">
                    <label for="operating_location" class="form-label">Operating Location</label>
                    <input id="operating_location" name="operating_location" class="form-control" value="{{ old('operating_location', $vendor->operating_location) }}">
                </div>
                <div class="col-12">
                    <label for="website_url" class="form-label">Website URL</label>
                    <input id="website_url" name="website_url" type="url" class="form-control" value="{{ old('website_url', $vendor->website_url) }}" placeholder="https://">
                </div>
                <div class="col-12">
                    <label for="address_notes" class="form-label">Address Notes</label>
                    <textarea id="address_notes" name="address_notes" class="form-control" rows="2">{{ old('address_notes', $vendor->address_notes) }}</textarea>
                </div>
            </div>
        </section>

        <section class="border rounded p-3" aria-labelledby="account-access-heading">
            <h2 class="h5 mb-3" id="account-access-heading">Account Access</h2>
            @if($vendor->exists)
                <p class="text-muted mb-0">Account passwords are managed through the existing password reset process.</p>
            @else
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input id="password" name="password" type="password" class="form-control" required autocomplete="new-password">
                    <button id="toggle-password" class="btn btn-outline-secondary" type="button" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
                <div class="form-text">Use at least 8 characters. This creates the vendor's login account.</div>
            @endif
        </section>

        <section class="border rounded p-3" aria-labelledby="partner-profile-heading">
            <h2 class="h5 mb-3" id="partner-profile-heading">Partner Profile</h2>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_as_partner" value="1" @checked(old('show_as_partner', $vendor->show_as_partner))>
                        <span class="form-check-label">Show as Partner</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label for="partner_description" class="form-label">Short Partner Description</label>
                    <textarea id="partner_description" name="partner_description" class="form-control" rows="3" maxlength="500">{{ old('partner_description', $vendor->partner_description) }}</textarea>
                </div>
                <div class="col-md-3">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input id="display_order" name="display_order" type="number" min="0" step="1" class="form-control" required value="{{ old('display_order', $vendor->display_order ?? 0) }}">
                </div>
                <div class="col-12">
                    <span class="form-label d-block">Website Publication</span>
                    <p class="form-text mb-0">
                        {{ $vendor->exists ? $vendor->websitePublicationLabel() : 'Not published' }}.
                        Publish or remove this vendor from the View Vendor page.
                    </p>
                </div>
            </div>
        </section>

        @if($vendor->exists)
            <section class="border rounded p-3" aria-labelledby="branding-approval-heading">
                <h2 class="h5 mb-2" id="branding-approval-heading">Public Branding Approval</h2>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="public_branding_approved" value="1"
                           @checked(old('public_branding_approved', $vendor->public_branding_approved))>
                    <span class="form-check-label">Approve this business name and logo for public display</span>
                </label>
                <p class="form-text mb-0">
                    Review the saved business name and logo before approving. Changing either one resets this approval;
                    publication remains a separate action on the View Vendor page.
                </p>
            </section>
        @endif

        <section class="border rounded p-3" aria-labelledby="internal-notes-heading">
            <h2 class="h5 mb-3" id="internal-notes-heading">Internal Notes</h2>
            <label for="internal_notes" class="form-label">Notes</label>
            <textarea id="internal_notes" name="internal_notes" class="form-control" rows="3">{{ old('internal_notes', $vendor->internal_notes) }}</textarea>
        </section>

        <div class="d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="{{ $vendor->exists ? route('marketplace.vendors.show', $vendor) : route('marketplace.vendors.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Vendor</button>
        </div>
    </form>
</div>
@if(!$vendor->exists)
<script>
    document.getElementById('toggle-password')?.addEventListener('click', function () {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.textContent = show ? 'Hide' : 'Show';
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        this.setAttribute('aria-pressed', String(show));
    });
</script>
@endif
<script>
    document.getElementById('business_logo')?.addEventListener('change', function () {
        const preview = document.getElementById('business-logo-preview');
        const file = this.files && this.files[0];
        if (!file) return;
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        const removeLogo = document.querySelector('input[name="remove_logo"]');
        if (removeLogo) removeLogo.checked = false;
    });
</script>
@endsection
