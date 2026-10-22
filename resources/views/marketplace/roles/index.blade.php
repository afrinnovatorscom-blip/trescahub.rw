@extends('front.layouts.admin')
@section('page-title', 'Roles')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">Roles</h1>
    @if(auth()->user()->hasPermission('roles.manage'))
        <form method="POST" action="{{ route('marketplace.roles.store') }}" class="row g-2 mb-4">@csrf
            <div class="col-md-4"><input name="name" class="form-control" placeholder="New role name" required></div>
            <div class="col-md-4"><input name="description" class="form-control" placeholder="Description"></div>
            <div class="col-auto"><button class="btn btn-primary">Create role</button></div>
        </form>
    @endif
    <table class="table"><thead><tr><th>Role</th><th>Users</th><th>Permissions</th><th></th></tr></thead>
        <tbody>@foreach($roles as $role)<tr><td>{{ $role->name }}</td><td>{{ $role->users_count }}</td><td>{{ $role->permissions_count }}</td><td><a href="{{ route('marketplace.roles.edit', $role) }}">Edit</a></td></tr>@endforeach</tbody></table>
</div>
@endsection
