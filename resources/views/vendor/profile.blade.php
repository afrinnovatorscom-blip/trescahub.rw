@extends('front.layouts.admin')
@section('page-title', 'Account Profile')
@push('styles')
<style>
    .vendor-profile-page { max-width: 1120px; color: #0f172a; }
    .profile-breadcrumb { margin-bottom: 16px; color: #64748b; font-size: .84rem; }
    .profile-breadcrumb a { color: #2563eb; text-decoration: none; }
    .profile-heading h1 { margin: 0; font-size: 1.65rem; font-weight: 700; }
    .profile-heading p { margin: 6px 0 0; color: #64748b; font-size: .92rem; }
    .profile-identity { display:flex; align-items:center; gap:18px; margin:22px 0; padding:20px 22px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:0 2px 7px rgba(15,23,42,.035); }
    .profile-logo { display:flex; width:88px; height:88px; flex:0 0 88px; align-items:center; justify-content:center; overflow:hidden; border:1px solid #e2e8f0; border-radius:50%; background:#eff6ff; color:#2563eb; font-size:1.6rem; font-weight:700; }
    .profile-logo img { width:100%; height:100%; border-radius:50%; object-fit:contain; }
    .profile-identity h2 { margin:0 0 4px; font-size:1.15rem; font-weight:700; overflow-wrap:anywhere; }
    .profile-identity-meta { color:#64748b; font-size:.86rem; }
    .profile-status { display:inline-flex; margin-top:8px; padding:4px 9px; border:1px solid #bfdbfe; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:.76rem; font-weight:600; }
    .profile-section { margin-bottom:20px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:0 2px 7px rgba(15,23,42,.035); }
    .profile-section-heading { padding:20px 22px 14px; border-bottom:1px solid #e2e8f0; }
    .profile-section-heading h2 { margin:0; font-size:1.1rem; font-weight:700; }
    .profile-section-heading p { margin:5px 0 0; color:#64748b; font-size:.86rem; }
    .profile-section-body { padding:20px 22px; }
    .profile-section .form-label { margin-bottom:6px; color:#334155; font-size:.85rem; font-weight:600; }
    .profile-section .form-control { min-height:42px; border-color:#cbd5e1; border-radius:8px; font-size:.9rem; }
    .profile-section textarea.form-control { min-height:92px; }
    .profile-section .form-control:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
    .profile-section .form-control[readonly] { background:#f8fafc; color:#475569; }
    .profile-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:10px; padding-top:16px; border-top:1px solid #e2e8f0; }
    .profile-actions .btn { min-height:42px; padding:9px 16px; border-radius:8px; font-size:.86rem; font-weight:600; }
    .profile-help { color:#64748b; font-size:.8rem; }
    .profile-alert { border-radius:9px; }
    .profile-upload-row { display:flex; align-items:center; gap:12px; }
    .profile-password-input { position:relative; }
    .profile-password-input .form-control { padding-right:68px; }
    .profile-password-toggle { position:absolute; top:50%; right:8px; padding:4px 8px; transform:translateY(-50%); border:0; border-radius:6px; background:transparent; color:#2563eb; font-size:.78rem; font-weight:600; }
    .profile-password-toggle:hover { background:#eff6ff; }
    .profile-password-toggle:focus-visible,.profile-actions .btn:focus-visible,.profile-upload-label:focus-within { outline:3px solid rgba(37,99,235,.45); outline-offset:2px; }
    @media(max-width:575.98px) { .profile-identity { align-items:flex-start; padding:16px; } .profile-logo { width:68px; height:68px; flex-basis:68px; } .profile-section-heading,.profile-section-body { padding:16px; } .profile-heading h1 { font-size:1.4rem; } .profile-actions { justify-content:stretch; } .profile-actions .btn { flex:1; } }
</style>
@endpush
@section('content')
@php
    $logoPath = preg_replace('#^(public/)?assets/#', '', ltrim((string) $vendor->business_logo_path, '/'));
    $logoAvailable = $logoPath !== ''
        && ! str_contains($logoPath, '..')
        && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$logoPath));
    $initialSource = trim((string) ($vendor->business_name ?: ''));
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($initialSource ?: 'A', 0, 1));
    $partnerDisplay = $vendor->partnerStatusLabel();
@endphp
<div class="container-fluid py-4 vendor-profile-page">
    @include('marketplace.partials.flash')

    <nav class="profile-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('vendor.dashboard') }}">Vendor Panel</a>
        <span aria-hidden="true"> / </span>
        <span aria-current="page">Account Profile</span>
    </nav>

    <header class="profile-heading">
        <h1>Account Profile</h1>
        <p>Manage your business information and account security.</p>
    </header>

    <section class="profile-identity" aria-label="Business identity">
        <div class="profile-logo">
            @if($logoAvailable)
                <img id="business-logo-preview" src="{{ asset('assets/'.$logoPath) }}" alt="{{ $vendor->business_name }} business logo" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
            @else
                <img id="business-logo-preview" src="" alt="" hidden>
            @endif
            <span id="business-logo-fallback" @if($logoAvailable) hidden @endif>{{ $initial }}</span>
        </div>
        <div>
            <h2>{{ $vendor->business_name ?: 'Business profile' }}</h2>
            <div class="profile-identity-meta">
                @if($vendor->reference_number)Reference: {{ $vendor->reference_number }} · @endif
                Partner display: {{ $partnerDisplay }} · Branding: {{ $vendor->brandingStatusLabel() }}
            </div>
            <span class="profile-status">Operational status: {{ ucfirst($vendor->status) }}</span>
        </div>
    </section>

    <section class="profile-section" aria-labelledby="business-profile-heading">
        <div class="profile-section-heading">
            <h2 id="business-profile-heading">Business Profile</h2>
            <p>These details are shared by users linked to this vendor account. Partner publication and operational controls are managed by an administrator.</p>
        </div>
        <div class="profile-section-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('vendor.profile.update') }}" data-profile-form>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="business_logo">Business logo</label>
                        <div class="profile-upload-row">
                            <div class="flex-grow-1">
                                <input id="business_logo" name="business_logo" type="file" class="form-control @error('business_logo') is-invalid @enderror" accept="image/jpeg,image/png,image/webp,image/gif" aria-describedby="logo-help">
                                <div id="logo-help" class="profile-help mt-1">Choose JPG, PNG, WebP, or GIF up to 5 MB. Your saved logo is used by your vendor panel.</div>
                                @error('business_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @if($vendor->business_logo_path)
                                <label class="form-check mb-0 text-nowrap">
                                    <input class="form-check-input" id="remove_logo" name="remove_logo" type="checkbox" value="1">
                                    <span class="form-check-label small">Remove logo</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="business_name">Business name <span class="text-danger">*</span></label>
                        <input id="business_name" name="business_name" class="form-control @error('business_name') is-invalid @enderror" value="{{ old('business_name', $vendor->business_name) }}" required maxlength="255" autocomplete="organization">
                        @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="legal_name">Legal name</label>
                        <input id="legal_name" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', $vendor->legal_name) }}" maxlength="255">
                        @error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Business email</label>
                        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $vendor->email) }}" required autocomplete="email">
                        <div class="profile-help mt-1">This is the shared business contact email; it does not change your personal login email.</div>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Business phone</label>
                        <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $vendor->phone) }}" maxlength="50" autocomplete="tel">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="district">District</label>
                        <input id="district" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district', $vendor->district) }}" maxlength="255">
                        @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="operating_location">Operating location</label>
                        <input id="operating_location" name="operating_location" class="form-control @error('operating_location') is-invalid @enderror" value="{{ old('operating_location', $vendor->operating_location) }}" maxlength="255">
                        @error('operating_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="website_url">Website URL</label>
                        <input id="website_url" name="website_url" type="url" class="form-control @error('website_url') is-invalid @enderror" value="{{ old('website_url', $vendor->website_url) }}" maxlength="2048" placeholder="https://">
                        @error('website_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="address_notes">Address notes</label>
                        <textarea id="address_notes" name="address_notes" class="form-control @error('address_notes') is-invalid @enderror" rows="3" maxlength="5000">{{ old('address_notes', $vendor->address_notes) }}</textarea>
                        @error('address_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="partner_description">Business description</label>
                        <textarea id="partner_description" name="partner_description" class="form-control @error('partner_description') is-invalid @enderror" rows="3" maxlength="500">{{ old('partner_description', $vendor->partner_description) }}</textarea>
                        <div class="profile-help mt-1">A public-facing description. An administrator controls whether partner information is published.</div>
                        @error('partner_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="profile-actions">
                    <a class="btn btn-outline-secondary" href="{{ route('vendor.dashboard') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit" data-submit-label="Save Changes">Save Changes</button>
                </div>
            </form>
        </div>
    </section>

    <section class="profile-section" aria-labelledby="password-security-heading">
        <div class="profile-section-heading">
            <h2 id="password-security-heading">Password &amp; Security</h2>
            <p>Change the password for your individual login account. Other users linked to this business are not affected.</p>
        </div>
        <div class="profile-section-body">
            <form method="POST" action="{{ route('vendor.profile.password') }}" data-profile-form>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="login_email">Personal login email</label>
                        <input id="login_email" class="form-control" value="{{ $user->email }}" readonly>
                        <div class="profile-help mt-1">Change your sign-in email through the account verification process.</div>
                    </div>
                    @foreach([
                        'current_password' => ['Current password', 'current-password'],
                        'password' => ['New password', 'new-password'],
                        'password_confirmation' => ['Confirm new password', 'new-password'],
                    ] as $field => [$label, $autocomplete])
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <div class="profile-password-input">
                                <input id="{{ $field }}" name="{{ $field }}" type="password" class="form-control @error($field, 'updatePassword') is-invalid @enderror" autocomplete="{{ $autocomplete }}" required>
                                <button class="profile-password-toggle" type="button" data-toggle-password="{{ $field }}" aria-label="Show {{ strtolower($label) }}" aria-pressed="false">Show</button>
                            </div>
                            @error($field, 'updatePassword')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                    @if($errors->updatePassword->any())
                        <div class="col-12" role="alert">
                            @foreach($errors->updatePassword->all() as $error)<div class="text-danger small">{{ $error }}</div>@endforeach
                        </div>
                    @endif
                </div>
                <div class="profile-actions">
                    <button class="btn btn-primary" type="submit" data-submit-label="Update Password">Update Password</button>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
    (() => {
        const imageInput = document.getElementById('business_logo');
        const preview = document.getElementById('business-logo-preview');
        const fallback = document.getElementById('business-logo-fallback');
        const removeLogo = document.getElementById('remove_logo');
        let previewUrl;

        imageInput?.addEventListener('change', () => {
            const file = imageInput.files?.[0];
            if (!file) return;
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
            preview.alt = 'Selected business logo preview';
            preview.hidden = false;
            fallback.hidden = true;
            if (removeLogo) removeLogo.checked = false;
        });

        removeLogo?.addEventListener('change', () => {
            if (removeLogo.checked && !window.confirm('Remove the saved business logo?')) {
                removeLogo.checked = false;
            }
        });

        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.togglePassword);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.textContent = show ? 'Hide' : 'Show';
                button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} ${input.labels[0].textContent.toLowerCase()}`);
                button.setAttribute('aria-pressed', String(show));
            });
        });

        document.querySelectorAll('[data-profile-form]').forEach((form) => {
            let changed = false;
            form.addEventListener('input', () => { changed = true; });
            form.addEventListener('change', () => { changed = true; });
            form.addEventListener('submit', () => {
                changed = false;
                const button = form.querySelector('[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Saving…';
                }
            });
            window.addEventListener('beforeunload', (event) => {
                if (changed) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
        });
    })();
</script>
@endsection
