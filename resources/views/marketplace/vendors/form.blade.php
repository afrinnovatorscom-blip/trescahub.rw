@extends('front.layouts.admin')
@section('page-title', $vendor->exists ? 'Edit vendor' : 'Register vendor')
@section('content')
@php
    $account = $account ?? null;
    $passwordRequired = ! $vendor->exists;
    $certificateExists = filled($vendor->registration_certificate_path)
        && \Illuminate\Support\Facades\Storage::disk('local')->exists($vendor->registration_certificate_path);
    $logoPath = ltrim((string) $vendor->business_logo_path, '/');
    $logoExists = $logoPath !== ''
        && ! str_contains($logoPath, '..')
        && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$logoPath));
    $knownStatuses = ['pending', 'active', 'suspended', 'inactive'];
@endphp
<div class="container-fluid py-4 vendor-admin-form-page">
    @include('marketplace.partials.flash')

    <header class="vendor-admin-form-header">
        <div>
            <p class="vendor-admin-eyebrow mb-1">Vendor Management</p>
            <h1>{{ $vendor->exists ? 'Edit Vendor' : 'Create Vendor' }}</h1>
            <p class="mb-0">Manage account access, business details, payment information, and marketplace settings.</p>
        </div>
    </header>

    @if($errors->any())
        <div class="alert alert-danger mt-3" role="alert">
            <p class="fw-semibold mb-1">Please correct the highlighted information.</p>
            <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $vendor->exists ? route('marketplace.vendors.update', $vendor) : route('marketplace.vendors.store') }}"
          class="vendor-admin-form">
        @csrf
        @if($vendor->exists) @method('PUT') @endif

        <section class="vendor-admin-section" aria-labelledby="account-owner-heading">
            <div class="vendor-admin-section-heading">
                <span class="vendor-admin-section-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h2 id="account-owner-heading">Account Owner Information</h2>
                    <p>These details identify the person and credentials connected to the vendor account.</p>
                </div>
            </div>
            @if($vendor->exists && $account)
                <p class="vendor-admin-help">Leave both password fields blank to keep the current password.</p>
            @elseif($vendor->exists)
                <p class="vendor-admin-help">No linked login account is on file. Set a password to create one, or leave it blank to save the vendor profile only.</p>
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="contact_person">Contact Person Full Name <span class="text-danger">*</span></label>
                    <input id="contact_person" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror"
                           value="{{ old('contact_person', $vendor->contact_person ?: ($account?->name ?? $vendor->business_name)) }}" required autocomplete="name">
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="account_email">Account Email <span class="text-danger">*</span></label>
                    <input id="account_email" name="account_email" type="email" class="form-control @error('account_email') is-invalid @enderror"
                           value="{{ old('account_email', $account?->email ?? $vendor->email) }}" required autocomplete="email">
                    @error('account_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password">Password @if($passwordRequired)<span class="text-danger">*</span>@endif</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror"
                           @if($passwordRequired) required @endif autocomplete="new-password">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">{{ $passwordRequired ? 'Use at least 8 characters.' : 'Optional; leave blank to keep the existing password.' }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirmation">Confirm Password @if($passwordRequired)<span class="text-danger">*</span>@endif</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                           @if($passwordRequired) required @endif autocomplete="new-password">
                    @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="vendor-admin-section" aria-labelledby="business-information-heading">
            <div class="vendor-admin-section-heading">
                <span class="vendor-admin-section-icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                <div>
                    <h2 id="business-information-heading">Business Information</h2>
                    <p>Keep business contact and registration details separate from the account owner’s login information.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="business_name">Business / Company Name <span class="text-danger">*</span></label>
                    <input id="business_name" name="business_name" class="form-control @error('business_name') is-invalid @enderror"
                           value="{{ old('business_name', $vendor->business_name) }}" required autocomplete="organization">
                    @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="legal_name">Legal Business Name</label>
                    <input id="legal_name" name="legal_name" class="form-control @error('legal_name') is-invalid @enderror"
                           value="{{ old('legal_name', $vendor->legal_name) }}">
                    @error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tin">TIN (Tax Identification Number) @unless($vendor->exists)<span class="text-danger">*</span>@endunless</label>
                    <input id="tin" name="tin" class="form-control @error('tin') is-invalid @enderror"
                           value="{{ old('tin', $vendor->tin) }}" @unless($vendor->exists)required @endunless>
                    @error('tin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="business_phone">Business Phone @unless($vendor->exists)<span class="text-danger">*</span>@endunless</label>
                    <input id="business_phone" name="business_phone" type="tel" class="form-control @error('business_phone') is-invalid @enderror"
                           value="{{ old('business_phone', $vendor->phone) }}" autocomplete="tel" @unless($vendor->exists)required @endunless>
                    @error('business_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="business_email">Business Email @unless($vendor->exists)<span class="text-danger">*</span>@endunless</label>
                    <input id="business_email" name="business_email" type="email" class="form-control @error('business_email') is-invalid @enderror"
                           value="{{ old('business_email', $vendor->business_email) }}" autocomplete="organization" @unless($vendor->exists)required @endunless>
                    @error('business_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="business_address">Business Address @unless($vendor->exists)<span class="text-danger">*</span>@endunless</label>
                    <textarea id="business_address" name="business_address" rows="3" class="form-control @error('business_address') is-invalid @enderror"
                              autocomplete="street-address" @unless($vendor->exists)required @endunless>{{ old('business_address', $vendor->business_address) }}</textarea>
                    @error('business_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="business_logo">Business Logo</label>
                    <div class="vendor-admin-upload-row">
                        <div class="vendor-admin-logo-preview" aria-label="Business logo preview">
                            @if($logoExists)
                                <img id="business-logo-preview" src="{{ asset('assets/'.$logoPath) }}" alt="{{ $vendor->business_name }} logo">
                            @else
                                <span id="business-logo-placeholder" aria-hidden="true"><i class="bi bi-building"></i></span>
                                <img id="business-logo-preview" src="" alt="Business logo preview" hidden>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <input id="business_logo" name="business_logo" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                   class="form-control @error('business_logo') is-invalid @enderror">
                            <div class="form-text">JPG, PNG, WEBP, or GIF; maximum 5 MB. Leave blank to keep the current logo.</div>
                            @error('business_logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @if($vendor->business_logo_path)
                                <label class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                    <span class="form-check-label">Remove current logo</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="vendor-admin-section" aria-labelledby="business-documents-heading">
            <div class="vendor-admin-section-heading">
                <span class="vendor-admin-section-icon" aria-hidden="true"><i class="bi bi-file-earmark-arrow-up"></i></span>
                <div>
                    <h2 id="business-documents-heading">Business Documents</h2>
                    <p>RDB registration documents are stored privately and are only available to authorized administrators.</p>
                </div>
            </div>
            @if($certificateExists)
                <div class="vendor-admin-document-row">
                    <span class="vendor-admin-document-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                    <div class="flex-grow-1">
                        <strong>RDB Business Registration Certificate</strong>
                        <span class="d-block text-muted small">Current document on file</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('marketplace.vendor-applications.certificate', ['vendor' => $vendor, 'view' => 1]) }}" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                        </a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('marketplace.vendor-applications.certificate', $vendor) }}">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>Download
                        </a>
                    </div>
                </div>
            @elseif($vendor->exists)
                <p class="vendor-admin-help">No registration certificate is on file.</p>
            @endif
            <div class="mt-3">
                <label class="form-label" for="rdb_certificate">
                    {{ $certificateExists ? 'Replace RDB Business Registration Certificate' : 'RDB Business Registration Certificate' }}
                    @unless($vendor->exists)<span class="text-danger">*</span>@endunless
                </label>
                <input id="rdb_certificate" name="rdb_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                       class="form-control @error('rdb_certificate') is-invalid @enderror" @unless($vendor->exists)required @endunless>
                <div class="form-text">PDF, JPG, or PNG; maximum 5 MB. Leave blank to retain the current document.</div>
                @error('rdb_certificate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @if($certificateExists)
                    <label class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_certificate" value="1" @checked(old('remove_certificate'))>
                        <span class="form-check-label">Remove current certificate</span>
                    </label>
                @endif
            </div>
        </section>

        <section class="vendor-admin-section" aria-labelledby="payment-information-heading">
            <div class="vendor-admin-section-heading">
                <span class="vendor-admin-section-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                <div>
                    <h2 id="payment-information-heading">Payment Information</h2>
                    <p>Enter complete bank details, Mobile Money details, or both. Payment account values are encrypted at rest.</p>
                </div>
            </div>
            @if($vendor->exists)
                <p class="vendor-admin-help">Leave both methods unchanged to retain current payment details. Complete all fields for any method you update.</p>
            @endif
            <div class="row g-3">
                <fieldset class="col-lg-6">
                    <div class="vendor-admin-payment-method">
                        <legend><i class="bi bi-bank" aria-hidden="true"></i> Bank Account</legend>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="bank_name">Bank Name</label>
                                <input id="bank_name" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name', $vendor->bank_name) }}">
                                @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="bank_account_holder">Account Holder Name</label>
                                <input id="bank_account_holder" name="bank_account_holder" class="form-control @error('bank_account_holder') is-invalid @enderror" value="{{ old('bank_account_holder', $vendor->bank_account_holder) }}">
                                @error('bank_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="bank_account_number">Account Number</label>
                                <input id="bank_account_number" name="bank_account_number" class="form-control @error('bank_account_number') is-invalid @enderror" value="{{ old('bank_account_number', $vendor->bank_account_number) }}" inputmode="numeric">
                                @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </fieldset>
                <fieldset class="col-lg-6">
                    <div class="vendor-admin-payment-method">
                        <legend><i class="bi bi-phone" aria-hidden="true"></i> Mobile Money</legend>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="mobile_money_provider">Provider</label>
                                <select id="mobile_money_provider" name="mobile_money_provider" class="form-select @error('mobile_money_provider') is-invalid @enderror">
                                    <option value="">Select a provider</option>
                                    <option value="mtn_momo" @selected(old('mobile_money_provider', $vendor->mobile_money_provider) === 'mtn_momo')>MTN MoMo</option>
                                    <option value="airtel_money" @selected(old('mobile_money_provider', $vendor->mobile_money_provider) === 'airtel_money')>Airtel Money</option>
                                </select>
                                @error('mobile_money_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="mobile_money_phone">Registered Phone Number</label>
                                <input id="mobile_money_phone" name="mobile_money_phone" type="tel" class="form-control @error('mobile_money_phone') is-invalid @enderror" value="{{ old('mobile_money_phone', $vendor->mobile_money_phone) }}">
                                @error('mobile_money_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="mobile_money_account_holder">Account Holder Name</label>
                                <input id="mobile_money_account_holder" name="mobile_money_account_holder" class="form-control @error('mobile_money_account_holder') is-invalid @enderror" value="{{ old('mobile_money_account_holder', $vendor->mobile_money_account_holder) }}">
                                @error('mobile_money_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </section>

        <section class="vendor-admin-section" aria-labelledby="marketplace-settings-heading">
            <div class="vendor-admin-section-heading">
                <span class="vendor-admin-section-icon" aria-hidden="true"><i class="bi bi-shop-window"></i></span>
                <div>
                    <h2 id="marketplace-settings-heading">Vendor and Marketplace Settings</h2>
                    <p>Control operational access and the vendor’s public partner presentation.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="status">Vendor Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                        @if($vendor->exists && ! in_array($vendor->status, $knownStatuses, true))
                            <option value="{{ $vendor->status }}" @selected(old('status', $vendor->status) === $vendor->status)>{{ $vendor->statusLabel() }}</option>
                        @endif
                        @foreach($knownStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $vendor->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <label class="form-check vendor-admin-partner-toggle">
                        <input class="form-check-input" type="checkbox" name="show_as_partner" value="1" @checked(old('show_as_partner', $vendor->show_as_partner))>
                        <span class="form-check-label">
                            <strong>Show as Partner on Home Page</strong>
                            <span class="d-block text-muted small">Publication also requires an active vendor and approved branding.</span>
                        </span>
                    </label>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="partner_description">Short Partner Description</label>
                    <textarea id="partner_description" name="partner_description" class="form-control @error('partner_description') is-invalid @enderror" rows="3" maxlength="500">{{ old('partner_description', $vendor->partner_description) }}</textarea>
                    @error('partner_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="display_order">Display Order <span class="text-danger">*</span></label>
                    <input id="display_order" name="display_order" type="number" min="0" step="1" class="form-control @error('display_order') is-invalid @enderror" required value="{{ old('display_order', $vendor->display_order ?? 0) }}">
                    @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Lower numbers appear first among vendors.</div>
                </div>
                @if($vendor->exists)
                    <div class="col-12">
                        <div class="vendor-admin-setting-note">
                            <span class="form-label d-block mb-1">Website Publication</span>
                            <span>{{ $vendor->websitePublicationLabel() }}. Use the publication action on the vendor profile to publish or remove this vendor from the website.</span>
                        </div>
                    </div>
                @endif
                @if($vendor->exists)
                    <div class="col-12">
                        <div class="vendor-admin-setting-note">
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="public_branding_approved" value="1" @checked(old('public_branding_approved', $vendor->public_branding_approved))>
                                <span class="form-check-label"><strong>Approve this business name and logo for public display</strong></span>
                            </label>
                            <span class="d-block mt-1">Changing either one resets this approval; website publication remains a separate action.</span>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="vendor-admin-section" aria-labelledby="additional-information-heading">
            <div class="vendor-admin-section-heading">
                <span class="vendor-admin-section-icon" aria-hidden="true"><i class="bi bi-info-circle"></i></span>
                <div>
                    <h2 id="additional-information-heading">Additional Information</h2>
                    <p>Operational and internal details used by the existing vendor management tools.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="district">District</label>
                    <input id="district" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district', $vendor->district) }}">
                    @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="operating_location">Operating Location</label>
                    <input id="operating_location" name="operating_location" class="form-control @error('operating_location') is-invalid @enderror" value="{{ old('operating_location', $vendor->operating_location) }}">
                    @error('operating_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="website_url">Website URL</label>
                    <input id="website_url" name="website_url" type="url" class="form-control @error('website_url') is-invalid @enderror" value="{{ old('website_url', $vendor->website_url) }}" placeholder="https://">
                    @error('website_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="address_notes">Address Notes / Directions</label>
                    <textarea id="address_notes" name="address_notes" class="form-control @error('address_notes') is-invalid @enderror" rows="2">{{ old('address_notes', $vendor->address_notes) }}</textarea>
                    @error('address_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="internal_notes">Internal Notes</label>
                    <textarea id="internal_notes" name="internal_notes" class="form-control @error('internal_notes') is-invalid @enderror" rows="3">{{ old('internal_notes', $vendor->internal_notes) }}</textarea>
                    @error('internal_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <div class="vendor-admin-form-actions">
            <a class="btn btn-outline-secondary" href="{{ $vendor->exists ? route('marketplace.vendors.show', $vendor) : route('marketplace.vendors.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>{{ $vendor->exists ? 'Save Vendor Changes' : 'Create Vendor' }}</button>
        </div>
    </form>
</div>
@push('styles')
<style>
    .vendor-admin-form-page { max-width: 1240px; color: #0f172a; }
    .vendor-admin-form-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.25rem; }
    .vendor-admin-form-header h1 { margin:0 0 .4rem; color:#0f172a; font-size:clamp(1.45rem,2.4vw,1.9rem); font-weight:700; letter-spacing:-.025em; }
    .vendor-admin-form-header p:last-child { color:#64748b; font-size:.94rem; }
    .vendor-admin-eyebrow { color:#2563eb; font-size:.75rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .vendor-admin-form { display:flex; flex-direction:column; gap:1rem; }
    .vendor-admin-section { padding:1.35rem 1.5rem 1.5rem; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:0 2px 6px rgba(15,23,42,.035); }
    .vendor-admin-section-heading { display:flex; align-items:flex-start; gap:.85rem; margin-bottom:1.25rem; padding-bottom:.95rem; border-bottom:1px solid #edf1f5; }
    .vendor-admin-section-heading h2 { margin:0 0 .25rem; color:#0f172a; font-size:1.05rem; font-weight:700; }
    .vendor-admin-section-heading p { margin:0; color:#64748b; font-size:.86rem; line-height:1.45; }
    .vendor-admin-section-icon { display:flex; width:38px; height:38px; flex:0 0 38px; align-items:center; justify-content:center; border-radius:10px; background:#eff6ff; color:#2563eb; font-size:1.05rem; }
    .vendor-admin-form .form-label { margin-bottom:.4rem; color:#334155; font-size:.86rem; font-weight:600; }
    .vendor-admin-form .form-control,.vendor-admin-form .form-select { min-height:42px; border-color:#cbd5e1; border-radius:8px; }
    .vendor-admin-form textarea.form-control { min-height:auto; }
    .vendor-admin-form .form-control:focus,.vendor-admin-form .form-select:focus { border-color:#2563eb; box-shadow:0 0 0 .2rem rgba(37,99,235,.14); }
    .vendor-admin-form .form-text,.vendor-admin-help { color:#64748b; font-size:.8rem; line-height:1.45; }
    .vendor-admin-help { margin:-.5rem 0 1rem; }
    .vendor-admin-upload-row { display:flex; align-items:center; gap:1rem; }
    .vendor-admin-logo-preview { display:flex; width:86px; height:86px; flex:0 0 86px; align-items:center; justify-content:center; overflow:hidden; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; color:#94a3b8; font-size:1.6rem; }
    .vendor-admin-logo-preview img { width:100%; height:100%; object-fit:contain; }
    .vendor-admin-document-row { display:flex; align-items:center; gap:.9rem; padding:.9rem 1rem; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; }
    .vendor-admin-document-icon { display:flex; width:38px; height:38px; flex:0 0 38px; align-items:center; justify-content:center; border-radius:9px; background:#eff6ff; color:#2563eb; font-size:1.1rem; }
    .vendor-admin-payment-method { height:100%; padding:1rem; border:1px solid #e2e8f0; border-radius:10px; background:#fbfdff; }
    .vendor-admin-payment-method legend { float:none; width:auto; margin:0 0 .9rem; color:#0f172a; font-size:.94rem; font-weight:700; }
    .vendor-admin-payment-method legend i { margin-right:.35rem; color:#2563eb; }
    .vendor-admin-partner-toggle { display:flex; align-items:flex-start; gap:.55rem; width:100%; min-height:42px; padding:.75rem .9rem; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; }
    .vendor-admin-partner-toggle .form-check-input { margin-top:.22rem; }
    .vendor-admin-setting-note { padding:.85rem 1rem; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc; color:#64748b; font-size:.82rem; }
    .vendor-admin-form-actions { display:flex; justify-content:flex-end; gap:.6rem; padding:.2rem 0 .6rem; }
    .vendor-admin-form-actions .btn { min-height:42px; padding:.55rem 1rem; border-radius:8px; font-weight:600; }
    .vendor-admin-form-actions .btn-primary { border-color:#2563eb; background:#2563eb; }
    .vendor-admin-form-actions .btn-primary:hover { border-color:#1d4ed8; background:#1d4ed8; }
    .vendor-admin-form :focus-visible { outline:2px solid rgba(37,99,235,.55); outline-offset:2px; }
    @media(max-width:767.98px) { .vendor-admin-section { padding:1.1rem; } .vendor-admin-upload-row { align-items:flex-start; } }
    @media(max-width:575.98px) { .vendor-admin-form-actions { flex-direction:column-reverse; } .vendor-admin-form-actions .btn { width:100%; } .vendor-admin-document-row { flex-wrap:wrap; } }
</style>
@endpush
@push('scripts')
<script>
    document.getElementById('business_logo')?.addEventListener('change', function () {
        const preview = document.getElementById('business-logo-preview');
        const placeholder = document.getElementById('business-logo-placeholder');
        const file = this.files && this.files[0];
        if (!file || !preview) return;
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        if (placeholder) placeholder.hidden = true;
        const removeLogo = document.querySelector('input[name="remove_logo"]');
        if (removeLogo) removeLogo.checked = false;
    });
</script>
@endpush
@endsection
