@extends('front.layouts.admin')
@section('page-title', 'Notifications')
@section('content')
<div class="container-fluid py-4">
    <h1 class="h3">Notifications</h1>
    @forelse($notifications as $notification)
        <form method="POST" action="{{ route('marketplace.notifications.read', $notification->id) }}" class="border rounded p-3 mb-2">
            @csrf
            <strong>{{ $notification->data['title'] ?? 'Update' }}</strong>
            <p class="mb-2">{{ $notification->data['body'] ?? '' }}</p>
            <button class="btn btn-sm btn-outline-dark" type="submit">{{ $notification->read_at ? 'Open' : 'Mark read and open' }}</button>
        </form>
    @empty
        <p class="text-muted">No notifications.</p>
    @endforelse
    {{ $notifications->links() }}
</div>
@endsection
