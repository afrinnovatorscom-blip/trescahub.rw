<style>
    .pay-admin { color: #0f172a; max-width: 1500px; }
    .pay-heading { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 1rem; margin-bottom: 1.1rem; }
    .pay-heading h1 { font-size: 1.35rem; font-weight: 700; margin: 0; }
    .pay-heading p { color: #64748b; font-size: .875rem; margin: .25rem 0 0; }
    .pay-tabs { display: flex; gap: .4rem; flex-wrap: wrap; margin-bottom: 1.1rem; }
    .pay-tabs a { border: 1px solid #e2e8f0; background: #fff; color: #475569; border-radius: 999px; padding: .35rem .9rem; font-size: .82rem; font-weight: 600; text-decoration: none; }
    .pay-tabs a.active { background: #0f172a; border-color: #0f172a; color: #fff; }
    .pay-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .75rem; margin-bottom: 1.1rem; }
    .pay-stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: .85rem 1rem; }
    .pay-stat span { display: block; color: #64748b; font-size: .78rem; }
    .pay-stat strong { display: block; font-size: 1.15rem; margin-top: .15rem; }
    .pay-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .pay-card-head { padding: .85rem 1.1rem; border-bottom: 1px solid #eef2f7; font-weight: 700; }
    .pay-filters { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .pay-filters .form-control, .pay-filters .form-select { width: auto; min-width: 140px; font-size: .85rem; }
    .pay-table { margin: 0; font-size: .85rem; }
    .pay-table thead th { background: #f8fafc; color: #475569; font-size: .74rem; font-weight: 600; text-transform: uppercase; letter-spacing: .02em; padding: .7rem .9rem; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .pay-table td { padding: .75rem .9rem; vertical-align: middle; border-color: #eef2f7; }
    .pay-table .muted { color: #94a3b8; font-size: .76rem; }
    .pay-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .76rem; word-break: break-all; }
    .pay-badge { display: inline-block; border-radius: 999px; padding: .15rem .6rem; font-size: .72rem; font-weight: 600; white-space: nowrap; }
    .pay-badge.is-completed, .pay-badge.is-successful { background: #dcfce7; color: #166534; }
    .pay-badge.is-pending { background: #fef9c3; color: #854d0e; }
    .pay-badge.is-failed { background: #fee2e2; color: #991b1b; }
    .pay-badge.is-request { background: #e0e7ff; color: #3730a3; }
    .pay-badge.is-callback { background: #f3e8ff; color: #6b21a8; }
    .pay-badge.is-status_check { background: #e0f2fe; color: #075985; }
    .pay-empty { text-align: center; color: #64748b; padding: 3rem 1rem; }
    .pay-icon-btn { border: 1px solid #e2e8f0; background: #fff; border-radius: 8px; padding: .25rem .55rem; font-size: .8rem; color: #334155; }
    .pay-icon-btn:hover { background: #f1f5f9; }
    .pay-details summary { cursor: pointer; color: #2563eb; font-size: .76rem; }
    .pay-details pre { background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: .75rem; font-size: .72rem; max-width: 520px; max-height: 280px; overflow: auto; margin: .4rem 0 0; white-space: pre-wrap; }
</style>
<nav class="pay-tabs" aria-label="Payment sections">
    <a href="{{ route('marketplace.payments.index') }}" class="{{ request()->routeIs('marketplace.payments.index') ? 'active' : '' }}"><i class="bi bi-credit-card"></i> Payment records</a>
    <a href="{{ route('marketplace.payments.logs') }}" class="{{ request()->routeIs('marketplace.payments.logs') ? 'active' : '' }}"><i class="bi bi-journal-code"></i> IntouchPay logs</a>
    <a href="{{ route('marketplace.wallet.index') }}" class="{{ request()->routeIs('marketplace.wallet.*') ? 'active' : '' }}"><i class="bi bi-wallet2"></i> Wallet &amp; payouts</a>
</nav>
