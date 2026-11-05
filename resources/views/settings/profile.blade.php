@extends('front.layouts.admin')
@section('title', 'Profile & Password — Settings')
@section('page-title', 'Profile & Password')

@push('styles')
    @include('settings._styles')
@endpush

@section('content')
<div class="settings-page py-3">

    {{-- Back to Settings --}}
    <a href="{{ route('admin.settings.index') }}" class="settings-back-link">
        <i class="bi bi-arrow-left"></i> Back to Settings
    </a>

    {{-- Page Header --}}
    <div class="settings-header">
        <h1 class="settings-title">Profile & Password</h1>
        <p class="settings-subtitle">Manage your administrator profile details and account password.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    {{-- Card 1: Administrator Profile --}}
    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Administrator Profile</h2>
            <p class="settings-card-desc">Update your personal account information and contact email.</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Profile Photo Preview (if available) --}}
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ $user->profile_photo_url }}" 
                     alt="{{ $user->name }}" 
                     class="rounded-circle border" 
                     style="width: 64px; height: 64px; object-fit: cover;">
                <div>
                    <label for="photo" class="form-label small fw-semibold text-dark mb-1">Profile Photo</label>
                    <input type="file" name="photo" id="photo" class="form-control form-control-sm" accept="image/*">
                    @if($user->profile_photo_path)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_photo" value="1" id="removePhoto">
                            <label class="form-check-label small text-muted" for="removePhoto">
                                Remove current profile photo
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-semibold text-dark">Full Name <span class="text-danger">*</span></label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $user->name) }}" 
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label small fw-semibold text-dark">Email Address <span class="text-danger">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           value="{{ old('email', $user->email) }}" 
                           required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-semibold text-dark">Phone Number</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-control @error('phone') is-invalid @enderror" 
                           value="{{ old('phone', $user->phone) }}" 
                           placeholder="+250 ...">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4 pt-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Card 2: Change Password --}}
    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Change Password</h2>
            <p class="settings-card-desc">Ensure your account is using a secure, long password to stay protected.</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.password.update') }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-12">
                    <label for="current_password" class="form-label small fw-semibold text-dark">Current Password <span class="text-danger">*</span></label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           class="form-control @error('current_password') is-invalid @enderror" 
                           required 
                           autocomplete="current-password">
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label small fw-semibold text-dark">New Password <span class="text-danger">*</span></label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           required 
                           autocomplete="new-password">
                    <div class="form-text small text-muted">Minimum 8 characters.</div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label small fw-semibold text-dark">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           class="form-control" 
                           required 
                           autocomplete="new-password">
                </div>
            </div>

            <div class="mt-4 pt-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                    Update Password
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

