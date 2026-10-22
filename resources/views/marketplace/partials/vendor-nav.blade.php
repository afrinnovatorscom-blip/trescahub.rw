@php $user = auth()->user(); @endphp
<a href="{{ route('vendor.dashboard') }}" class="nav-item-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Overview</a>
<a href="{{ route('vendor.products.index') }}" class="nav-item-link {{ request()->routeIs('vendor.products.*') ? 'active' : '' }}"><i class="bi bi-box-seam"></i> My Products</a>
<a href="{{ route('vendor.products.create') }}" class="nav-item-link"><i class="bi bi-plus-circle"></i> Submit Product</a>
<a href="{{ route('vendor.requests.index') }}" class="nav-item-link {{ request()->routeIs('vendor.requests.*') ? 'active' : '' }}"><i class="bi bi-inbox"></i> Assigned Requests</a>
<a href="{{ route('vendor.orders.index') }}" class="nav-item-link {{ request()->routeIs('vendor.orders.*') ? 'active' : '' }}"><i class="bi bi-bag-check"></i> Assigned Orders</a>
@if($user->hasPermission('inventory.view'))
    <a href="{{ route('vendor.inventory') }}" class="nav-item-link"><i class="bi bi-boxes"></i> Inventory</a>
@endif
<a href="{{ route('marketplace.notifications.index') }}" class="nav-item-link"><i class="bi bi-bell"></i> Notifications</a>
<a href="{{ route('vendor.profile') }}" class="nav-item-link"><i class="bi bi-person"></i> Account Profile</a>
