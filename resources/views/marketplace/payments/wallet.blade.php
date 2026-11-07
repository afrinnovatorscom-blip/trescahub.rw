@extends('front.layouts.admin')
@section('page-title', 'Wallet & Payouts')
@section('content')
<style>
    .wallet-top { display: grid; grid-template-columns: minmax(260px, 1fr) 2fr; gap: .9rem; margin-bottom: 1.1rem; }
    .wallet-balance { background: linear-gradient(135deg, #14532d, #16803c); color: #fff; border-radius: 14px; padding: 1.3rem 1.4rem; }
    .wallet-balance span { color: rgba(255,255,255,.75); font-size: .82rem; }
    .wallet-balance-amount { font-size: 2rem; font-weight: 800; margin: .2rem 0; line-height: 1.15; }
    .wallet-balance-info { font-size: .75rem; color: rgba(255,255,255,.7); }
    .wallet-balance-info.is-error { color: #fecaca; }
    .wallet-balance button { margin-top: .9rem; background: #fff; color: #14532d; border: 0; border-radius: 8px; padding: .45rem .9rem; font-weight: 700; font-size: .85rem; }
    .wallet-balance button:disabled { opacity: .6; }
    .wallet-bottom { display: grid; grid-template-columns: minmax(280px, 1fr) 2fr; gap: .9rem; align-items: start; }
    .wallet-form { padding: 1.1rem; }
    .wallet-form .form-label { font-size: .84rem; font-weight: 600; margin-bottom: .25rem; }
    .wallet-form .form-control { font-size: .9rem; }
    .wallet-warning { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; border-radius: 8px; padding: .55rem .7rem; font-size: .78rem; margin-bottom: .9rem; }
    @media (max-width: 991.98px) { .wallet-top, .wallet-bottom { grid-template-columns: 1fr; } }
</style>

<div class="pay-admin">
    <div class="pay-heading">
        <div>
            <h1><i class="bi bi-wallet2"></i> Wallet &amp; Payouts</h1>
            <p>IntouchPay account {{ $accountNo ?: '(not configured)' }}</p>
        </div>
        <a href="{{ route('marketplace.wallet.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Refresh</a>
    </div>

    @include('marketplace.payments._styles')
    @include('marketplace.partials.flash')

    <div class="wallet-top">
        <div class="wallet-balance">
            <span><i class="bi bi-wallet2"></i> Available balance</span>
            <div id="balance-amount" class="wallet-balance-amount">{{ $balance !== null ? 'RWF '.number_format($balance) : 'Unavailable' }}</div>
            <div id="balance-info" class="wallet-balance-info {{ $balance === null ? 'is-error' : '' }}">
                {{ $balance !== null ? 'Live from IntouchPay · '.now()->format('M j, g:i:s A') : $balanceError }}
            </div>
            <button type="button" id="check-balance-btn"><i class="bi bi-arrow-repeat"></i> Check balance</button>
        </div>
        <div class="pay-stats mb-0">
            <div class="pay-stat"><span>Collected from customers</span><strong>RWF {{ number_format($stats['collected']) }}</strong></div>
            <div class="pay-stat"><span>Paid out</span><strong class="text-success">RWF {{ number_format($stats['paid_out']) }}</strong></div>
            <div class="pay-stat"><span>Payouts processing</span><strong>RWF {{ number_format($stats['pending_out']) }}</strong></div>
            <div class="pay-stat"><span>Successful payouts</span><strong>{{ number_format($stats['payout_count']) }}</strong></div>
        </div>
    </div>

    <div class="wallet-bottom">
        <div class="pay-card">
            <div class="pay-card-head">Send payout</div>
            <div class="wallet-form">
                @if($canSendPayouts)
                    <div class="wallet-warning"><i class="bi bi-exclamation-triangle"></i> Money is sent immediately from the IntouchPay account to the mobile money number. This cannot be undone.</div>
                    <form method="POST" action="{{ route('marketplace.wallet.payout') }}" id="payout-form">
                        @csrf
                        <label class="form-label" for="payout-phone">Mobile money number</label>
                        <input id="payout-phone" type="tel" name="phone" required value="{{ old('phone') }}" placeholder="078 123 4567" autocomplete="off"
                               class="form-control mb-2 @error('phone') is-invalid @enderror">
                        @error('phone')<div class="invalid-feedback mb-2">{{ $message }}</div>@enderror
                        <label class="form-label" for="payout-phone-confirm">Confirm mobile money number</label>
                        <input id="payout-phone-confirm" type="tel" name="phone_confirmation" required value="" placeholder="Type the number again" autocomplete="off"
                               class="form-control @error('phone_confirmation') is-invalid @enderror">
                        <div id="payout-phone-match" class="small mb-2" aria-live="polite">
                            @error('phone_confirmation')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <label class="form-label" for="payout-amount">Amount (RWF)</label>
                        <input id="payout-amount" type="number" name="amount" required min="100" step="1" value="{{ old('amount') }}"
                               @if($balance !== null) max="{{ (int) $balance }}" @endif class="form-control mb-2">
                        <label class="form-label" for="payout-reason">Reason</label>
                        <input id="payout-reason" type="text" name="reason" required maxlength="100" value="{{ old('reason') }}" placeholder="e.g. Vendor settlement — November" class="form-control mb-2">
                        <label class="d-flex align-items-start gap-2 small my-2">
                            <input type="checkbox" name="confirm" value="1" required class="mt-1">
                            I have checked the number and amount
                        </label>
                        <button type="submit" class="btn btn-success w-100 fw-semibold"><i class="bi bi-send"></i> Send payout</button>
                    </form>
                @else
                    <p class="text-muted small mb-0"><i class="bi bi-lock"></i> You can view the wallet, but sending payouts needs the “Send mobile money payouts” permission. Ask a super administrator.</p>
                @endif
            </div>
        </div>

        <div class="pay-card">
            <div class="pay-card-head">Payout history</div>
            <div class="table-responsive">
                <table class="table pay-table">
                    <thead>
                        <tr><th>Date</th><th>Recipient</th><th class="text-end">Amount</th><th>Status</th><th>Sent by</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $payout)
                            <tr>
                                <td class="text-nowrap">{{ $payout->created_at->format('M j, Y') }}<div class="muted">{{ $payout->created_at->format('g:i A') }}</div></td>
                                <td>
                                    <strong>{{ $payout->mobile_phone }}</strong>
                                    <div class="muted">{{ $payout->reason }}</div>
                                    <div class="muted pay-mono">{{ $payout->request_transaction_id }}</div>
                                </td>
                                <td class="text-end fw-bold text-nowrap">RWF {{ number_format((float) $payout->amount) }}</td>
                                <td>
                                    <span class="pay-badge is-{{ $payout->status }}">{{ ucfirst($payout->status) }}</span>
                                    @if($payout->message && $payout->status !== 'successful')
                                        <div class="muted" style="max-width: 14rem">{{ $payout->message }}</div>
                                    @endif
                                </td>
                                <td class="muted">{{ $payout->user->name ?? '—' }}</td>
                                <td class="text-end">
                                    @if($payout->reference_id && $payout->status !== 'successful')
                                        <form method="POST" action="{{ route('marketplace.wallet.payout.refresh', $payout) }}">
                                            @csrf
                                            <button class="pay-icon-btn" title="Re-check status with IntouchPay"><i class="bi bi-arrow-repeat"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="pay-empty">No payouts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $payouts->links() }}</div>
</div>

<script>
    (() => {
        const button = document.getElementById('check-balance-btn');
        const amount = document.getElementById('balance-amount');
        const info = document.getElementById('balance-info');
        button?.addEventListener('click', async () => {
            const original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking...';
            try {
                const response = await fetch(@json(route('marketplace.wallet.balance')), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await response.json().catch(() => ({}));
                if (response.ok && data.success) {
                    amount.textContent = data.formatted;
                    info.textContent = 'Live from IntouchPay · ' + data.checked_at;
                    info.classList.remove('is-error');
                    const amountInput = document.getElementById('payout-amount');
                    if (amountInput) amountInput.max = Math.floor(data.balance);
                } else {
                    info.textContent = data.message || (response.status === 429 ? 'Too many checks, wait a minute.' : 'Could not load balance');
                    info.classList.add('is-error');
                }
            } catch (error) {
                info.textContent = 'Network error, please try again.';
                info.classList.add('is-error');
            } finally {
                button.disabled = false;
                button.innerHTML = original;
            }
        });

        // Phone typed twice: compare as you type, block pasting into the confirmation field.
        const phone = document.getElementById('payout-phone');
        const phoneConfirm = document.getElementById('payout-phone-confirm');
        const phoneMatch = document.getElementById('payout-phone-match');
        const normalize = (value) => {
            let digits = String(value).replace(/\D+/g, '');
            if (digits.startsWith('250')) digits = digits.slice(3);
            return digits.replace(/^0+/, '');
        };
        const checkMatch = () => {
            if (!phoneConfirm.value) { phoneMatch.innerHTML = ''; phoneConfirm.setCustomValidity(''); return; }
            const ok = normalize(phone.value) !== '' && normalize(phone.value) === normalize(phoneConfirm.value);
            phoneMatch.innerHTML = ok
                ? '<span class="text-success"><i class="bi bi-check-circle"></i> Numbers match</span>'
                : '<span class="text-danger"><i class="bi bi-x-circle"></i> Numbers do not match</span>';
            phoneConfirm.setCustomValidity(ok ? '' : 'The two phone numbers do not match.');
            phoneConfirm.classList.toggle('is-invalid', !ok);
            phoneConfirm.classList.toggle('is-valid', ok);
        };
        phone?.addEventListener('input', checkMatch);
        phoneConfirm?.addEventListener('input', checkMatch);
        phoneConfirm?.addEventListener('paste', (event) => {
            event.preventDefault();
            phoneMatch.innerHTML = '<span class="text-warning-emphasis">Please type the number — pasting is disabled here.</span>';
        });

        document.getElementById('payout-form')?.addEventListener('submit', (event) => {
            const form = event.target;
            if (!form.reportValidity()) { event.preventDefault(); return; }
            const message = 'Send RWF ' + Number(form.amount.value).toLocaleString() + ' to ' + form.phone.value + '?\n\nThis cannot be undone.';
            if (!confirm(message)) { event.preventDefault(); return; }
            form.querySelector('button[type="submit"]').disabled = true;
        });
    })();
</script>
@endsection
