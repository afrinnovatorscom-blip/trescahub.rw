<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Tresca Hub — @yield('title', 'Dashboard')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --sidebar-width: 265px;
            --sidebar-bg: #0b0f1a;
            --sidebar-border: rgba(255,255,255,0.06);
            --accent: #00d4aa;
            --accent-soft: rgba(0, 212, 170, 0.12);
            --accent-glow: rgba(0, 212, 170, 0.35);
            --topbar-h: 64px;
            --text-muted-custom: #7a8499;
            --body-bg: #f0f2f7;
            --card-bg: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--body-bg);
            margin: 0;
            min-height: 100vh;
        }

        h1,h2,h3,h4,h5,h6,
        .brand-name, .nav-label { font-family: 'Syne', sans-serif; }

        /* ── Sidebar ── */
        #sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 24px 22px 20px;
            border-bottom: 1px solid var(--sidebar-border);
            text-decoration: none;
        }

        .brand-icon {
            width: 36px; height: 36px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            color: #0b0f1a;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 0 16px var(--accent-glow);
        }

        .brand-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
            line-height: 1.1;
        }

        .brand-name span {
            display: block;
            font-size: 0.65rem;
            font-weight: 400;
            color: var(--text-muted-custom);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: 'DM Sans', sans-serif;
        }

        .nav-section { padding: 20px 14px 6px; }

        .nav-section-label {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--text-muted-custom);
            padding: 0 8px;
            margin-bottom: 6px;
            font-family: 'DM Sans', sans-serif;
        }

        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #a0aabf;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 400;
            transition: all 0.18s ease;
            margin-bottom: 2px;
            position: relative;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-item-link i { font-size: 1.05rem; width: 20px; text-align: center; flex-shrink: 0; }
        .nav-item-link:hover { background: rgba(255,255,255,0.05); color: #ffffff; }
        .nav-item-link.active { background: var(--accent-soft); color: var(--accent); font-weight: 500; }
        .nav-item-link.active i { color: var(--accent); }

        .nav-badge {
            margin-left: auto;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
            background: #ff4757;
            color: #fff;
            line-height: 1.4;
            flex-shrink: 0;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 14px;
            border-top: 1px solid var(--sidebar-border);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: rgba(255,255,255,0.04);
            cursor: pointer;
            transition: background 0.18s;
        }

        .user-card:hover { background: rgba(255,255,255,0.08); }

        .user-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--accent), #0099ff);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }

        .user-name {
            font-size: 0.8rem;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: 'Syne', sans-serif;
        }

        .user-role { font-size: 0.68rem; color: var(--text-muted-custom); }

        /* ── Topbar ── */
        #topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-h);
            background: rgba(240, 242, 247, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 16px;
            z-index: 1030;
        }

        .topbar-title {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a2035;
            flex: 1;
        }

        .topbar-search { position: relative; }

        .topbar-search input {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 10px;
            padding: 7px 14px 7px 36px;
            font-size: 0.85rem;
            width: 220px;
            outline: none;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .topbar-search input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        .topbar-search i {
            position: absolute;
            left: 11px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted-custom);
            font-size: 0.9rem;
        }

        .topbar-icon-btn {
            width: 38px; height: 38px;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: #4a5568;
            font-size: 1rem;
            transition: all 0.18s;
            position: relative;
            text-decoration: none;
        }

        .topbar-icon-btn:hover {
            background: var(--accent-soft);
            border-color: var(--accent);
            color: var(--accent);
        }

        .notif-badge {
            position: absolute;
            top: 6px; right: 6px;
            width: 8px; height: 8px;
            background: #ff4757;
            border-radius: 50%;
            border: 2px solid var(--body-bg);
        }

        #sidebarToggle {
            display: none;
            width: 38px; height: 38px;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 10px;
            align-items: center; justify-content: center;
            cursor: pointer;
            color: #4a5568;
            font-size: 1.1rem;
        }

        #main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-h);
            min-height: 100vh;
        }

        .content-inner { padding: 30px 28px; }

        .dash-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .dash-card:hover {
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
            transform: translateY(-1px);
        }

        #sidebarOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1039;
        }

        @media (max-width: 991px) {
            #sidebar { transform: translateX(calc(-1 * var(--sidebar-width))); }
            #sidebar.open { transform: translateX(0); }
            #topbar { left: 0; }
            #main-content { margin-left: 0; }
            #sidebarToggle { display: flex; }
            #sidebarOverlay.show { display: block; }
        }

        @media (max-width: 576px) {
            .content-inner { padding: 20px 16px; }
            .topbar-search { display: none; }
        }
    </style>

    @stack('styles')
