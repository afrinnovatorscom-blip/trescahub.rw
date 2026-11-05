@php $user = auth()->user(); @endphp
<div class="nav-section-label mt-3">Marketplace</div>
@if($user->hasPermission('products.approve'))
    <a href="{{ route('marketplace.approvals.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.approvals.*') ? 'active' : '' }}"><i class="bi bi-check2-square"></i> Product Approvals</a>
    <a href="{{ route('marketplace.reviews.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.reviews.*') ? 'active' : '' }}"><i class="bi bi-star"></i> Reviews</a>
@endif
@if($user->hasPermission('vendors.view'))
    <a href="{{ route('marketplace.vendors.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.vendors.*') ? 'active' : '' }}"><i class="bi bi-shop"></i> Vendors</a>
@endif
@if($user->hasAnyPermission(['orders.view_all', 'orders.view_assigned']))
    <a href="{{ route('marketplace.orders.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Orders</a>
@endif
@if($user->hasPermission('finance.view'))
    <a href="{{ route('marketplace.payments.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.payments.*') ? 'active' : '' }}"><i class="bi bi-credit-card"></i> Payments</a>
    <a href="{{ route('marketplace.wallet.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.wallet.*') ? 'active' : '' }}"><i class="bi bi-wallet2"></i> Wallet &amp; Payouts</a>
@endif
@if($user->hasPermission('deliveries.manage') || $user->hasPermission('orders.view_assigned'))
    <a href="{{ route('marketplace.deliveries.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.deliveries.*') ? 'active' : '' }}" data-marketplace-deliveries-link><i class="bi bi-truck"></i> Deliveries</a>
@endif
@if($user->hasPermission('inventory.view'))
    <a href="{{ route('marketplace.inventory.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.inventory.*') ? 'active' : '' }}"><i class="bi bi-boxes"></i> Inventory</a>
@endif
@if($user->hasPermission('reports.view'))
    <a href="{{ route('marketplace.reports.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.reports.*') ? 'active' : '' }}"><i class="bi bi-bar-chart"></i> Reports</a>
@endif
@if($user->hasPermission('audit_logs.view'))
    <a href="{{ route('marketplace.audit.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.audit.*') ? 'active' : '' }}"><i class="bi bi-journal-text"></i> Audit Log</a>
@endif
@if($user->hasPermission('users.manage'))
    <a href="{{ route('marketplace.users.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.users.*') ? 'active' : '' }}"><i class="bi bi-people"></i> Users</a>
@endif
@if($user->hasAnyPermission(['roles.manage', 'permissions.manage']))
    <a href="{{ route('marketplace.roles.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.roles.*') ? 'active' : '' }}"><i class="bi bi-shield-lock"></i> Roles</a>
@endif
<a href="{{ route('marketplace.notifications.index') }}" class="nav-item-link {{ request()->routeIs('marketplace.notifications.*') ? 'active' : '' }}"><i class="bi bi-bell"></i> Notifications</a>
