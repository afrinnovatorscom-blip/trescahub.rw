@extends('front.layouts.admin')
@section('page-title', 'Users')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <div class="d-flex justify-content-between"><h1 class="h3">Users</h1><a class="btn btn-primary" href="{{ route('marketplace.users.create') }}">New user</a></div>
    <table class="table align-middle"><thead><tr><th>Name</th><th>Email</th><th>Access</th></tr></thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <form method="POST" action="{{ route('marketplace.users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($roles as $role)
                                <label class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($user->roles->contains('id', $role->id))>
                                    {{ $role->name }}
                                </label>
                            @endforeach
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <select name="vendor_id" class="form-select form-select-sm" aria-label="Vendor account for {{ $user->name }}">
                                <option value="">No vendor account</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected($user->vendor_id == $vendor->id)>{{ $vendor->business_name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-dark" type="submit">Save access</button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody></table>
    {{ $users->links() }}
</div>
@endsection