</head>
<body>

<div id="sidebarOverlay"></div>

<aside id="sidebar">

    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <div class="brand-icon">T</div>
        <div class="brand-name">
            Tresca Hub
            <span>{{ auth()->user()->isVendorOnly() ? 'Vendor Panel' : 'Admin Panel' }}</span>
        </div>
    </a>

    @php
        $pendingTestimonials    = \App\Models\Testimonial::pending()->count();
        $pendingServiceRequests = \App\Models\Servicerequest::pending()->count();
    @endphp

    <nav class="nav-section">
        @if(auth()->user()->isVendorOnly())
            @include('marketplace.partials.vendor-nav')
        @else
        <div class="nav-section-label">Main</div>

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
           class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i>
            Dashboard
        </a>

        @if(auth()->user()->hasPermission('cms.manage'))
            <a href="{{ route('services.index') }}" class="nav-item-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
                <i class="bi bi-briefcase"></i>
                Services
            </a>
            <a href="{{ route('backgrounds.index') }}" class="nav-item-link {{ request()->routeIs('backgrounds.*') ? 'active' : '' }}">
                <i class="bi bi-image"></i>
                Backgrounds
            </a>
            <a href="{{ route('categories.index') }}" class="nav-item-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="bi bi-card-list"></i>
                Categories
            </a>
        @endif

        @if(auth()->user()->hasPermission('admin.access'))
            <a href="{{ route('shop-categories.index') }}" class="nav-item-link {{ request()->routeIs('shop-categories.*') ? 'active' : '' }}">
                <i class="bi bi-bag"></i>
                Shop Categories
            </a>
            <a href="{{ route('admin.shop-products.index') }}" class="nav-item-link {{ request()->routeIs('admin.shop-products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                Shop Products
            </a>
        @endif

        @if(auth()->user()->hasPermission('cms.manage'))
            <a href="{{ route('partners.index') }}" class="nav-item-link {{ request()->routeIs('partners.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                Partners
            </a>
            <a href="{{ route('works.index') }}" class="nav-item-link {{ request()->routeIs('works.*') ? 'active' : '' }}">
                <i class="bi bi-grid-3x3-gap"></i>
                Works
            </a>
            <a href="{{ route('cta-categories.index') }}" class="nav-item-link {{ request()->routeIs('cta-categories.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i>
                CTA Categories
            </a>
            <a href="{{ route('ctas.index') }}" class="nav-item-link {{ request()->routeIs('ctas.*') ? 'active' : '' }}">
                <i class="bi bi-lightning-charge"></i>
                CTAs
            </a>

        {{-- Testimonials --}}
        <a href="{{ route('testimonials.index') }}"
           class="nav-item-link {{ request()->routeIs('testimonials.*') ? 'active' : '' }}">
            <i class="bi bi-chat-quote"></i>
            Testimonials
            @if($pendingTestimonials > 0)
                <span class="nav-badge">{{ $pendingTestimonials }}</span>
            @endif
        </a>

        {{-- Service Requests --}}
        <a href="{{ route('service-requests.index') }}"
           class="nav-item-link {{ request()->routeIs('service-requests.*') ? 'active' : '' }}">
            <i class="bi bi-inbox"></i>
            Service Requests
            @if($pendingServiceRequests > 0)
                <span class="nav-badge">{{ $pendingServiceRequests }}</span>
            @endif
        </a>

        @endif

        @include('marketplace.partials.staff-nav')
        @endif
    </nav>

    {{-- System --}}
    <nav class="nav-section">
        <div class="nav-section-label">System</div>

        <a href="#" class="nav-item-link">
            <i class="bi bi-gear"></i>
            Settings
        </a>

        <a href="#"
           class="nav-item-link"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-left"></i>
            Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            @php
                $profileUser = auth()->user();
                $profileVendor = $profileUser->isVendorOnly() ? $profileUser->vendor : null;
                $profileLogoPath = ltrim((string) $profileVendor?->business_logo_path, '/');
                $profileLogoExists = $profileLogoPath !== ''
                    && ! str_contains($profileLogoPath, '..')
                    && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$profileLogoPath));
                $profileInitialSource = trim((string) ($profileVendor?->business_name ?: $profileUser->name));
                $profileInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($profileInitialSource ?: 'A', 0, 1));
            @endphp
            <div class="user-avatar">
                @if($profileLogoExists)
                    <img
                        src="{{ asset('assets/'.$profileLogoPath) }}"
                        alt="{{ $profileVendor->business_name ?: $profileUser->name }} logo"
                        style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;"
                        onerror="this.hidden = true; this.nextElementSibling.hidden = false;"
                    >
                @endif
                <span @if($profileLogoExists) hidden @endif>{{ $profileInitial }}</span>
            </div>
            <div class="user-info">
                <div class="user-name">{{ $profileUser->name ?? 'Admin' }}</div>
                <div class="user-role">{{ $profileUser->roleLabel() }}</div>
            </div>
            <i class="bi bi-three-dots-vertical" style="color:#7a8499; font-size:0.85rem;"></i>
        </div>
    </div>

