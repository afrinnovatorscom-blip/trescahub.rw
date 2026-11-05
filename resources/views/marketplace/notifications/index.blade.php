@extends('front.layouts.admin')
@section('page-title', 'Notifications')
@section('content')
    @push('styles')
        <style>
            .notification-center {
                @if(auth()->user()->isVendorOnly())
                --notification-primary: #087f6b;
                --notification-navy: #1a2035;
                --notification-page: var(--body-bg);
                --notification-border: rgba(0, 0, 0, .08);
                --notification-body: #4a5568;
                --notification-muted: #7a8499;
                font-family: 'DM Sans', sans-serif;
                @else
                --notification-primary: #2563eb;
                --notification-navy: #0f172a;
                --notification-page: #f8fafc;
                --notification-border: #e2e8f0;
                --notification-body: #475569;
                --notification-muted: #64748b;
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                @endif
                margin: -30px -28px;
                min-height: calc(100vh - var(--topbar-h));
                padding: 28px;
                background: var(--notification-page);
                color: var(--notification-body);
            }

            .notification-center h1,
            .notification-center h2,
            .notification-center h3 {
                @if(auth()->user()->isVendorOnly())
                font-family: 'Syne', sans-serif;
                @else
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                @endif
            }

            .notification-center-heading {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 20px;
                margin-bottom: 24px;
            }

            .notification-center-title {
                margin: 0 0 6px;
                color: var(--notification-navy);
                font-size: 26px;
                font-weight: 700;
                letter-spacing: -.025em;
            }

            .notification-center-subtitle {
                margin: 0;
                color: var(--notification-muted);
                font-size: 14px;
            }

            .notification-mark-all {
                display: inline-flex;
                min-height: 42px;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 9px 14px;
                border: 1px solid var(--notification-primary);
                border-radius: 9px;
                background: var(--notification-primary);
                color: #fff;
                font-family: inherit;
                font-size: 13px;
                font-weight: 600;
                line-height: 1.3;
                white-space: nowrap;
                cursor: pointer;
                transition: background .15s ease, border-color .15s ease;
            }

            .notification-mark-all:hover:not(:disabled) {
                @if(auth()->user()->isVendorOnly())
                border-color: #065f52;
                background: #065f52;
                @else
                border-color: #1d4ed8;
                background: #1d4ed8;
                @endif
            }

            .notification-mark-all:focus-visible,
            .notification-filter:focus-visible,
            .notification-search-input:focus-visible,
            .notification-search-submit:focus-visible,
            .notification-search-clear:focus-visible,
            .notification-card-link:focus-visible,
            .notification-summary-card:focus-visible {
                outline: 3px solid rgba(37, 99, 235, .28);
                outline-offset: 2px;
            }

            .notification-mark-all:disabled {
                border-color: #cbd5e1;
                background: #e2e8f0;
                color: #64748b;
                cursor: not-allowed;
            }

            .notification-success {
                margin: 0 0 20px;
                padding: 12px 16px;
                border: 1px solid #bbf7d0;
                border-radius: 9px;
                background: #f0fdf4;
                color: #166534;
                font-size: 14px;
            }

            .notification-summary {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
                margin-bottom: 20px;
            }

            .notification-summary-card {
                display: flex;
                min-height: 82px;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 16px 18px;
                border: 1px solid var(--notification-border);
                border-radius: 11px;
                background: #fff;
                color: var(--notification-body);
                text-decoration: none;
                transition: border-color .15s ease, background .15s ease;
            }

            .notification-summary-card:hover { border-color: #bfdbfe; }
            .notification-summary-card.is-active {
                border-color: #93c5fd;
                background: #eff6ff;
            }

            .notification-summary-label {
                color: var(--notification-muted);
                font-size: 13px;
                font-weight: 500;
            }

            .notification-summary-count {
                color: var(--notification-navy);
                font-size: 23px;
                font-weight: 700;
                line-height: 1;
            }

            .notification-controls {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                margin-bottom: 14px;
            }

            .notification-filters {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            .notification-filter {
                display: inline-flex;
                min-height: 36px;
                align-items: center;
                padding: 7px 12px;
                border: 1px solid transparent;
                border-radius: 8px;
                color: var(--notification-muted);
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
            }

            .notification-filter:hover {
                background: #fff;
                color: var(--notification-navy);
            }

            .notification-filter.is-active {
                border-color: var(--notification-border);
                background: #fff;
                color: var(--notification-primary);
            }

            .notification-search {
                display: flex;
                width: min(100%, 340px);
                min-height: 40px;
                align-items: center;
                gap: 8px;
                padding: 0 10px 0 12px;
                border: 1px solid var(--notification-border);
                border-radius: 9px;
                background: #fff;
            }

            .notification-search > i {
                color: var(--notification-muted);
                font-size: 14px;
            }

            .notification-search-input {
                width: 100%;
                min-width: 0;
                border: 0;
                outline: 0;
                background: transparent;
                color: var(--notification-navy);
                font: inherit;
                font-size: 13px;
            }

            .notification-search-input:focus-visible { outline: 0; }
            .notification-search-input::placeholder { color: #94a3b8; }
            .notification-search-submit,
            .notification-search-clear {
                display: inline-flex;
                width: 28px;
                height: 28px;
                flex: 0 0 28px;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 6px;
                background: transparent;
                color: var(--notification-muted);
                text-decoration: none;
                cursor: pointer;
            }

            .notification-search-submit:hover,
            .notification-search-clear:hover { background: #f1f5f9; color: var(--notification-navy); }

            .notification-list {
                overflow: hidden;
                border: 1px solid var(--notification-border);
                border-radius: 11px;
                background: #fff;
            }

            .notification-card-form + .notification-card-form { border-top: 1px solid var(--notification-border); }
            .notification-card-form { margin: 0; }

            .notification-card-link {
                display: flex;
                width: 100%;
                min-height: 96px;
                align-items: flex-start;
                gap: 14px;
                padding: 18px 20px;
                border: 0;
                background: #fff;
                color: inherit;
                text-align: left;
                cursor: pointer;
                transition: background .15s ease;
            }

            .notification-card-link.is-unread,
            .notification-card-link.is-unread:hover { background: #eff6ff; }
            .notification-card-link.is-read,
            .notification-card-link.is-read:hover { background: #fff; }
            .notification-card-link.is-read:hover { background: #f8fafc; }

            .notification-card-icon {
                display: flex;
                width: 38px;
                height: 38px;
                flex: 0 0 38px;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--notification-border);
                border-radius: 9px;
                background: #fff;
                color: var(--notification-primary);
                font-size: 16px;
            }

            .notification-card-copy { display: block; min-width: 0; flex: 1; }
            .notification-card-title {
                display: block;
                margin: 0 0 4px;
                color: var(--notification-navy);
                font-size: 14px;
                font-weight: 600;
                line-height: 1.4;
            }

            .notification-card-description {
                display: block;
                color: var(--notification-body);
                font-size: 13px;
                line-height: 1.5;
                overflow-wrap: anywhere;
            }

            .notification-card-time {
                display: flex;
                align-items: center;
                gap: 5px;
                margin-top: 8px;
                color: var(--notification-muted);
                font-size: 12px;
            }

            .notification-empty {
                padding: 44px 20px;
                color: var(--notification-muted);
                text-align: center;
            }

            .notification-empty i {
                display: block;
                margin-bottom: 10px;
                color: #94a3b8;
                font-size: 24px;
            }

            .notification-empty strong {
                display: block;
                margin-bottom: 4px;
                color: var(--notification-navy);
                font-size: 15px;
            }

            .notification-pagination {
                display: flex;
                min-height: 58px;
                align-items: center;
                justify-content: space-between;
                gap: 12px 20px;
                padding: 10px 18px;
                border-top: 1px solid var(--notification-border);
            }

            .notification-pagination-summary {
                margin: 0;
                color: var(--notification-muted);
                font-size: 12px;
                line-height: 1.4;
                white-space: nowrap;
            }

            .notification-pagination-controls {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 4px;
                min-width: 0;
            }

            .notification-page-link {
                display: inline-flex;
                min-width: 34px;
                height: 34px;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 0 9px;
                border: 1px solid transparent;
                border-radius: 7px;
                background: transparent;
                color: var(--notification-body);
                font: inherit;
                font-size: 12px;
                font-weight: 500;
                line-height: 1;
                text-decoration: none;
                white-space: nowrap;
                transition: border-color .15s ease, background .15s ease, color .15s ease;
            }

            .notification-page-link:hover:not(.is-disabled):not(.is-current) {
                border-color: var(--notification-border);
                background: #f8fafc;
                color: var(--notification-primary);
            }

            .notification-page-link.is-current {
                border-color: var(--notification-primary);
                background: var(--notification-primary);
                color: #fff;
                font-weight: 600;
            }

            .notification-page-link.is-disabled {
                color: #94a3b8;
                cursor: not-allowed;
            }

            .notification-page-link:focus-visible {
                outline: 3px solid rgba(37, 99, 235, .28);
                outline-offset: 2px;
            }

            .notification-page-link i { font-size: 11px; line-height: 1; }
            .notification-page-ellipsis {
                display: inline-flex;
                min-width: 20px;
                height: 34px;
                align-items: center;
                justify-content: center;
                color: var(--notification-muted);
                font-size: 12px;
            }

            @media (max-width: 767px) {
                .notification-center { padding: 22px 18px; }
                .notification-center-heading { gap: 12px; }
                .notification-center-title { font-size: 23px; }
                .notification-summary { gap: 8px; }
                .notification-summary-card { min-height: 72px; padding: 12px; }
                .notification-summary-label { font-size: 12px; }
                .notification-summary-count { font-size: 20px; }
                .notification-controls { align-items: stretch; flex-direction: column; }
                .notification-search { width: 100%; }
            }

            @media (max-width: 576px) {
                .notification-center { margin: -20px -16px; padding: 20px 16px; }
                .notification-center-heading { align-items: flex-start; flex-direction: column; }
                .notification-mark-all { min-height: 40px; }
                .notification-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .notification-summary-card { align-items: flex-start; flex-direction: column-reverse; gap: 8px; }
                .notification-card-link { gap: 10px; padding: 15px 13px; }
                .notification-card-icon { width: 34px; height: 34px; flex-basis: 34px; }
                .notification-pagination {
                    align-items: flex-start;
                    flex-direction: column;
                    gap: 8px;
                    padding: 12px;
                }
                .notification-pagination-controls {
                    width: 100%;
                    justify-content: center;
                    gap: 2px;
                }
                .notification-page-link { min-width: 32px; height: 36px; padding: 0 7px; }
                .notification-page-link.is-direction { padding: 0 8px; }
                .notification-page-link.is-direction span { display: none; }
            }
        </style>
    @endpush

    <section class="notification-center">
        <div class="notification-center-heading">
            <div>
                <h1 class="notification-center-title">Notifications</h1>
                <p class="notification-center-subtitle">
                    {{ auth()->user()->isVendorOnly()
                        ? 'Stay updated on important activity in your vendor account.'
                        : 'Stay updated on important activity across Tresca Hub.' }}
                </p>
            </div>
            <form id="markAllNotificationsForm" method="POST" action="{{ route('marketplace.notifications.read-all') }}">
                @csrf
                <button class="notification-mark-all" type="submit" @disabled($notificationCounts['unread'] === 0)>
                    <i class="bi bi-check2-all" aria-hidden="true"></i>
                    Mark all as read
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="notification-success" role="status">{{ session('success') }}</div>
        @endif

        <div class="notification-controls">
            <nav class="notification-filters" aria-label="Filter notifications">
                @foreach(['all' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $filterKey => $filterLabel)
                    <a
                        class="notification-filter {{ $filter === $filterKey ? 'is-active' : '' }}"
                        href="{{ route('marketplace.notifications.index', array_filter(['filter' => $filterKey === 'all' ? null : $filterKey, 'q' => $search ?: null])) }}"
                        @if($filter === $filterKey) aria-current="page" @endif
                    >{{ $filterLabel }}</a>
                @endforeach
            </nav>

            <form class="notification-search" method="GET" action="{{ route('marketplace.notifications.index') }}" role="search">
                @if($filter !== 'all')
                    <input type="hidden" name="filter" value="{{ $filter }}">
                @endif
                <i class="bi bi-search" aria-hidden="true"></i>
                <input
                    class="notification-search-input"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search notifications"
                    aria-label="Search notifications"
                >
                @if($search !== '')
                    <a class="notification-search-clear" href="{{ route('marketplace.notifications.index', array_filter(['filter' => $filter === 'all' ? null : $filter])) }}" aria-label="Clear search">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                @endif
                <button class="notification-search-submit" type="submit" aria-label="Submit search">
                    <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
                </button>
            </form>
        </div>

        <div class="notification-list">
            @forelse($notifications as $notification)
                @php
                    $notificationTitle = $notification->data['title'] ?? 'Update';
                    $notificationBody = $notification->data['body'] ?? '';
                    $notificationText = \Illuminate\Support\Str::lower($notificationTitle.' '.$notificationBody);
                    $notificationIcon = match (true) {
                        str_contains($notificationText, 'order') => 'bi-receipt',
                        str_contains($notificationText, 'product') => 'bi-box-seam',
                        str_contains($notificationText, 'vendor') => 'bi-shop',
                        str_contains($notificationText, 'request') => 'bi-inboxes',
                        str_contains($notificationText, 'stock'), str_contains($notificationText, 'inventory') => 'bi-boxes',
                        str_contains($notificationText, 'delivery') => 'bi-truck',
                        default => 'bi-bell',
                    };
                @endphp
                <form method="POST" action="{{ route('marketplace.notifications.read', $notification->id) }}" class="notification-card-form">
                    @csrf
                    <button
                        type="submit"
                        class="notification-card-link {{ $notification->read_at ? 'is-read' : 'is-unread' }}"
                        aria-label="Open notification: {{ $notificationTitle }}"
                    >
                        <span class="notification-card-icon" aria-hidden="true"><i class="bi {{ $notificationIcon }}"></i></span>
                        <span class="notification-card-copy">
                            <span class="notification-card-title">{{ $notificationTitle }}</span>
                            <span class="notification-card-description">{{ $notificationBody }}</span>
                            <time class="notification-card-time" datetime="{{ $notification->created_at->toIso8601String() }}">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                {{ $notification->created_at->format('M j, Y · g:i A') }}
                                <span aria-hidden="true">·</span>
                                {{ $notification->created_at->diffForHumans() }}
                            </time>
                        </span>
                    </button>
                </form>
            @empty
                <div class="notification-empty">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    <strong>No notifications found</strong>
                    <span>
                        @if($search !== '' || $filter !== 'all')
                            Try adjusting your search or filter.
                        @else
                            Important updates will appear here.
                        @endif
                    </span>
                </div>
            @endforelse

            @if($notifications->hasPages())
                @php
                    $currentPage = $notifications->currentPage();
                    $lastPage = $notifications->lastPage();
                    $firstVisiblePage = max(1, $currentPage - 1);
                    $lastVisiblePage = min($lastPage, $currentPage + 1);
                @endphp
                <nav class="notification-pagination" aria-label="Notification pagination">
                    <p class="notification-pagination-summary">
                        Showing {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ $notifications->total() }} results
                    </p>
                    <div class="notification-pagination-controls">
                        @if($notifications->previousPageUrl())
                            <a class="notification-page-link is-direction" href="{{ $notifications->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                <span>Previous</span>
                            </a>
                        @else
                            <span class="notification-page-link is-direction is-disabled" aria-disabled="true" aria-label="Previous page">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                <span>Previous</span>
                            </span>
                        @endif

                        @if($firstVisiblePage > 1)
                            <a class="notification-page-link" href="{{ $notifications->url(1) }}" aria-label="Page 1">1</a>
                            @if($firstVisiblePage > 2)
                                <span class="notification-page-ellipsis" aria-hidden="true">…</span>
                            @endif
                        @endif

                        @foreach(range($firstVisiblePage, $lastVisiblePage) as $page)
                            @if($page === $currentPage)
                                <span class="notification-page-link is-current" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                            @else
                                <a class="notification-page-link" href="{{ $notifications->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if($lastVisiblePage < $lastPage)
                            @if($lastVisiblePage < $lastPage - 1)
                                <span class="notification-page-ellipsis" aria-hidden="true">…</span>
                            @endif
                            <a class="notification-page-link" href="{{ $notifications->url($lastPage) }}" aria-label="Page {{ $lastPage }}">{{ $lastPage }}</a>
                        @endif

                        @if($notifications->nextPageUrl())
                            <a class="notification-page-link is-direction" href="{{ $notifications->nextPageUrl() }}" rel="next" aria-label="Next page">
                                <span>Next</span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        @else
                            <span class="notification-page-link is-direction is-disabled" aria-disabled="true" aria-label="Next page">
                                <span>Next</span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </span>
                        @endif
                    </div>
                </nav>
            @endif
        </div>
    </section>

    @push('scripts')
        <script>
            const markAllNotificationsForm = document.getElementById('markAllNotificationsForm');
            if (markAllNotificationsForm) {
                markAllNotificationsForm.addEventListener('submit', () => {
                    const button = markAllNotificationsForm.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');
                });
            }
        </script>
    @endpush
    @endsection
