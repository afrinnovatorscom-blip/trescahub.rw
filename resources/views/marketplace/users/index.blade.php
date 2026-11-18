@extends('front.layouts.admin')
@section('page-title', 'Users')
@section('content')
<style>
    .users-page { color: #172033; max-width: 1500px; }
    .users-page-heading { align-items: center; display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .users-page-heading h1 { font-size: 1.35rem; font-weight: 700; margin: 0; }
    .users-page-heading p { color: #64748b; font-size: .875rem; margin: .25rem 0 0; }
    .users-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .users-summary-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; padding: .7rem .9rem; }
    .users-summary-card span { color: #64748b; display: block; font-size: .75rem; }
    .users-summary-card strong { color: #172033; display: block; font-size: 1.15rem; line-height: 1.35; }
    .users-tabs { border-bottom: 1px solid #e2e8f0; display: flex; gap: 1.25rem; margin-bottom: 1rem; }
    .users-tab { background: none; border: 0; border-bottom: 2px solid transparent; color: #64748b; font-size: .88rem; font-weight: 600; padding: .6rem .1rem; }
    .users-tab[aria-selected="true"] { border-color: #2563eb; color: #1d4ed8; }
    .users-panel[hidden] { display: none; }
    .users-toolbar { align-items: center; display: flex; flex-wrap: wrap; gap: .55rem; margin-bottom: .75rem; }
    .users-toolbar .form-control, .users-toolbar .form-select { font-size: .85rem; max-width: 340px; min-width: 180px; }
    .users-directory-title { font-size: 1rem; font-weight: 700; margin: 0; }
    .users-table-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; overflow: hidden; }
    .users-table-heading { align-items: center; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; padding: .75rem 1rem; }
    .users-table { font-size: .84rem; margin: 0; }
    .users-table thead th { background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: .72rem; font-weight: 600; letter-spacing: .02em; padding: .65rem .85rem; text-transform: uppercase; white-space: nowrap; }
    .users-table td { border-color: #eef2f7; padding: .65rem .85rem; vertical-align: middle; }
    .users-person { align-items: center; display: flex; gap: .65rem; min-width: 0; }
    .users-avatar { align-items: center; background: #eff6ff; border: 1px solid #dbeafe; border-radius: 50%; color: #1d4ed8; display: inline-flex; flex: 0 0 36px; font-size: .82rem; font-weight: 700; height: 36px; justify-content: center; overflow: hidden; width: 36px; }
    .users-avatar img { height: 100%; object-fit: cover; width: 100%; }
    .users-person-info { min-width: 0; }
    .users-person-name { color: #172033; font-weight: 600; overflow-wrap: anywhere; }
    .users-person-email, .users-muted { color: #64748b; font-size: .76rem; overflow-wrap: anywhere; }
    .users-role-text { color: #475569; }
    .users-actions { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: flex-end; }
    .users-pagination { align-items: center; display: flex; gap: .5rem; justify-content: flex-end; padding: .7rem 1rem; }
    .users-page-button { align-items: center; background: #fff; border: 1px solid #dbe2ea; border-radius: 6px; color: #334155; display: inline-flex; font-size: .78rem; gap: .35rem; min-height: 32px; padding: .3rem .6rem; text-decoration: none; }
    .users-page-button:hover { background: #f8fafc; color: #1d4ed8; }
    .users-page-button.is-disabled { background: #f8fafc; color: #94a3b8; }
    .users-page-status { color: #64748b; font-size: .76rem; min-width: 76px; text-align: center; }
    .users-role-grid { display: grid; gap: .55rem .8rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .users-role-option { align-items: flex-start; background: #fff; border: 1px solid #e2e8f0; border-radius: 7px; display: flex; gap: .5rem; padding: .55rem .65rem; }
    .users-role-option .form-check-input { margin-top: .2rem; }
    @media (max-width: 767.98px) {
        .users-page-heading { align-items: flex-start; }
        .users-summary { gap: .5rem; }
        .users-summary-card { padding: .6rem; }
        .users-summary-card strong { font-size: 1rem; }
        .users-toolbar { align-items: stretch; }
        .users-toolbar .form-control, .users-toolbar .form-select { max-width: none; min-width: 0; width: 100%; }
        .users-table thead { display: none; }
        .users-table, .users-table tbody, .users-table tr, .users-table td { display: block; width: 100%; }
        .users-table tbody tr { border-bottom: 1px solid #e2e8f0; padding: .4rem .8rem; }
        .users-table tbody tr:last-child { border-bottom: 0; }
        .users-table td { border: 0; padding: .35rem 0; }
        .users-table td[data-label]:not([data-label="User"]):not([data-label="Customer"]) { align-items: flex-start; display: flex; gap: .75rem; justify-content: space-between; }
        .users-table td[data-label]:not([data-label="User"]):not([data-label="Customer"])::before { color: #64748b; content: attr(data-label); flex: 0 0 86px; font-size: .72rem; font-weight: 600; }
        .users-actions { justify-content: flex-start; }
        .users-table-heading { padding: .65rem .8rem; }
        .users-pagination { justify-content: center; padding: .7rem .5rem; }
    }
    @media (max-width: 420px) {
        .users-page-heading h1 { font-size: 1.2rem; }
        .users-page-heading p { font-size: .78rem; max-width: 230px; }
        .users-page-heading > .btn { flex: 0 0 auto; font-size: .8rem; padding: .4rem .6rem; }
        .users-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .users-summary-card span { font-size: .67rem; }
        .users-role-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="container-fluid py-4 users-page">
    @include('marketplace.partials.flash')

    <header class="users-page-heading">
        <div>
            <h1>Users</h1>
            <p>Manage system users, customers and access permissions.</p>
        </div>
        <a class="btn btn-primary btn-sm" href="{{ route('marketplace.users.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> New User</a>
    </header>

    <section class="users-summary" aria-label="User counts">
        <div class="users-summary-card"><span>Total Users</span><strong>{{ number_format($totalUsers) }}</strong></div>
        <div class="users-summary-card"><span>Staff Users</span><strong>{{ number_format($staffCount) }}</strong></div>
        <div class="users-summary-card"><span>Customers</span><strong>{{ number_format($customerCount) }}</strong></div>
    </section>

    <div class="users-tabs" role="tablist" aria-label="User directories">
        <button id="users-tab-all" class="users-tab" type="button" role="tab" aria-controls="users-panel-all" aria-selected="{{ $activeTab === 'all' ? 'true' : 'false' }}">All Users</button>
        <button id="users-tab-customers" class="users-tab" type="button" role="tab" aria-controls="users-panel-customers" aria-selected="{{ $activeTab === 'customers' ? 'true' : 'false' }}">Customers</button>
    </div>

    <section id="users-panel-all" class="users-panel" role="tabpanel" aria-labelledby="users-tab-all" @if($activeTab !== 'all') hidden @endif>
        <form method="GET" action="{{ route('marketplace.users.index') }}" class="users-toolbar">
            <input type="hidden" name="tab" value="all">
            <label class="visually-hidden" for="staff-search">Search users</label>
            <input id="staff-search" name="staff_search" type="search" value="{{ $filters['staff_search'] ?? '' }}" class="form-control form-control-sm" placeholder="Search name, email or phone">
            <label class="visually-hidden" for="staff-role">Filter by role</label>
            <select id="staff-role" name="staff_role" class="form-select form-select-sm">
                <option value="">All roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) ($filters['staff_role'] ?? '') === (string) $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Search</button>
            @if(($filters['staff_search'] ?? null) || ($filters['staff_role'] ?? null))
                <a href="{{ route('marketplace.users.index', ['tab' => 'all']) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            @endif
        </form>

        <div class="users-table-card">
            <div class="users-table-heading"><h2 class="users-directory-title">User Directory</h2></div>
            <div class="table-responsive">
                <table class="table users-table">
                    <thead><tr><th>User</th><th>Phone</th><th>Assigned Roles</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $manageableIds = $roles->pluck('id');
                                $protectedRoleNames = $user->roles->whereNotIn('id', $manageableIds)->whereNotIn('slug', ['vendor', 'customer'])->pluck('name')->values();
                            @endphp
                            <tr>
                                <td data-label="User">
                                    <div class="users-person">
                                        <span class="users-avatar">
                                            @if($user->profile_photo_path)
                                                <img src="{{ $user->profile_photo_url }}" alt="">
                                            @else
                                                {{ mb_strtoupper(mb_substr($user->name ?: '?', 0, 1)) }}
                                            @endif
                                        </span>
                                        <span class="users-person-info">
                                            <span class="users-person-name d-block">{{ $user->name }}</span>
                                            <span class="users-person-email d-block">{{ $user->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Phone">{{ $user->phone ?: '—' }}</td>
                                <td data-label="Assigned Roles" class="users-role-text">{{ $user->roles->whereNotIn('slug', ['vendor', 'customer'])->pluck('name')->join(', ') ?: 'No assigned roles' }}</td>
                                <td data-label="Actions">
                                    <div class="users-actions">
                                        <button type="button" class="btn btn-outline-primary btn-sm js-manage-access"
                                                data-bs-toggle="modal" data-bs-target="#manageAccessModal"
                                                data-user-name="{{ $user->name }}"
                                                data-user-email="{{ $user->email }}"
                                                data-action="{{ route('marketplace.users.access', $user) }}"
                                                data-role-ids="{{ $user->roles->pluck('id')->values()->toJson() }}"
                                                data-protected-roles="{{ $protectedRoleNames->join(', ') }}">
                                            Manage Access
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No users match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->total() > 20)
                <nav class="users-pagination border-top" aria-label="User directory pagination">
                    @if($users->currentPage() > 1)
                        <a class="users-page-button" href="{{ $users->url($users->currentPage() - 1) }}" rel="prev"><i class="bi bi-arrow-left" aria-hidden="true"></i> Previous</a>
                    @else
                        <span class="users-page-button is-disabled" aria-disabled="true"><i class="bi bi-arrow-left" aria-hidden="true"></i> Previous</span>
                    @endif
                    <span class="users-page-status">Page {{ $users->currentPage() }} of {{ $users->lastPage() }}</span>
                    @if($users->currentPage() < $users->lastPage())
                        <a class="users-page-button" href="{{ $users->url($users->currentPage() + 1) }}" rel="next">Next <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @else
                        <span class="users-page-button is-disabled" aria-disabled="true">Next <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    @endif
                </nav>
            @endif
        </div>
    </section>

    <section id="users-panel-customers" class="users-panel" role="tabpanel" aria-labelledby="users-tab-customers" @if($activeTab !== 'customers') hidden @endif>
        <form method="GET" action="{{ route('marketplace.users.index') }}" class="users-toolbar">
            <input type="hidden" name="tab" value="customers">
            <label class="visually-hidden" for="customer-search">Search customers</label>
            <input id="customer-search" name="customer_search" type="search" value="{{ $filters['customer_search'] ?? '' }}" class="form-control form-control-sm" placeholder="Search customer name, email or phone">
            <button type="submit" class="btn btn-dark btn-sm">Search</button>
            @if($filters['customer_search'] ?? null)
                <a href="{{ route('marketplace.users.index', ['tab' => 'customers']) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            @endif
        </form>

        <div class="users-table-card">
            <div class="users-table-heading"><h2 class="users-directory-title">Customer Directory</h2></div>
            <div class="table-responsive">
                <table class="table users-table">
                    <thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Registered</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td data-label="Customer">
                                    <div class="users-person">
                                        <span class="users-avatar">{{ mb_strtoupper(mb_substr($customer->name ?: '?', 0, 1)) }}</span>
                                        <span class="users-person-info"><span class="users-person-name d-block">{{ $customer->name ?: '—' }}</span></span>
                                    </div>
                                </td>
                                <td data-label="Email">{{ $customer->email ?: '—' }}</td>
                                <td data-label="Phone">{{ $customer->phone ?: '—' }}</td>
                                <td data-label="Registered">{{ $customer->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td data-label="Actions"><div class="users-actions"><span class="users-muted">—</span></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No customers match this search.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->total() > 20)
                <nav class="users-pagination border-top" aria-label="Customer directory pagination">
                    @if($customers->currentPage() > 1)
                        <a class="users-page-button" href="{{ $customers->url($customers->currentPage() - 1) }}" rel="prev"><i class="bi bi-arrow-left" aria-hidden="true"></i> Previous</a>
                    @else
                        <span class="users-page-button is-disabled" aria-disabled="true"><i class="bi bi-arrow-left" aria-hidden="true"></i> Previous</span>
                    @endif
                    <span class="users-page-status">Page {{ $customers->currentPage() }} of {{ $customers->lastPage() }}</span>
                    @if($customers->currentPage() < $customers->lastPage())
                        <a class="users-page-button" href="{{ $customers->url($customers->currentPage() + 1) }}" rel="next">Next <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @else
                        <span class="users-page-button is-disabled" aria-disabled="true">Next <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
</div>

<div class="modal fade" id="manageAccessModal" tabindex="-1" aria-labelledby="manageAccessTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="manageAccessForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="manageAccessTitle">Manage Access</h2>
                        <div class="users-person-email" id="manageAccessIdentity"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Select the roles this user should have.</p>
                    <div class="users-role-grid">
                        @foreach($roles as $role)
                            <label class="users-role-option" for="access-role-{{ $role->id }}">
                                <input class="form-check-input" id="access-role-{{ $role->id }}" type="checkbox" name="roles[]" value="{{ $role->id }}">
                                <span>{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div id="protectedRolesNote" class="alert alert-light border small mt-3 mb-0" hidden></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save access</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (() => {
        const tabs = [
            { button: document.getElementById('users-tab-all'), panel: document.getElementById('users-panel-all') },
            { button: document.getElementById('users-tab-customers'), panel: document.getElementById('users-panel-customers') },
        ];
        tabs.forEach(({ button, panel }) => {
            button.addEventListener('click', () => {
                tabs.forEach((tab) => {
                    const selected = tab.button === button;
                    tab.button.setAttribute('aria-selected', String(selected));
                    tab.panel.hidden = !selected;
                });
                const tabName = button === tabs[0].button ? 'all' : 'customers';
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
                panel.querySelectorAll('.users-page-button[href]').forEach((link) => {
                    const pageUrl = new URL(link.href);
                    pageUrl.searchParams.set('tab', tabName);
                    link.href = pageUrl.toString();
                });
            });
        });

        const modal = document.getElementById('manageAccessModal');
        const form = document.getElementById('manageAccessForm');
        const identity = document.getElementById('manageAccessIdentity');
        const protectedRolesNote = document.getElementById('protectedRolesNote');
        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const assignedRoleIds = JSON.parse(trigger.dataset.roleIds || '[]').map(String);
            form.action = trigger.dataset.action;
            document.getElementById('manageAccessTitle').textContent = 'Manage Access — ' + trigger.dataset.userName;
            identity.textContent = trigger.dataset.userEmail;
            form.querySelectorAll('input[name="roles[]"]').forEach((checkbox) => {
                checkbox.checked = assignedRoleIds.includes(checkbox.value);
            });
            protectedRolesNote.textContent = trigger.dataset.protectedRoles
                ? 'Protected roles remain unchanged: ' + trigger.dataset.protectedRoles
                : '';
            protectedRolesNote.hidden = !trigger.dataset.protectedRoles;
        });
    })();
</script>
@endsection
