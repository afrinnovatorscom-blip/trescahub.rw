@extends('front.layouts.admin')

@section('title', 'Backgrounds')
@section('page-title', 'Backgrounds')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-image me-2"></i>Backgrounds
    </h4>
    <a href="{{ route('backgrounds.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Background
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="dash-card">
    <div class="card-body p-0">
        @if($backgrounds->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Thumbnail</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Go To</th>
                            <th>Button Text</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backgrounds as $background)
                            @php
                                // Handle both legacy array ["value"] and plain string storage
                                $val = fn($raw) => is_array($raw) ? ($raw[0] ?? null) : $raw;

                                $title      = $val($background->title);
                                $thumbnail  = $val($background->thumbnail);
                                $desc       = $val($background->description);
                                $goTo       = $val($background->go_to);
                                $buttonText = $val($background->button_text);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                {{-- Thumbnail --}}
                                <td>
                                    @if($thumbnail)
                                        <img src="{{ asset('' . $thumbnail) }}"
                                             alt="{{ $title ?? '' }}"
                                             class="rounded"
                                             style="width:50px;height:40px;object-fit:cover;">
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                                {{-- Title --}}
                                <td>
                                    <strong>{{ $title ?? '—' }}</strong>
                                </td>

                                {{-- Slug --}}
                                <td>
                                    <code class="text-muted small">/{{ $background->slug }}</code>
                                </td>

                                {{-- Description --}}
                                <td>
                                    @if($desc)
                                        <small class="text-muted" style="display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;">
                                            {{ $desc }}
                                        </small>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                                {{-- Go To --}}
                                <td>
                                    @if($goTo)
                                        <small class="text-muted"><code>{{ $goTo }}</code></small>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                                {{-- Button Text --}}
                                <td>
                                    @if($buttonText)
                                        <small class="text-muted">{{ $buttonText }}</small>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('backgrounds.edit', $background->slug) }}"
                                           class="btn btn-outline-warning"
                                           title="Edit">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <form action="{{ route('backgrounds.destroy', $background->slug) }}"
                                              method="POST"
                                              style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-outline-danger"
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this background?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($backgrounds->hasPages())
                <div class="d-flex justify-content-center p-3">
                    {{ $backgrounds->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size:3rem;color:#ccc;"></i>
                <p class="text-muted mt-3">No backgrounds found.</p>
                <a href="{{ route('backgrounds.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Create First Background
                </a>
            </div>
        @endif
    </div>
</div>

@endsection