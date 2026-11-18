@extends('front.layouts.admin')
@section('page-title', 'Create New User')
@section('content')
<style>
    .create-user-page { color: #172033; max-width: 1040px; }
    .create-user-breadcrumb { color: #64748b; font-size: .8rem; margin-bottom: .45rem; }
    .create-user-breadcrumb a { color: #475569; text-decoration: none; }
    .create-user-breadcrumb a:hover { color: #1d4ed8; }
    .create-user-heading { margin-bottom: 1.15rem; }
    .create-user-heading h1 { font-size: 1.45rem; font-weight: 700; margin: 0; }
    .create-user-heading p { color: #64748b; font-size: .9rem; margin: .3rem 0 0; }
    .create-user-form { display: grid; gap: .9rem; }
    .create-user-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.1rem 1.2rem; }
    .create-user-section-heading { align-items: flex-start; display: flex; gap: .7rem; margin-bottom: 1rem; }
    .create-user-section-icon { align-items: center; background: #eff6ff; border-radius: 7px; color: #2563eb; display: inline-flex; flex: 0 0 32px; height: 32px; justify-content: center; width: 32px; }
    .create-user-section h2 { color: #172033; font-size: .98rem; font-weight: 700; margin: 0; }
    .create-user-section-heading p { color: #64748b; font-size: .8rem; margin: .18rem 0 0; }
    .create-user-fields { display: grid; gap: .85rem 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .create-user-field { min-width: 0; }
    .create-user-field label { color: #334155; display: block; font-size: .82rem; font-weight: 600; margin-bottom: .35rem; }
    .create-user-field .form-control { border-color: #cbd5e1; border-radius: 7px; font-size: .88rem; min-height: 40px; }
    .create-user-field .form-control:focus { border-color: #60a5fa; box-shadow: 0 0 0 .18rem rgba(37, 99, 235, .12); }
    .create-user-field-full { grid-column: 1 / -1; max-width: calc(50% - .5rem); }
    .create-user-password-wrap { position: relative; }
    .create-user-password-wrap .form-control { padding-right: 2.8rem; }
    .create-user-password-toggle { align-items: center; background: transparent; border: 0; color: #64748b; display: inline-flex; height: 36px; justify-content: center; padding: 0; position: absolute; right: .45rem; top: 2px; width: 32px; }
    .create-user-password-toggle:hover { color: #1d4ed8; }
    .create-user-helper { color: #64748b; font-size: .76rem; margin: .65rem 0 0; }
    .create-user-role-grid { display: grid; gap: .65rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .create-user-role-card { align-items: flex-start; background: #fff; border: 1px solid #dbe2ea; border-radius: 8px; cursor: pointer; display: flex; gap: .7rem; min-height: 78px; padding: .7rem .75rem; transition: background-color .15s ease, border-color .15s ease; }
    .create-user-role-card:hover { background: #f8fafc; border-color: #94a3b8; }
    .create-user-role-card:has(input:checked) { background: #f0fdf4; border-color: #86c99b; }
    .create-user-role-card:focus-within { outline: 3px solid rgba(37, 99, 235, .18); outline-offset: 1px; }
    .create-user-role-icon { align-items: center; background: #f1f5f9; border-radius: 6px; color: #475569; display: inline-flex; flex: 0 0 30px; height: 30px; justify-content: center; }
    .create-user-role-card:has(input:checked) .create-user-role-icon { background: #dcfce7; color: #15803d; }
    .create-user-role-content { flex: 1; min-width: 0; }
    .create-user-role-name { color: #1e293b; display: block; font-size: .84rem; font-weight: 600; line-height: 1.3; }
    .create-user-role-description { color: #64748b; display: block; font-size: .75rem; line-height: 1.35; margin-top: .2rem; }
    .create-user-role-check { accent-color: #16a34a; flex: 0 0 auto; height: 16px; margin: .1rem 0 0 auto; width: 16px; }
    .create-user-actions { display: flex; gap: .6rem; justify-content: flex-end; padding-top: .15rem; }
    .create-user-actions .btn { border-radius: 7px; min-height: 38px; padding-left: .9rem; padding-right: .9rem; }
    @media (max-width: 767.98px) {
        .create-user-page { max-width: 620px; }
        .create-user-section { padding: .95rem; }
        .create-user-fields, .create-user-role-grid { grid-template-columns: minmax(0, 1fr); }
        .create-user-field-full { grid-column: auto; max-width: none; }
    }
    @media (max-width: 420px) {
        .create-user-page { padding-left: .85rem; padding-right: .85rem; }
        .create-user-heading h1 { font-size: 1.25rem; }
        .create-user-section { padding: .8rem; }
        .create-user-section-heading { gap: .55rem; }
        .create-user-actions { flex-direction: column-reverse; }
        .create-user-actions .btn { width: 100%; }
    }
</style>

<div class="container-fluid py-4 create-user-page">
    @include('marketplace.partials.flash')

    <div class="create-user-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('marketplace.users.index') }}">Users</a>
        <span aria-hidden="true"> &gt; </span>
        <span aria-current="page">Create New User</span>
    </div>
    <header class="create-user-heading">
        <h1>Create New User</h1>
        <p>Add a new account and configure its system access.</p>
    </header>

    <form method="POST" action="{{ route('marketplace.users.store') }}" class="create-user-form" id="create-user-form">
        @csrf

        <section class="create-user-section" aria-labelledby="personal-information-heading">
            <div class="create-user-section-heading">
                <span class="create-user-section-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                <div>
                    <h2 id="personal-information-heading">01. Personal Information</h2>
                    <p>Enter the user's basic account details.</p>
                </div>
            </div>
            <div class="create-user-fields">
                <div class="create-user-field">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" required maxlength="255" autocomplete="name" value="{{ old('name') }}">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="create-user-field">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" required maxlength="255" autocomplete="email" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="create-user-field create-user-field-full">
                    <label for="phone">Phone Number</label>
                    <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" maxlength="50" autocomplete="tel" value="{{ old('phone') }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="create-user-section" aria-labelledby="account-security-heading">
            <div class="create-user-section-heading">
                <span class="create-user-section-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
                <div>
                    <h2 id="account-security-heading">02. Account Security</h2>
                    <p>Set up login credentials for the new account.</p>
                </div>
            </div>
            <div class="create-user-fields">
                <div class="create-user-field">
                    <label for="password">Password</label>
                    <div class="create-user-password-wrap">
                        <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                        <button class="create-user-password-toggle" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="create-user-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="create-user-password-wrap">
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="8" autocomplete="new-password">
                        <button class="create-user-password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show confirm password" aria-pressed="false">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
            <p class="create-user-helper">Password requirements and validation follow the existing account security rules.</p>
        </section>

        <section class="create-user-section" aria-labelledby="assign-roles-heading">
            <div class="create-user-section-heading">
                <span class="create-user-section-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                <div>
                    <h2 id="assign-roles-heading">03. Assign Roles</h2>
                    <p>Choose the access roles for this user.</p>
                </div>
            </div>
            @error('roles')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
            @error('roles.*')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

            @php
                $rolePresentation = [
                    'administrator' => ['Administrator', 'Full administrative access.', 'bi-person-gear'],
                    'customer' => ['Customer', 'Customer account access.', 'bi-person'],
                    'delivery-officer' => ['Delivery Officer', 'Delivery management access.', 'bi-truck'],
                    'inventory-officer' => ['Inventory Officer', 'Inventory management access.', 'bi-box-seam'],
                    'operations-manager' => ['Operations Manager', 'Operations management access.', 'bi-diagram-3'],
                    'product-manager' => ['Product Manager', 'Product management access.', 'bi-boxes'],
                    'request-officer' => ['Request and Order Officer', 'Request and order management access.', 'bi-clipboard-check'],
                ];
                $formRoles = $roles->filter(fn ($role) => isset($rolePresentation[$role->slug]));
            @endphp

            <div class="create-user-role-grid">
                @foreach($formRoles as $role)
                    @php([$roleName, $roleDescription, $roleIcon] = $rolePresentation[$role->slug])
                    <label class="create-user-role-card" for="role-{{ $role->id }}">
                        <span class="create-user-role-icon" aria-hidden="true"><i class="bi {{ $roleIcon }}"></i></span>
                        <span class="create-user-role-content">
                            <span class="create-user-role-name">{{ $roleName }}</span>
                            <span class="create-user-role-description">{{ $roleDescription }}</span>
                        </span>
                        <input id="role-{{ $role->id }}" class="create-user-role-check @error('roles') is-invalid @enderror" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', [])))>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="create-user-actions">
            <a href="{{ route('marketplace.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="create-user-submit">
                <i class="bi bi-person-plus" aria-hidden="true"></i> Create User
            </button>
        </div>
    </form>
</div>

<script>
    (() => {
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(showing));
                button.setAttribute('aria-label', (showing ? 'Hide ' : 'Show ') + (input.id === 'password' ? 'password' : 'confirm password'));
                button.querySelector('i').className = showing ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });

        document.getElementById('create-user-form').addEventListener('submit', (event) => {
            const form = event.currentTarget;
            if (!form.reportValidity()) {
                event.preventDefault();
                return;
            }
            const button = document.getElementById('create-user-submit');
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Creating User...';
        });
    })();
</script>
@endsection
