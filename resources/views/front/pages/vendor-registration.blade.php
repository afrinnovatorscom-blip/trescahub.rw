@extends('front.layouts.guest')

@section('content')
@php
    $correctionMode = $correctionMode ?? false;
    $vendor = $vendor ?? null;
    $value = fn (string $field, $default = '') => old($field, $default);
@endphp

<main class="vendor-registration-page">
    @if(filled($correctionNotice ?? null))
        <div class="vendor-correction-toast" id="vendor-correction-toast" role="status" aria-live="polite">
            <i class="vendor-correction-toast-icon bi bi-exclamation-circle" aria-hidden="true"></i>
            <span class="vendor-correction-toast-message"><strong>Requested correction:</strong> {{ $correctionNotice }}</span>
            <button type="button" class="vendor-correction-toast-close" aria-label="Dismiss notification">&times;</button>
        </div>
    @endif

    <div class="container">
        <div class="vendor-registration-heading">
            @include('front.partials.auth-brand')
            <h1>{{ $correctionMode ? 'Update Your Vendor Application' : 'Vendor Registration' }}</h1>
            <p>{{ $correctionMode ? 'Update the requested information and resubmit your application.' : 'Tell us about your business to apply to sell on TRESCA Hub.' }}</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger rounded-3 mb-4" role="alert">
                <p class="fw-semibold mb-2">Please correct the highlighted information.</p>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $correctionMode ? route('vendor.application.correction.update') : route('vendor.application.store') }}"
              enctype="multipart/form-data"
              class="vendor-registration-form">
            @csrf
            @if($correctionMode)
                @method('PUT')
            @endif

            <section class="vendor-form-section" aria-labelledby="account-information-heading">
                <div class="vendor-form-section-heading">
                    <span class="vendor-form-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
                    <div>
                        <h2 id="account-information-heading">Account Information</h2>
                        <p>{{ $correctionMode ? 'Your account sign-in email cannot be changed here.' : 'These credentials will be used to access your vendor account.' }}</p>
                    </div>
                </div>
                <div class="vendor-form-grid">
                    <div>
                        <label class="form-label" for="contact_person">Contact Person’s Full Name <span class="required-mark">*</span></label>
                        <input id="contact_person" name="contact_person" type="text" class="form-control @error('contact_person') is-invalid @enderror"
                               value="{{ $value('contact_person', $vendor?->contact_person ?? auth()->user()?->name ?? '') }}" required autocomplete="name">
                        @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if($correctionMode)
                        <div>
                            <label class="form-label" for="account_email">Account Email Address</label>
                            <input id="account_email" type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                        </div>
                    @else
                        <div>
                            <label class="form-label" for="email">Email Address <span class="required-mark">*</span></label>
                            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ $value('email') }}" required autocomplete="email">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="password">Password <span class="required-mark">*</span></label>
                            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="password_confirmation">Confirm Password <span class="required-mark">*</span></label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control"
                                   required autocomplete="new-password">
                        </div>
                    @endif
                </div>
            </section>

            <section class="vendor-form-section" aria-labelledby="business-information-heading">
                <div class="vendor-form-section-heading">
                    <span class="vendor-form-icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                    <div>
                        <h2 id="business-information-heading">Business Information</h2>
                        <p>Provide your registered business details.</p>
                    </div>
                </div>
                <div class="vendor-form-grid">
                    <div>
                        <label class="form-label" for="business_name">Business / Company Name <span class="required-mark">*</span></label>
                        <input id="business_name" name="business_name" type="text" class="form-control @error('business_name') is-invalid @enderror"
                               value="{{ $value('business_name', $vendor?->business_name ?? '') }}" required autocomplete="organization">
                        @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="tin">TIN (Tax Identification Number) <span class="required-mark">*</span></label>
                        <input id="tin" name="tin" type="text" class="form-control @error('tin') is-invalid @enderror"
                               value="{{ $value('tin', $vendor?->tin ?? '') }}" required>
                        @error('tin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="business_phone">Business Phone Number <span class="required-mark">*</span></label>
                        <input id="business_phone" name="business_phone" type="tel" class="form-control @error('business_phone') is-invalid @enderror"
                               value="{{ $value('business_phone', $vendor?->phone ?? '') }}" required autocomplete="tel">
                        @error('business_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="business_email">Business Email <span class="required-mark">*</span></label>
                        <input id="business_email" name="business_email" type="email" class="form-control @error('business_email') is-invalid @enderror"
                               value="{{ $value('business_email', $vendor?->business_email ?? $vendor?->email ?? '') }}" required autocomplete="organization">
                        @error('business_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="vendor-form-full">
                        <label class="form-label" for="business_logo">Business Logo</label>
                        <input id="business_logo" name="business_logo" type="file"
                               class="form-control @error('business_logo') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                        <div class="vendor-form-help">
                            @if($correctionMode && $vendor?->business_logo_path)
                                A business logo is already on file. Leave this blank to keep it, or upload a replacement.
                            @else
                                JPG, JPEG, or PNG only. Maximum file size: 5 MB.
                            @endif
                        </div>
                        @error('business_logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="vendor-form-full">
                        <label class="form-label" for="business_address">Business Address <span class="required-mark">*</span></label>
                        <textarea id="business_address" name="business_address" rows="3" class="form-control @error('business_address') is-invalid @enderror"
                                  required autocomplete="street-address">{{ $value('business_address', $vendor?->business_address ?? '') }}</textarea>
                        @error('business_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="vendor-form-section" aria-labelledby="business-documents-heading">
                <div class="vendor-form-section-heading">
                    <span class="vendor-form-icon" aria-hidden="true"><i class="bi bi-file-earmark-arrow-up"></i></span>
                    <div>
                        <h2 id="business-documents-heading">Business Documents</h2>
                        <p>Submit your official RDB business registration certificate.</p>
                    </div>
                </div>
                @if($correctionMode && $vendor?->registration_certificate_path)
                    <p class="vendor-form-help">A certificate is already on file. Leave this blank to keep it, or upload a replacement.</p>
                @endif
                <label class="form-label" for="rdb_certificate">
                    RDB Business Registration Certificate
                    @unless($correctionMode)<span class="required-mark">*</span>@endunless
                </label>
                <input id="rdb_certificate" name="rdb_certificate" type="file"
                       class="form-control @error('rdb_certificate') is-invalid @enderror"
                       accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                       @unless($correctionMode)required @endunless>
                <div class="vendor-form-help">PDF, JPG, or PNG only. Maximum file size: 5 MB. Your document is stored securely and is not publicly accessible.</div>
                @error('rdb_certificate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </section>

            <section class="vendor-form-section" aria-labelledby="payment-details-heading">
                <div class="vendor-form-section-heading">
                    <span class="vendor-form-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                    <div>
                        <h2 id="payment-details-heading">Payment Details</h2>
                        <p>These details help us identify where your business should receive payments. Complete bank details, Mobile Money details, or both.</p>
                    </div>
                </div>
                @if($correctionMode)
                    <p class="vendor-form-help mb-3">Leave both subsections blank to keep the current details. To change a method, complete all of its fields.</p>
                @endif
                <div class="vendor-payment-grid">
                    <fieldset class="vendor-payment-method">
                        <legend><i class="bi bi-bank" aria-hidden="true"></i> Bank Account</legend>
                        @if($correctionMode && filled($vendor?->bank_name))
                            <p class="vendor-form-help">Bank details are currently on file.</p>
                        @endif
                        <div class="vendor-form-grid">
                            <div>
                                <label class="form-label" for="bank_name">Bank Name</label>
                                <input id="bank_name" name="bank_name" type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                       value="{{ $value('bank_name') }}" autocomplete="off">
                                @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="bank_account_holder">Account Holder Name</label>
                                <input id="bank_account_holder" name="bank_account_holder" type="text" class="form-control @error('bank_account_holder') is-invalid @enderror"
                                       value="{{ $value('bank_account_holder') }}" autocomplete="off">
                                @error('bank_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="vendor-form-full">
                                <label class="form-label" for="bank_account_number">Account Number</label>
                                <input id="bank_account_number" name="bank_account_number" type="text" class="form-control @error('bank_account_number') is-invalid @enderror"
                                       value="{{ $value('bank_account_number') }}" inputmode="numeric" autocomplete="off">
                                @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="vendor-payment-method">
                        <legend><i class="bi bi-phone" aria-hidden="true"></i> Mobile Money</legend>
                        @if($correctionMode && filled($vendor?->mobile_money_provider))
                            <p class="vendor-form-help">Mobile Money details are currently on file.</p>
                        @endif
                        <div class="vendor-form-grid">
                            <div>
                                <label class="form-label" for="mobile_money_provider">Provider</label>
                                <select id="mobile_money_provider" name="mobile_money_provider" class="form-select @error('mobile_money_provider') is-invalid @enderror">
                                    <option value="">Select a provider</option>
                                    <option value="mtn_momo" @selected($value('mobile_money_provider', $vendor?->mobile_money_provider ?? '') === 'mtn_momo')>MTN MoMo</option>
                                    <option value="airtel_money" @selected($value('mobile_money_provider', $vendor?->mobile_money_provider ?? '') === 'airtel_money')>Airtel Money</option>
                                </select>
                                @error('mobile_money_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="mobile_money_phone">Registered Phone Number</label>
                                <input id="mobile_money_phone" name="mobile_money_phone" type="tel" class="form-control @error('mobile_money_phone') is-invalid @enderror"
                                       value="{{ $value('mobile_money_phone') }}" autocomplete="off">
                                @error('mobile_money_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="vendor-form-full">
                                <label class="form-label" for="mobile_money_account_holder">Account Holder Name</label>
                                <input id="mobile_money_account_holder" name="mobile_money_account_holder" type="text" class="form-control @error('mobile_money_account_holder') is-invalid @enderror"
                                       value="{{ $value('mobile_money_account_holder') }}" autocomplete="off">
                                @error('mobile_money_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </fieldset>
                </div>
            </section>

            <div class="vendor-registration-submit">
                <button type="submit" class="btn btn-success btn-lg">
                    {{ $correctionMode ? 'Resubmit Vendor Application' : 'Submit Vendor Application' }}
                </button>
                @unless($correctionMode)
                    <p class="text-muted small mt-3 mb-0">Already have an account? <a href="{{ route('login') }}" class="fw-semibold text-success text-decoration-none">Sign in</a></p>
                @endunless
            </div>
        </form>
    </div>
</main>

@push('styles')
<style>
    .vendor-correction-toast { position: fixed; z-index: 1090; top: 18px; right: 24px; display: flex; max-width: min(460px, calc(100vw - 32px)); align-items: flex-start; gap: .625rem; padding: .8rem .9rem; border: 1px solid #fde68a; border-radius: 8px; background: #fffbeb; box-shadow: 0 8px 24px rgba(15, 23, 42, .12); color: #92400e; font-size: .875rem; }
    .vendor-correction-toast[hidden] { display: none !important; }
    .vendor-correction-toast-icon { flex: 0 0 auto; margin-top: .1rem; }
    .vendor-correction-toast-message { min-width: 0; flex: 1; overflow-wrap: anywhere; }
    .vendor-correction-toast-close { border: 0; background: transparent; color: inherit; font-size: 1.25rem; line-height: 1; }
    .vendor-registration-page { min-height: 70vh; padding: 3.5rem 0 5rem; background: #f9f9f6; }
    .vendor-registration-page > .container { max-width: 900px; }
    .vendor-registration-heading { margin: 0 auto 2rem; text-align: center; }
    .vendor-registration-heading > * { margin-bottom: 1rem; }
    .vendor-registration-heading h1 { margin: .75rem 0 .5rem; color: #111827; font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.9rem, 5vw, 2.5rem); font-weight: 700; letter-spacing: -.04em; }
    .vendor-registration-heading p { max-width: 620px; margin: 0 auto; color: #6b7280; line-height: 1.65; }
    .vendor-registration-form { overflow: hidden; border: 1px solid #e5e7eb; border-radius: 18px; background: #fff; box-shadow: 0 8px 30px rgba(17,24,39,.06); }
    .vendor-form-section { padding: 1.75rem 2rem; border-bottom: 1px solid #e5e7eb; }
    .vendor-form-section-heading { display: flex; align-items: flex-start; gap: .9rem; margin-bottom: 1.5rem; }
    .vendor-form-section-heading h2 { margin: 0 0 .3rem; color: #111827; font-size: 1.2rem; font-weight: 700; }
    .vendor-form-section-heading p { margin: 0; color: #6b7280; font-size: .92rem; line-height: 1.5; }
    .vendor-form-icon { display: inline-flex; width: 42px; height: 42px; flex: 0 0 42px; align-items: center; justify-content: center; border-radius: 12px; background: #ecfdf5; color: #15803d; font-size: 1.15rem; }
    .vendor-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.1rem 1.25rem; }
    .vendor-form-full { grid-column: 1 / -1; }
    .vendor-form-section .form-label { margin-bottom: .45rem; color: #374151; font-size: .9rem; font-weight: 600; }
    .vendor-form-section .form-control, .vendor-form-section .form-select { min-height: 46px; border-color: #d1d5db; border-radius: 9px; }
    .vendor-form-section textarea.form-control { min-height: 100px; }
    .vendor-form-section .form-control:focus, .vendor-form-section .form-select:focus { border-color: #16a34a; box-shadow: 0 0 0 .2rem rgba(22,163,74,.15); }
    .required-mark { color: #b91c1c; }
    .vendor-form-help { margin: .5rem 0 0; color: #6b7280; font-size: .83rem; line-height: 1.5; }
    .vendor-payment-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .vendor-payment-method { min-width: 0; padding: 1.2rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #fbfdfb; }
    .vendor-payment-method legend { float: none; width: auto; margin: 0 0 1rem; color: #111827; font-size: 1rem; font-weight: 700; }
    .vendor-payment-method legend i { margin-right: .4rem; color: #15803d; }
    .vendor-payment-method .vendor-form-grid { grid-template-columns: 1fr; gap: .9rem; }
    .vendor-payment-method .vendor-form-full { grid-column: auto; }
    .vendor-registration-submit { padding: 1.75rem 2rem 2rem; text-align: center; }
    .vendor-registration-submit .btn { min-height: 50px; border-color: #16a34a; border-radius: 10px; background: #16a34a; padding: .8rem 2rem; font-size: 1rem; font-weight: 700; }
    .vendor-registration-submit .btn:hover { border-color: #15803d; background: #15803d; }
    .vendor-registration-page a:focus-visible, .vendor-registration-page button:focus-visible { outline: 3px solid rgba(22,163,74,.45); outline-offset: 3px; }
    @media (max-width: 767.98px) {
        .vendor-registration-page { padding: 2rem 0 3rem; }
        .vendor-form-section { padding: 1.35rem 1.2rem; }
        .vendor-form-grid { grid-template-columns: 1fr; }
        .vendor-form-full { grid-column: auto; }
        .vendor-payment-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 575.98px) {
        .vendor-correction-toast { top: 12px; right: 12px; max-width: calc(100vw - 24px); }
        .vendor-registration-heading { margin-bottom: 1.5rem; }
        .vendor-form-grid { grid-template-columns: 1fr; gap: .9rem; }
        .vendor-form-full { grid-column: auto; }
        .vendor-form-section-heading { gap: .7rem; }
        .vendor-form-section-heading h2 { font-size: 1.05rem; }
        .vendor-form-section-heading p { font-size: .85rem; }
        .vendor-form-icon { width: 38px; height: 38px; flex-basis: 38px; }
        .vendor-payment-method { padding: 1rem; }
        .vendor-registration-submit { padding: 1.35rem 1.2rem 1.5rem; }
        .vendor-registration-submit .btn { width: 100%; }
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const toast = document.getElementById('vendor-correction-toast');
    if (!toast) return;

    const dismiss = () => { toast.hidden = true; };
    const timer = window.setTimeout(dismiss, 5000);

    toast.querySelector('.vendor-correction-toast-close')?.addEventListener('click', () => {
        window.clearTimeout(timer);
        dismiss();
    });
})();
</script>
@endpush
@endsection