</aside>

<header id="topbar">
    <button id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <div class="topbar-title">@yield('page-title', 'Dashboard')</div>

    <div class="topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Search anything…" />
    </div>

    {{-- Testimonials notif --}}
    <a href="{{ route('testimonials.index') }}" class="topbar-icon-btn" title="Pending Testimonials">
        <i class="bi bi-chat-quote"></i>
        @if($pendingTestimonials > 0)
            <span class="notif-badge"></span>
        @endif
    </a>

    {{-- Service Requests notif --}}
    <a href="{{ route('service-requests.index') }}" class="topbar-icon-btn" title="Pending Service Requests">
        <i class="bi bi-inbox"></i>
        @if($pendingServiceRequests > 0)
            <span class="notif-badge"></span>
        @endif
    </a>

    <a href="#" class="topbar-icon-btn">
        <i class="bi bi-person-circle"></i>
    </a>
</header>

<main id="main-content">
    <div class="content-inner">
        @yield('content')
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    const sidebarScrollKey = 'tresca-admin-sidebar-scroll';

    const restoreSidebarScroll = () => {
        try {
            const savedPosition = sessionStorage.getItem(sidebarScrollKey);
            if (savedPosition === null) return;

            const saved = JSON.parse(savedPosition);
            if (
                !saved
                || !Number.isFinite(saved.scrollTop)
                || saved.scrollTop < 0
                || typeof saved.atBottom !== 'boolean'
            ) return;

            const maxScroll = Math.max(0, sidebar.scrollHeight - sidebar.clientHeight);
            sidebar.scrollTop = saved.atBottom
                ? maxScroll
                : Math.min(saved.scrollTop, maxScroll);
        } catch (error) {
            console.warn('Unable to restore the admin sidebar scroll position.', error);
        }
    };

    const saveSidebarScroll = () => {
        const maxScroll = Math.max(0, sidebar.scrollHeight - sidebar.clientHeight);

        try {
            sessionStorage.setItem(sidebarScrollKey, JSON.stringify({
                scrollTop: sidebar.scrollTop,
                atBottom: maxScroll > 0 && sidebar.scrollTop >= maxScroll,
            }));
        } catch (error) {
            console.error('Unable to save the admin sidebar scroll position.', error);
        }
    };

    restoreSidebarScroll();
    window.addEventListener('pagehide', saveSidebarScroll);

</script>

@stack('scripts')
</body>
</html>