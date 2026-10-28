@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 760px;">
    <h1 class="h3 mb-3">Checkout</h1>
    @include('marketplace.partials.flash')
    <ul class="list-group mb-4">
        @foreach($lines as $line)
            <li class="list-group-item d-flex justify-content-between">
                <span>{{ $line['product']->product_name }} × {{ $line['quantity'] }}</span>
                <strong>RWF {{ number_format($line['line'], 0, '.', ',') }}</strong>
            </li>
        @endforeach
    </ul>
    <form method="POST" action="{{ route('checkout.place') }}" class="row g-3">
        @csrf
        <input type="hidden" name="submission_key" value="{{ $submissionKey }}">
        <div class="col-md-6"><label class="form-label" for="customer_name">Name</label><input id="customer_name" name="customer_name" class="form-control" required value="{{ old('customer_name', $defaultAddress?->recipient_name ?? $customer?->name ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" class="form-control" required value="{{ old('phone', $defaultAddress?->recipient_phone ?? $customer?->phone ?? '') }}"></div>
        <div class="col-12"><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" class="form-control" value="{{ old('email', $defaultAddress?->recipient_email ?? $customer?->email ?? '') }}"></div>
        <div class="col-12"><label class="form-label" for="delivery_address">Delivery address</label><textarea id="delivery_address" name="delivery_address" class="form-control" required>{{ old('delivery_address', $defaultAddress?->formattedAddress()) }}</textarea></div>
        <div class="col-12"><label class="form-label" for="delivery_notes">Delivery notes</label><textarea id="delivery_notes" name="delivery_notes" class="form-control">{{ old('delivery_notes', $defaultAddress?->delivery_instructions) }}</textarea></div>
        @if($customer)
            <div class="col-12">
                <a href="{{ route('account.profile') }}" class="small text-decoration-none">Manage saved addresses</a>
            </div>
        @endif
        <div class="col-12">
            <label class="form-label" for="payment_method">Payment</label>
            <select id="payment_method" name="payment_method" class="form-select">
                <option value="cash_on_delivery">Pay on delivery</option>
                <option value="bank_transfer">Bank transfer</option>
            </select>
            <p class="small text-muted mt-2">{{ $bankInstructions }}</p>
        </div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Place order</button></div>
    </form>
</div></main>
@endsection
