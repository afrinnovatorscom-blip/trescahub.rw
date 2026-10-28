@extends('front.layouts.admin')

@section('title', 'All Services')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-briefcase-fill me-2 text-primary"></i>All Services</h4>
    <a href="{{ route('services.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Service
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th style="width:70px">Thumb</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Classification</th>
                        <th>Subcategories</th>
                        <th>Working Day</th>
                        <th>Status</th>
                        <th style="width:130px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr>
                        <td class="text-muted small">{{ $service->id }}</td>

                        <td>
                            @if($service->thumbnail)
                                <img src="{{ asset('' . $service->thumbnail) }}"
                                     class="rounded"
                                     style="width:48px;height:48px;object-fit:cover"
                                     alt="{{ $service->title }}">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                     style="width:48px;height:48px">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            @endif
                        </td>

                        <td>
                            <div class="fw-semibold">{{ $service->title }}</div>
                            <code class="small text-muted">{{ $service->slug }}</code>
                        </td>

                        <td class="fw-semibold text-success">
                            RWF {{ number_format($service->price, 2) }}
                        </td>

                        <td>
                            @if($service->classification_name && isset($categories[$service->classification_name]))
                                <span class="badge bg-info text-dark">
                                    {{ $categories[$service->classification_name]->title }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td>
                            @if(!empty($service->subcategory))
                                @foreach($service->subcategory as $sub)
                                    <span class="badge bg-secondary me-1">{{ $sub }}</span>
                                @endforeach
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>

                        <td class="small">{{ $service->working_day ?: '—' }}</td>

                        {{-- Status: Popular / In Stock / Default badges --}}
                        <td>
                            @if($service->is_popular)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-fire"></i> Popular
                                </span>
                            @elseif($service->is_in_stock)
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> In Stock
                                </span>
                            @else
                                <span class="badge bg-light text-muted border">
                                    <i class="bi bi-dash-circle"></i> Default
                                </span>
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('services.show', $service) }}"
                               class="btn btn-sm btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('services.edit', $service) }}"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('services.destroy', $service) }}" method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete \'{{ addslashes($service->title) }}\'?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No services found. <a href="{{ route('services.create') }}">Create one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($services->hasPages())
    <div class="mt-3">{{ $services->links('pagination::bootstrap-5') }}</div>
@endif
@endsection