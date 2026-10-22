@extends('front.layouts.admin')
@section('page-title', 'Audit')
@section('content')
<div class="container-fluid py-4">
    <h1 class="h3">Audit log</h1>
    <table class="table"><thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Record</th></tr></thead>
        <tbody>@foreach($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->actor->name ?? 'System' }}</td><td>{{ $log->action }}</td><td>{{ class_basename($log->auditable_type) }} {{ $log->auditable_id }}</td></tr>@endforeach</tbody></table>
    {{ $logs->links() }}
</div>
@endsection
