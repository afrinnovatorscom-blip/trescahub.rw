<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShopProduct;
use App\Services\OrderService;
use App\Services\RequestWorkflowService;
use App\Support\CustomerTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontCommerceController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private RequestWorkflowService $requests,
    ) {}

    public function cart()
    {
        return view('front.pages.cart', ['lines' => $this->lines()]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $product = ShopProduct::query()->publicCatalog()->where('slug', $data['product'])->first();

        if (! $product || ! $product->canPurchase((int) $data['quantity'])) {
            return back()->withErrors(['product' => 'That product is not available in the requested quantity.']);
        }

        $cart = session('marketplace.cart', []);
        $cart[$product->slug] = min(100, ($cart[$product->slug] ?? 0) + (int) $data['quantity']);
        session(['marketplace.cart' => $cart]);

        return redirect()->route('shop')->with('success', 'Added to cart.');
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'quantities' => ['array'],
            'quantities.*' => ['integer', 'min:0', 'max:100'],
        ]);
        $cart = [];

        foreach ($data['quantities'] ?? [] as $slug => $quantity) {
            if ($quantity > 0 && is_string($slug)) {
                $cart[$slug] = (int) $quantity;
            }
        }

        session(['marketplace.cart' => $cart]);

        if ($request->expectsJson()) {
            $lines = $this->lines();

            return response()->json([
                'message' => 'Cart updated.',
                'quantities' => $cart,
                'item_count' => count($cart),
                'subtotal' => $lines->sum('line'),
                'lines' => $lines->mapWithKeys(fn (array $line) => [
                    $line['product']->slug => $line['line'],
                ]),
            ]);
        }

        return back()->with('success', 'Cart updated.');
    }

    public function checkout(Request $request)
    {
        $lines = $this->lines(true);
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }

        $customer = Auth::guard('customers')->user();
        $defaultAddress = $customer?->defaultAddress();

        return view('front.pages.checkout', [
            'lines' => $lines,
            'bankInstructions' => config('marketplace.bank_instructions'),
            'customer' => $customer,
            'defaultAddress' => $defaultAddress,
            'submissionKey' => (string) Str::uuid(),
        ]);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:cash_on_delivery,bank_transfer'],
            'submission_key' => ['nullable', 'uuid'],
        ]);

        $lines = $this->lines(true);
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }

        try {
            $order = $this->orders->place([
                'name' => $data['customer_name'],
                'email' => $data['email'] ?? Auth::guard('customers')->user()?->email,
                'phone' => $data['phone'],
                'delivery_address' => $data['delivery_address'],
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'payment_method' => $data['payment_method'],
            ], $lines->map(fn ($line) => ['product' => $line['product'], 'quantity' => $line['quantity']])->all(), null, Auth::guard('customers')->user(),
                'cart_checkout',
                $data['submission_key'] ?? null);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['cart' => $exception->getMessage()]);
        }

        session()->forget('marketplace.cart');

        return redirect()->route('checkout.done', $order->reference)->with('placed_order', $order->reference);
    }

    public function done(string $reference)
    {
        abort_unless(session('placed_order') === $reference, 404);
        $order = Order::query()->where('reference', $reference)->firstOrFail();

        return view('front.pages.order-placed', ['tracking' => CustomerTracking::present($order)]);
    }

    public function trackForm()
    {
        return view('front.pages.track-order');
    }

    public function track(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'verification' => ['required', 'string', 'max:255'],
        ]);

        $order = Order::query()->where('reference', $data['reference'])->first();
        $factor = trim($data['verification']);
        $storedPhone = $this->digits($order->phone ?? '');
        $storedEmail = strtolower($order->email ?? '');
        $phoneMatch = $storedPhone !== '' && hash_equals(hash('sha256', $storedPhone), hash('sha256', $this->digits($factor)));
        $emailMatch = $storedEmail !== '' && hash_equals(hash('sha256', $storedEmail), hash('sha256', strtolower($factor)));

        if (! $order || (! $phoneMatch && ! $emailMatch)) {
            return back()->withErrors([
                'reference' => 'We could not match that order. Check the reference and the phone or email used at checkout.',
            ])->withInput();
        }

        session(['marketplace.tracked_order' => $order->id]);

        return redirect()->route('track-order.result');
    }

    public function trackResult()
    {
        $order = Order::query()->find(session('marketplace.tracked_order'));
        abort_unless($order, 404);

        return view('front.pages.track-result', ['tracking' => CustomerTracking::present($order)]);
    }

    public function requestForm(Request $request)
    {
        $product = null;
        if ($request->filled('product')) {
            $product = ShopProduct::query()->publicCatalog()->where('slug', $request->product)->first();
        }

        return view('front.pages.product-request', [
            'product' => $product,
            'defaultAddress' => Auth::guard('customers')->user()?->defaultAddress(),
            'submissionKey' => (string) Str::uuid(),
        ]);
    }

    public function storeRequest(Request $request)
    {
        $data = $request->validate([
            'product_slug' => ['nullable', 'string'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'request_type' => ['nullable', 'in:purchase,quote'],
            'submission_key' => ['nullable', 'uuid'],
        ]);

        if (empty($data['product_slug']) && empty($data['product_name'])) {
            throw ValidationException::withMessages(['product_name' => 'Tell us which product you need.']);
        }

        try {
            $result = $this->requests->submit(
                $data,
                Auth::guard('customers')->user(),
                $data['submission_key'] ?? null,
            );
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        if ($result instanceof Order) {
            return redirect()->route('checkout.done', $result->reference)
                ->with('placed_order', $result->reference)
                ->with('success', 'Your product order has been submitted.');
        }

        return redirect()->route('product-requests.done', $result->reference)
            ->with('placed_request', $result->reference)
            ->with('success', 'Your product request has been submitted.');
    }

    public function requestDone(string $reference)
    {
        abort_unless(session('placed_request') === $reference, 404);

        return view('front.pages.product-request-done', ['reference' => $reference]);
    }

    public function accountOrders(Request $request)
    {
        $orders = $request->user()->orders()
            ->with([
                'items:id,order_id,product_name,quantity,line_total',
                'payments:id,order_id,amount',
                'deliveries:id,order_id,status',
            ])
            ->latest()
            ->paginate(15);

        return view('front.pages.account-orders', compact('orders'));
    }

    public function accountOrder(Request $request, string $reference)
    {
        $order = $this->customerOrder($request, $reference);

        return view('front.pages.account-order', [
            'order' => $order,
            'tracking' => CustomerTracking::present($order),
            'canCancel' => in_array($order->fulfillment_status, ['pending_confirmation', 'confirmed', 'processing', 'awaiting_stock'], true),
        ]);
    }

    public function accountOrderTracking(Request $request, string $reference)
    {
        $order = $this->customerOrder($request, $reference);

        return response()->json(['tracking' => CustomerTracking::present($order)]);
    }

    public function cancel(Request $request, string $reference)
    {
        $customer = Auth::guard('customers')->user();
        $order = Order::query()->where('reference', $reference)->where('customer_id', $customer->id)->firstOrFail();
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->orders->requestCancellation($order, $request->user(), $data['reason']);

        return back()->with('success', 'Cancellation request sent.');
    }

    private function lines(bool $purchaseCheck = false)
    {
        $cart = session('marketplace.cart', []);
        $products = ShopProduct::query()->publicCatalog()->whereIn('slug', array_keys($cart))->get()->keyBy('slug');

        return collect($cart)->map(function ($quantity, $slug) use ($products, $purchaseCheck) {
            $product = $products->get($slug);
            if (! $product) {
                return null;
            }
            if ($purchaseCheck && ! $product->canPurchase((int) $quantity)) {
                return null;
            }

            return ['product' => $product, 'quantity' => (int) $quantity, 'line' => (float) $product->price * (int) $quantity];
        })->filter()->values();
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    private function customerOrder(Request $request, string $reference): Order
    {
        return $request->user()->orders()
            ->where('reference', $reference)
            ->with([
                'items:id,order_id,product_name,sku,unit_price,quantity,line_total,fulfillment_status,assignment_status',
                'payments:id,order_id,amount,type,method,created_at',
                'histories:id,order_id,from_status,to_status,customer_message,created_at',
                'deliveries:id,order_id,order_item_id,reference,status,customer_message,dispatched_at,delivered_at,created_at',
            ])
            ->firstOrFail();
    }
}
