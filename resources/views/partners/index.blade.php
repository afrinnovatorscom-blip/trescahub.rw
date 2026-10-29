@extends('front.layouts.admin')

@section('title', 'Partners')

@section('content')
    <div class="container-fluid px-4 px-lg-5 py-4 py-lg-5 partner-admin">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="partner-page-title mb-1">
                    <i class="bi bi-handshake me-2" aria-hidden="true"></i>
                    Partners
                </h1>
                <p class="partner-page-description mb-0">Manage partners and control which ones appear on your website.</p>
            </div>
            <a href="{{ route('partners.create') }}" class="btn partner-btn-primary">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Add partner
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show partner-alert" role="status">
                <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        @endif

        @if($partners->isNotEmpty())
            <div class="partner-list-card">
                <div class="partner-list-meta">
                    <span>All partners</span>
                    <span class="partner-count">{{ $partners->total() }} {{ \Illuminate\Support\Str::plural('partner', $partners->total()) }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table partner-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Partner</th>
                                <th scope="col">Website</th>
                                <th scope="col">Visibility</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partners as $partner)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            @if($partner->logo_path)
                                                <img class="partner-list-logo" src="{{ asset('assets/'.ltrim($partner->logo_path, '/')) }}" alt="">
                                            @else
                                                <span class="partner-list-logo partner-list-logo-placeholder" aria-hidden="true">
                                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($partner->title, 0, 1)) }}
                                                </span>
                                            @endif
                                            <div class="min-w-0">
                                                <div class="partner-list-name">{{ $partner->title }}</div>
                                                <div class="partner-list-description">{{ $partner->description ?: 'No description added' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($partner->website_url)
                                            <a href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" class="partner-website-link">
                                                {{ \Illuminate\Support\Str::limit(parse_url($partner->website_url, PHP_URL_HOST) ?: $partner->website_url, 32) }}
                                                <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                                            </a>
                                        @else
                                            <span class="partner-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="partner-badge {{ $partner->show_on_website ? 'partner-badge-visible' : 'partner-badge-hidden' }}">
                                            {{ $partner->show_on_website ? 'Shown on website' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="partner-badge {{ $partner->status === 'active' ? 'partner-badge-active' : 'partner-badge-inactive' }}">
                                            <span class="partner-status-dot" aria-hidden="true"></span>
                                            {{ ucfirst($partner->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('partners.edit', $partner) }}" class="partner-action-link" aria-label="Edit {{ $partner->title }}">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                            <span>Edit</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($partners->hasPages())
                    <div class="partner-pagination">{{ $partners->links() }}</div>
                @endif
            </div>
        @else
            <div class="partner-empty-state">
                <div class="partner-empty-icon"><i class="bi bi-people" aria-hidden="true"></i></div>
                <h2>No partners yet</h2>
                <p>Add a partner to manage its profile and website visibility.</p>
                <a href="{{ route('partners.create') }}" class="btn partner-btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Add your first partner
                </a>
            </div>
        @endif
    </div>

    @include('partners._list_styles')
@endsection
