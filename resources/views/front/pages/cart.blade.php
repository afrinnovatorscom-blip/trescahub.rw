@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 860px;">
    <h1 class="h3 mb-3">Your cart</h1>
    @include('marketplace.partials.flash')
    @if($lines->isEmpty())
        <p class="text-muted">Your cart is empty. <a href="{{ route('shop') }}">Continue shopping</a>.</p>
    @else
        <form method="POST" action="{{ route('cart.update') }}">
            @csrf @method('PATCH')
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr></thead>
                    <tbody>
                    @foreach($lines as $line)
                        <tr>
                            <td>{{ $line['product']->product_name }}</td>
                            <td>{{ $line['product']->formatted_price }}</td>
                            <td style="max-width:120px"><input class="form-control" type="number" min="0" max="100" name="quantities[{{ $line['product']->slug }}]" value="{{ $line['quantity'] }}"></td>
                            <td>RWF {{ number_format($line['line'], 0, '.', ',') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark" type="submit">Update cart</button>
                <a class="btn btn-primary" href="{{ route('checkout') }}">Checkout</a>
            </div>
        </form>
    @endif
</div></main>
@endsection
