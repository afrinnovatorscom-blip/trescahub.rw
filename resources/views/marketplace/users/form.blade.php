@extends('front.layouts.admin')
@section('page-title', 'New user')
@section('content')
<div class="container py-4" style="max-width: 760px;">
    @include('marketplace.partials.flash')
    <h1 class="h3">New user</h1>
    <form method="POST" action="{{ route('marketplace.users.store') }}" class="row g-3">@csrf
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required value="{{ old('email') }}"></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
        <div class="col-md-6"><label class="form-label">Vendor account</label><select name="vendor_id" class="form-select"><option value="">None</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->business_name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Confirm password</label><input name="password_confirmation" type="password" class="form-control" required></div>
        <div class="col-12">@foreach($roles as $role)<label class="form-check"><input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}"> {{ $role->name }}</label>@endforeach</div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Create user</button></div>
    </form>
</div>
@endsection
