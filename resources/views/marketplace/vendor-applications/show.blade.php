@extends('front.layouts.admin')
@section('page-title', 'Review Vendor Application')

@push('styles')
<style>
    .vendor-review-page { max-width:1440px; margin:0 auto; color:#0f172a; font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
    .vendor-review-page h1,.vendor-review-page h2,.vendor-review-page h3 { font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
    .review-breadcrumb { margin-bottom:16px; font-size:13px; }
    .review-breadcrumb a { color:#2563eb; text-decoration:none; }
    .review-breadcrumb a:hover { color:#1d4ed8; text-decoration:underline; }
    .review-page-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:16px; }
    .review-page-title { margin:0 0 4px; color:#0f172a; font-size:26px; font-weight:700; line-height:1.2; }
    .review-page-description { margin:0; color:#64748b; font-size:14px; }
    .review-correction-toast { position:fixed; z-index:1090; top:18px; right:24px; display:flex; max-width:min(460px,calc(100vw - 32px)); align-items:flex-start; gap:10px; padding:13px 15px; border:1px solid #fde68a; border-radius:8px; background:#fffbeb; box-shadow:0 8px 24px rgba(15,23,42,.12); color:#92400e; font-size:14px; }
    .review-correction-toast[hidden] { display:none !important; }
    .review-correction-toast-icon { flex:0 0 auto; margin-top:2px; }
    .review-correction-toast-message { min-width:0; flex:1; overflow-wrap:anywhere; }
    .review-correction-toast-close { border:0; background:transparent; color:inherit; font-size:20px; line-height:1; }
    .review-status-badge { display:inline-flex; min-height:28px; align-items:center; justify-content:center; border:1px solid transparent; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:600; white-space:nowrap; }
    .review-status-pending { color:#b45309; background:#fef3c7; border-color:#fde68a; }
    .review-status-correction { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
    .review-status-approved { color:#15803d; background:#dcfce7; border-color:#bbf7d0; }
    .review-status-rejected { color:#b91c1c; background:#fee2e2; border-color:#fecaca; }
    .review-status-neutral { color:#475569; background:#f1f5f9; border-color:#e2e8f0; }
    .review-profile { display:flex; align-items:center; gap:16px; margin-bottom:20px; padding:18px 20px; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
    .review-profile-logo { position:relative; display:inline-flex; width:64px; height:64px; flex:0 0 64px; align-items:center; justify-content:center; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#f1f5f9; color:#475569; font-size:22px; font-weight:700; }
    .review-profile-logo img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .review-profile-copy { min-width:0; flex:1 1 auto; }
    .review-profile-kicker { margin:0 0 3px; color:#64748b; font-size:12px; font-weight:600; }
    .review-profile-name { margin:0 0 5px; color:#0f172a; font-size:20px; font-weight:700; line-height:1.3; overflow-wrap:anywhere; }
    .review-profile-contact { display:flex; flex-wrap:wrap; align-items:center; gap:5px 10px; margin:0; color:#475569; font-size:13px; overflow-wrap:anywhere; }
    .review-profile-contact i { color:#64748b; }
    .review-submitted { display:flex; flex:0 0 auto; flex-direction:column; gap:3px; padding-left:20px; border-left:1px solid #e2e8f0; }
    .review-submitted-label { color:#64748b; font-size:12px; font-weight:500; }
    .review-submitted-value { color:#0f172a; font-size:14px; font-weight:600; white-space:nowrap; }
    .review-alert { margin-bottom:16px; border-radius:8px; font-size:14px; }
    .review-section-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .review-panel { min-width:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
    .review-panel-heading { display:flex; align-items:center; gap:10px; min-height:58px; padding:12px 16px; border-bottom:1px solid #e2e8f0; }
    .review-panel-icon { display:inline-flex; width:34px; height:34px; flex:0 0 34px; align-items:center; justify-content:center; border-radius:8px; background:#eff6ff; color:#2563eb; font-size:16px; }
    .review-panel-title { margin:0; color:#0f172a; font-size:16px; font-weight:600; line-height:1.3; }
    .review-panel-body { padding:16px; }
    .review-field-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px 18px; margin:0; }
    .review-field { min-width:0; }
    .review-field-full { grid-column:1 / -1; }
    .review-field dt { margin:0 0 4px; color:#64748b; font-size:12px; font-weight:500; }
    .review-field dd { margin:0; color:#334155; font-size:14px; font-weight:500; line-height:1.45; overflow-wrap:anywhere; word-break:break-word; }
    .review-document-row { display:flex; align-items:center; gap:12px; min-width:0; padding:12px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; }
    .review-document-icon { display:inline-flex; width:40px; height:40px; flex:0 0 40px; align-items:center; justify-content:center; border-radius:8px; background:#f1f5f9; color:#475569; font-size:20px; }
    .review-document-copy { min-width:0; flex:1 1 auto; }
    .review-document-name { display:block; color:#0f172a; font-size:14px; font-weight:600; overflow-wrap:anywhere; }
    .review-document-meta { display:block; margin-top:2px; color:#64748b; font-size:12px; }
    .review-payment-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
    .review-payment-method { min-width:0; }
    .review-payment-method + .review-payment-method { padding-left:20px; border-left:1px solid #e2e8f0; }
    .review-payment-title { display:flex; align-items:center; gap:7px; margin:0 0 12px; color:#334155; font-size:14px; font-weight:600; }
    .review-payment-title i { color:#2563eb; }
    .review-payment-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px 16px; margin:0; }
    .review-payment-fields .review-field-full { grid-column:1 / -1; }
    .review-button { display:inline-flex; min-height:42px; align-items:center; justify-content:center; gap:7px; border:1px solid transparent; border-radius:8px; padding:9px 14px; font-size:13px; font-weight:600; line-height:1.2; text-align:center; text-decoration:none; transition:background-color .15s ease,border-color .15s ease,color .15s ease; }
    .review-button-primary { border-color:#2563eb; background:#2563eb; color:#fff; }
    .review-button-primary:hover { border-color:#1d4ed8; background:#1d4ed8; color:#fff; }
    .review-button-secondary { border-color:#cbd5e1; background:#fff; color:#334155; }
    .review-button-secondary:hover { border-color:#94a3b8; background:#f8fafc; color:#0f172a; }
    .review-button-danger { border-color:#b91c1c; background:#b91c1c; color:#fff; }
    .review-button-danger:hover { border-color:#991b1b; background:#991b1b; color:#fff; }
    .review-button:focus-visible { outline:3px solid #93c5fd; outline-offset:2px; }
    .review-button:disabled { cursor:not-allowed; opacity:.55; }
    .review-actions { margin-top:16px; overflow:hidden; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
    .review-actions-heading { padding:14px 16px; border-bottom:1px solid #e2e8f0; }
    .review-actions-title { margin:0; color:#0f172a; font-size:16px; font-weight:600; }
    .review-actions-body { padding:16px; }
    .review-action-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
    .review-action-form { display:flex; min-width:0; flex-direction:column; gap:9px; }
    .review-action-title { margin:0; color:#334155; font-size:14px; font-weight:600; }
    .review-action-help { min-height:36px; margin:0; color:#64748b; font-size:13px; line-height:1.45; }
    .review-action-label { margin:0; color:#475569; font-size:12px; font-weight:600; }
    .review-action-textarea { width:100%; min-height:84px; resize:vertical; border:1px solid #cbd5e1; border-radius:8px; padding:9px 11px; color:#334155; font:inherit; font-size:13px; line-height:1.45; }
    .review-action-textarea:focus { border-color:#2563eb; outline:3px solid rgba(37,99,235,.16); }
    .review-action-textarea::placeholder { color:#94a3b8; }
    .review-action-form .review-button { width:100%; margin-top:auto; }
    .review-action-error { color:#b91c1c; font-size:12px; }
    .review-approved { display:flex; align-items:center; gap:8px; margin-top:16px; border:1px solid #bbf7d0; border-radius:8px; padding:12px 14px; background:#f0fdf4; color:#166534; font-size:14px; }
    @media(max-width:991.98px) {
        .review-payment-grid { grid-template-columns:1fr; gap:16px; }
        .review-payment-method + .review-payment-method { padding-top:16px; padding-left:0; border-top:1px solid #e2e8f0; border-left:0; }
        .review-action-grid { grid-template-columns:1fr; }
        .review-action-help { min-height:0; }
    }
    @media(max-width:767.98px) {
        .review-page-header { flex-direction:column; align-items:flex-start; }
        .review-section-grid { grid-template-columns:1fr; }
        .review-profile { align-items:flex-start; flex-wrap:wrap; padding:16px; }
        .review-profile-logo { width:56px; height:56px; flex-basis:56px; }
        .review-profile-copy { flex-basis:calc(100% - 76px); }
        .review-submitted { width:100%; flex-direction:row; justify-content:space-between; padding:12px 0 0; border-top:1px solid #e2e8f0; border-left:0; }
        .review-submitted-value { white-space:normal; text-align:right; }
        .review-document-row { align-items:flex-start; flex-wrap:wrap; }
        .review-document-copy { flex-basis:calc(100% - 56px); }
        .review-document-row .review-button { width:100%; }
    }
    @media(max-width:479.98px) {
        .review-correction-toast { top:12px; right:12px; max-width:calc(100vw - 24px); }
        .review-page-title { font-size:23px; }
        .review-panel-body,.review-actions-body { padding:14px; }
        .review-field-grid,.review-payment-fields { grid-template-columns:1fr; }
        .review-field-full,.review-payment-fields .review-field-full { grid-column:auto; }
        .review-action-form .review-button { min-height:44px; }
    }
</style>
@endpush

@section('content')
@if(filled($correctionNotice ?? null))
    <div class="review-correction-toast" id="review-correction-toast" role="status" aria-live="polite">
        <i class="review-correction-toast-icon bi bi-exclamation-circle" aria-hidden="true"></i>
        <span class="review-correction-toast-message"><strong>Requested correction:</strong> {{ $correctionNotice }}</span>
        <button type="button" class="review-correction-toast-close" aria-label="Dismiss notification">&times;</button>
    </div>
@endif

<div class="container-fluid py-4 vendor-review-page">
    @include('marketplace.partials.flash')

    @php
        $logoPath = ltrim((string) $vendor->business_logo_path, '/');
        $logoExists = $logoPath !== '' && ! str_contains($logoPath, '..') && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$logoPath));
        $businessInitial = mb_strtoupper(mb_substr(trim((string) $vendor->business_name), 0, 1));
        $applicationStatusClass = match ($vendor->application_status) {
            'pending' => 'review-status-pending',
            'correction_requested' => 'review-status-correction',
            'approved' => 'review-status-approved',
            'rejected' => 'review-status-rejected',
            default => 'review-status-neutral',
        };
        $mobileMoneyProvider = match ($vendor->mobile_money_provider) {
            'mtn_momo' => 'MTN MoMo',
            'airtel_money' => 'Airtel Money',
            null, '' => '—',
            default => $vendor->mobile_money_provider,
        };
        $certificateType = $vendor->registration_certificate_path
            ? strtoupper(pathinfo($vendor->registration_certificate_path, PATHINFO_EXTENSION))
            : null;
    @endphp

    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb review-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('marketplace.vendors.index') }}">Vendors</a></li>
            <li class="breadcrumb-item"><a href="{{ route('marketplace.vendors.index', ['tab' => 'unverified']) }}">Unverified Applications</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Review Application</li>
        </ol>
    </nav>

    <header class="review-page-header">
        <div>
            <h1 class="review-page-title">Review Vendor Application</h1>
            <p class="review-page-description">Review the submitted business details and application documents.</p>
        </div>
        <span class="review-status-badge {{ $applicationStatusClass }}">{{ $vendor->applicationStatusLabel() }}</span>
    </header>

    <section class="review-profile" aria-label="Vendor application profile">
        <span class="review-profile-logo" aria-hidden="true">
            <span>{{ $businessInitial !== '' ? $businessInitial : 'V' }}</span>
            @if($logoExists)<img src="{{ asset('assets/'.$logoPath) }}" alt="" onerror="this.style.display='none'">@endif
        </span>
        <div class="review-profile-copy">
            <p class="review-profile-kicker">Vendor applicant</p>
            <h2 class="review-profile-name">{{ $vendor->business_name }}</h2>
            <p class="review-profile-contact">
                <span><i class="bi bi-person me-1" aria-hidden="true"></i>{{ $vendor->contact_person ?: '—' }}</span>
                <span><i class="bi bi-envelope me-1" aria-hidden="true"></i>{{ $vendor->users->first()?->email ?? '—' }}</span>
            </p>
        </div>
        <div class="review-submitted">
            <span class="review-submitted-label"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Submitted</span>
            <span class="review-submitted-value">{{ $vendor->application_submitted_at?->format('M j, Y H:i') ?? '—' }}</span>
        </div>
    </section>

    @if($vendor->application_status === 'rejected' && $vendor->rejection_reason)
        <div class="alert alert-danger review-alert" role="status"><strong>Rejection reason:</strong> {{ $vendor->rejection_reason }}</div>
    @endif
    <div class="review-section-grid">
        <section class="review-panel" aria-labelledby="account-information-heading">
            <div class="review-panel-heading">
                <span class="review-panel-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
                <h2 id="account-information-heading" class="review-panel-title">Account Information</h2>
            </div>
            <div class="review-panel-body">
                <dl class="review-field-grid">
                    <div class="review-field"><dt>Contact person</dt><dd>{{ $vendor->contact_person ?: '—' }}</dd></div>
                    <div class="review-field"><dt>Account email</dt><dd>{{ $vendor->users->first()?->email ?? '—' }}</dd></div>
                    <div class="review-field"><dt>Business phone</dt><dd>{{ $vendor->phone ?: '—' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="review-panel" aria-labelledby="business-information-heading">
            <div class="review-panel-heading">
                <span class="review-panel-icon"><i class="bi bi-building" aria-hidden="true"></i></span>
                <h2 id="business-information-heading" class="review-panel-title">Business Information</h2>
            </div>
            <div class="review-panel-body">
                <dl class="review-field-grid">
                    <div class="review-field"><dt>Business name</dt><dd>{{ $vendor->business_name ?: '—' }}</dd></div>
                    <div class="review-field"><dt>TIN</dt><dd>{{ $vendor->tin ?: '—' }}</dd></div>
                    <div class="review-field"><dt>Business email</dt><dd>{{ $vendor->business_email ?: $vendor->email ?: '—' }}</dd></div>
                    <div class="review-field review-field-full"><dt>Business address</dt><dd>{{ $vendor->business_address ?: '—' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="review-panel" aria-labelledby="business-documents-heading">
            <div class="review-panel-heading">
                <span class="review-panel-icon"><i class="bi bi-file-earmark-lock" aria-hidden="true"></i></span>
                <h2 id="business-documents-heading" class="review-panel-title">Business Documents</h2>
            </div>
            <div class="review-panel-body">
                @if($vendor->registration_certificate_path)
                    <div class="review-document-row">
                        <span class="review-document-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                        <div class="review-document-copy">
                            <span class="review-document-name">RDB Business Registration Certificate</span>
                            <span class="review-document-meta">{{ $certificateType ?: 'Document' }} · Securely stored</span>
                        </div>
                        <a class="review-button review-button-secondary" href="{{ route('marketplace.vendor-applications.certificate', $vendor) }}">
                            <i class="bi bi-download" aria-hidden="true"></i>Download
                        </a>
                    </div>
                @else
                    <p class="mb-0 text-muted">No certificate uploaded.</p>
                @endif
            </div>
        </section>

        <section class="review-panel" aria-labelledby="payment-information-heading">
            <div class="review-panel-heading">
                <span class="review-panel-icon"><i class="bi bi-wallet2" aria-hidden="true"></i></span>
                <h2 id="payment-information-heading" class="review-panel-title">Payment Information</h2>
            </div>
            <div class="review-panel-body">
                <div class="review-payment-grid">
                    <section class="review-payment-method" aria-labelledby="bank-account-heading">
                        <h3 id="bank-account-heading" class="review-payment-title"><i class="bi bi-bank" aria-hidden="true"></i>Bank Account</h3>
                        <dl class="review-payment-fields">
                            <div class="review-field"><dt>Bank name</dt><dd>{{ $vendor->bank_name ?: '—' }}</dd></div>
                            <div class="review-field"><dt>Account holder</dt><dd>{{ $vendor->bank_account_holder ?: '—' }}</dd></div>
                            <div class="review-field review-field-full"><dt>Account number</dt><dd>{{ $vendor->bank_account_number ?: '—' }}</dd></div>
                        </dl>
                    </section>
                    <section class="review-payment-method" aria-labelledby="mobile-money-heading">
                        <h3 id="mobile-money-heading" class="review-payment-title"><i class="bi bi-phone" aria-hidden="true"></i>Mobile Money</h3>
                        <dl class="review-payment-fields">
                            <div class="review-field"><dt>Provider</dt><dd>{{ $mobileMoneyProvider }}</dd></div>
                            <div class="review-field"><dt>Registered phone</dt><dd>{{ $vendor->mobile_money_phone ?: '—' }}</dd></div>
                            <div class="review-field review-field-full"><dt>Account holder</dt><dd>{{ $vendor->mobile_money_account_holder ?: '—' }}</dd></div>
                        </dl>
                    </section>
                </div>
            </div>
        </section>
    </div>

    @if(in_array($vendor->application_status, ['pending', 'correction_requested'], true))
        <section class="review-actions" aria-labelledby="review-decision-heading">
            <div class="review-actions-heading">
                <h2 id="review-decision-heading" class="review-actions-title">Review decision</h2>
            </div>
            <div class="review-actions-body">
                <div class="review-action-grid">
                    <form method="POST" action="{{ route('marketplace.vendor-applications.approve', $vendor) }}" class="review-action-form">
                        @csrf
                        <h3 class="review-action-title">Accept Vendor</h3>
                        <p class="review-action-help">Approve this application and activate the vendor account.</p>
                        <button type="submit" class="review-button review-button-primary" onclick="return confirm('Accept this vendor application and activate vendor access?')">
                            <i class="bi bi-check-circle" aria-hidden="true"></i>Accept Vendor
                        </button>
                    </form>

                    <form method="POST" action="{{ route('marketplace.vendor-applications.correction', $vendor) }}" class="review-action-form">
                        @csrf
                        <h3 class="review-action-title">Request Correction</h3>
                        <label class="review-action-label" for="correction_request">What needs to be updated?</label>
                        <textarea id="correction_request" name="correction_request" rows="3" maxlength="5000" class="review-action-textarea @error('correction_request') is-invalid @enderror" placeholder="Explain what the applicant needs to update." required>{{ old('correction_request') }}</textarea>
                        @error('correction_request')<div class="review-action-error">{{ $message }}</div>@enderror
                        <button type="submit" class="review-button review-button-secondary">Send Correction Request</button>
                    </form>

                    <form method="POST" action="{{ route('marketplace.vendor-applications.reject', $vendor) }}" class="review-action-form">
                        @csrf
                        <h3 class="review-action-title">Reject Application</h3>
                        <label class="review-action-label" for="rejection_reason">Reason for rejection</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" maxlength="5000" class="review-action-textarea @error('rejection_reason') is-invalid @enderror" placeholder="Explain why this application is being rejected." required>{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')<div class="review-action-error">{{ $message }}</div>@enderror
                        <button type="submit" class="review-button review-button-danger">
                            <i class="bi bi-x-circle" aria-hidden="true"></i>Reject Application
                        </button>
                    </form>
                </div>
            </div>
        </section>
    @elseif($vendor->application_status === 'approved')
        <div class="review-approved" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span>Approved {{ $vendor->approved_at?->format('M j, Y H:i') ? 'on '.$vendor->approved_at->format('M j, Y H:i') : '' }}.</span>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const toast = document.getElementById('review-correction-toast');
    if (!toast) return;

    const dismiss = () => { toast.hidden = true; };
    const timer = window.setTimeout(dismiss, 5000);

    toast.querySelector('.review-correction-toast-close')?.addEventListener('click', () => {
        window.clearTimeout(timer);
        dismiss();
    });
})();
</script>
@endpush
