@extends('front.layouts.admin')
@section('page-title', $role->name)
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">{{ $role->name }}</h1>
    <form method="POST" action="{{ route('marketplace.roles.update', $role) }}">@csrf @method('PUT')
        <div class="row g-2 mb-3"><div class="col-md-4"><input name="name" class="form-control" value="{{ $role->name }}" required></div><div class="col-md-6"><input name="description" class="form-control" value="{{ $role->description }}"></div></div>
        @foreach($permissions as $group => $items)
            <h2 class="h6 mt-3">{{ $group }}</h2>
            <div class="row">
                @foreach($items as $permission)
                    <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains('id', $permission->id) || $role->slug === 'super-administrator') @disabled($role->slug === 'super-administrator' || !auth()->user()->hasPermission('permissions.manage'))> {{ $permission->slug }}</label></div>
                @endforeach
            </div>
        @endforeach
        @if(auth()->user()->hasPermission('permissions.manage'))
            <button class="btn btn-primary mt-3" type="submit">Save permissions</button>
        @endif
    </form>
</div>
@endsection
