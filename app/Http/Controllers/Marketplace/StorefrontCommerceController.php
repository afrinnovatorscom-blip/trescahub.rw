<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductVariation;
use App\Models\ShopProduct;
use App\Services\MobileMoneyPaymentService;
use App\Services\OrderService;
use App\Services\RequestWorkflowService;
use App\Support\CustomerTracking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontCommerceController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private RequestWorkflowService $requests,
        private MobileMoneyPaymentService $mobileMoney,
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
            'variation_id' => ['nullable', 'integer'],
        ]);

        $product = ShopProduct::query()->publicCatalog()->with('variations')->where('slug', $data['product'])->first();

        if (! $product || ! $product->canPurchase((int) $data['quantity'])) {
            return back()->withErrors(['product' => 'That product is not available in the requested quantity.']);
        }

        $variation = $this->resolveVariation($product, isset($data['variation_id']) ? (int) $data['variation_id'] : null);
        $lineKey = $variation ? $product->slug.'|variation|'.$variation->id : $product->slug;
        $cart = session('marketplace.cart', []);
        $existing = $cart[$lineKey] ?? 0;
        $existingQuantity = is_array($existing) ? (int) ($existing['quantity'] ?? 0) : (int) $existing;
        $quantity = min(100, $existingQuantity + (int) $data['quantity']);
        $cart[$lineKey] = $variation
            ? ['quantity' => $quantity, 'variation_id' => $variation->id]
            : $quantity;
        session(['marketplace.cart' => $cart]);

        return redirect()->route('shop')->with('success', 'Product added to cart successfully.');
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'quantities' => ['array'],
            'quantities.*' => ['integer', 'min:0', 'max:100'],
        ]);
        $cart = [];

        $existingCart = session('marketplace.cart', []);
        foreach ($data['quantities'] ?? [] as $lineKey => $quantity) {
            if ($quantity > 0 && is_string($lineKey) && array_key_exists($lineKey, $existingCart)) {
                $entry = $existingCart[$lineKey];
                $variationId = is_array($entry) ? ($entry['variation_id'] ?? null) : null;
                $cart[$lineKey] = $variationId
                    ? ['quantity' => (int) $quantity, 'variation_id' => $variationId]
                    : (int) $quantity;
            }
        }

        session(['marketplace.cart' => $cart]);

        if ($request->expectsJson()) {
            $lines = $this->lines();

            return response()->json([
                'message' => 'Cart updated.',
                'quantities' => collect($cart)->map(
                    fn ($entry): int => is_array($entry) ? (int) ($entry['quantity'] ?? 0) : (int) $entry
                )->all(),
                'item_count' => count($cart),
                'subtotal' => $lines->sum('line'),
                'lines' => $lines->mapWithKeys(fn (array $line) => [
                    $line['key'] => $line['line'],
                ]),
            ]);
        }

        return back()->with('success', 'Cart updated.');
    }

    public function checkout(Request $request)
    {
        if ($request->filled('product')) {
            $data = $request->validate([
                'product' => ['required', 'string'],
                'quantity' => ['required', 'integer', 'min:1', 'max:100'],
                'variation_id' => ['nullable', 'integer'],
            ]);

            $product = ShopProduct::query()->publicCatalog()->with('variations')->where('slug', $data['product'])->first();
            if (! $product || ! $product->canPurchase((int) $data['quantity'])) {
                return redirect()->route('shop')->withErrors(['product' => 'That product is not available in the requested quantity.']);
            }

            $variation = $this->resolveVariation($product, isset($data['variation_id']) ? (int) $data['variation_id'] : null);
            $lineKey = $variation ? $product->slug.'|variation|'.$variation->id : $product->slug;
            session(['marketplace.cart' => [$lineKey => $variation
                ? ['quantity' => (int) $data['quantity'], 'variation_id' => $variation->id]
                : (int) $data['quantity']]]);
            session()->forget('marketplace.checkout');

            return redirect()->route('checkout');
        }

        $lines = $this->eligibleCheckoutLines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }

        $customer = Auth::guard('customers')->user();
        $defaultAddress = $customer?->defaultAddress();
        $details = session('marketplace.checkout.details', []);

        return view('front.pages.checkout', [
            'lines' => $lines,
            'customer' => $customer,
            'defaultAddress' => $defaultAddress,
            'checkoutDetails' => $details,
        ]);
    }

    public function saveCheckoutDetails(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->eligibleCheckoutLines()->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart contains items that are no longer available. Review your cart and try again.',
            ]);
        }

        $checkout = session('marketplace.checkout', []);
        $checkout['details'] = $data;
        $checkout['submission_key'] ??= (string) Str::uuid();
        session(['marketplace.checkout' => $checkout]);

        return redirect()->route('checkout.payment');
    }

    public function payment(Request $request)
    {
        $lines = $this->eligibleCheckoutLines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }
        if (! session()->has('marketplace.checkout.details')) {
            return redirect()->route('checkout');
        }

        return view('front.pages.checkout-payment', [
            'customer' => Auth::guard('customers')->user(),
            'checkoutDetails' => session('marketplace.checkout.details'),
            'paymentMethod' => old('payment_method', session('marketplace.checkout.payment_method')),
            'paymentPhone' => old(
                'payment_phone',
                session('marketplace.checkout.payment_phone')
                    ?? session('marketplace.checkout.details.phone')
                    ?? Auth::guard('customers')->user()?->phone
                    ?? ''
            ),
        ]);
    }

    public function place(Request $request)
    {
        $checkout = session('marketplace.checkout', []);
        if (! isset($checkout['details']) || ! isset($checkout['submission_key'])) {
            return redirect()->route('checkout')->withErrors([
                'checkout' => 'Please confirm your checkout details before choosing a payment method.',
            ]);
        }

        $detailsValidator = Validator::make($checkout['details'], [
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($detailsValidator->fails()) {
            return redirect()->route('checkout')
                ->withErrors($detailsValidator)
                ->withInput($checkout['details']);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:mtn_mobile_money,airtel_money'],
            'payment_phone' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $digits = preg_replace('/\D+/', '', $value);
                    if (str_starts_with($digits, '250')) {
                        $digits = substr($digits, 3);
                    }
                    if (str_starts_with($digits, '0')) {
                        $digits = substr($digits, 1);
                    }
                    if (! preg_match('/^7\d{8}$/', $digits)) {
                        $fail('Enter a valid Rwandan mobile number, such as 078 123 4567 or +250 78 123 4567.');
                    }
                },
            ],
        ]);

        $lines = $this->eligibleCheckoutLines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart');
        }
        $checkout['payment_method'] = $data['payment_method'];
        $checkout['payment_phone'] = $data['payment_phone'];
        session(['marketplace.checkout' => $checkout]);

        try {
            $order = $this->orders->place([
                'name' => $checkout['details']['customer_name'],
                'email' => $checkout['details']['email'] ?? Auth::guard('customers')->user()?->email,
                'phone' => $checkout['details']['phone'],
                'payment_phone' => $data['payment_phone'],
                'delivery_address' => $checkout['details']['delivery_address'],
                'delivery_notes' => $checkout['details']['delivery_notes'] ?? null,
                'payment_method' => $data['payment_method'],
            ], $lines->map(fn ($line) => [
                'product' => $line['product'],
                'quantity' => $line['quantity'],
                'variation_id' => $line['variation']?->id,
            ])->all(), null, Auth::guard('customers')->user(),
                'cart_checkout',
                $checkout['submission_key']);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['cart' => $exception->getMessage()]);
        }

        session()->forget('marketplace.cart');
        session()->forget('marketplace.checkout');

        // Push the mobile money prompt to the customer's phone. A failed request still lands on the
        // status page, which explains the problem and lets the customer try again for this order.
        if ($this->mobileMoney->canPay($order)) {
            $transaction = $this->mobileMoney->start($order, $data['payment_method'], $data['payment_phone'], $request->ip());

            return redirect()->route('checkout.payment.status', $transaction);
        }

        return redirect()->route('checkout.done', $order->reference)->with('placed_order', $order->reference);
    }

    private function eligibleCheckoutLines()
    {
        $lines = $this->lines(true);
        $cart = session('marketplace.cart', []);

        if ($lines->count() !== count($cart)) {
            return collect();
        }

        return $lines;
    }

    public function done(Request $request, string $reference)
    {
        abort_unless(session('placed_order') === $reference, 404);
        $order = Order::query()->where('reference', $reference)->firstOrFail();
        $customer = $request->user('customers');
        $trackingUrl = $customer && (int) $order->customer_id === (int) $customer->id
            ? route('account.orders.show', $order->reference)
            : route('track-order');

        return view('front.pages.order-placed', [
            'tracking' => CustomerTracking::present($order),
            'trackingUrl' => $trackingUrl,
        ]);
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
        $variation = null;
        if ($request->filled('product')) {
            $product = ShopProduct::query()->publicCatalog()->with('variations')->where('slug', $request->product)->first();
            if ($product) {
                $requestedVariationId = $request->integer('variation_id') ?: null;
                $variation = $this->resolveVariation($product, $requestedVariationId, false)
                    ?? $product->variations->firstWhere('is_default', true);
            }
        }

        return view('front.pages.product-request', [
            'product' => $product,
            'selectedVariation' => $variation,
            'defaultAddress' => Auth::guard('customers')->user()?->defaultAddress(),
            'submissionKey' => (string) Str::uuid(),
            'requestedQuantity' => max(1, $request->integer('quantity', 1)),
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
            'variation_id' => ['nullable', 'integer'],
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
                'items:id,order_id,product_name,variation_label,variation_value,quantity,line_total',
                'deliveries:id,order_id,reference,status',
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
            'canPay' => $this->mobileMoney->canPay($order),
        ]);
    }

    public function downloadOrderReceipt(Request $request, string $reference)
    {
        $order = $this->customerOrder($request, $reference);
        $websiteHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $websiteUrl = $websiteHost && ! in_array($websiteHost, ['localhost', '127.0.0.1', '::1'], true)
            ? $websiteHost
            : null;
        $generatedAt = now();

        return Pdf::loadView('front.pages.account-order-receipt', compact('order', 'websiteUrl', 'generatedAt'))
            ->setPaper('a4', 'portrait')
            ->download('receipt-'.$order->reference.'.pdf');
    }

    public function confirmOrderDelivery(Request $request, string $reference, string $deliveryReference)
    {
        /** @var Customer $customer */
        $customer = $request->user('customers');
        $order = $customer->orders()->where('reference', $reference)->firstOrFail();
        $delivery = $order->deliveries()
            ->where('reference', $deliveryReference)
            ->where('status', 'in_transit')
            ->firstOrFail();
        abort_unless(CustomerTracking::canConfirmDelivery($order, $delivery), 404);

        $this->orders->confirmDeliveryByCustomer($delivery, $customer);

        return redirect()->route('account.orders.show', $order->reference)
            ->with('success', 'Delivery marked as delivered.');
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
        $slugs = collect(array_keys($cart))->map(fn (string $key) => $this->cartProductSlug($key))->unique()->all();
        $products = ShopProduct::query()->publicCatalog()->with('variations')->whereIn('slug', $slugs)->get()->keyBy('slug');

        return collect($cart)->map(function ($entry, $key) use ($products, $purchaseCheck) {
            [$slug, $keyVariationId] = $this->cartKey($key);
            $quantity = is_array($entry) ? (int) ($entry['quantity'] ?? 0) : (int) $entry;
            $variationId = is_array($entry) && ! empty($entry['variation_id'])
                ? (int) $entry['variation_id']
                : $keyVariationId;
            $product = $products->get($slug);
            if (! $product) {
                return null;
            }

            $variation = $this->resolveVariation($product, $variationId, false);
            if ($product->variations->isNotEmpty() && ! $variation && $variationId === null) {
                $variation = $product->variations->firstWhere('is_default', true);
            }

            if (($product->variations->isNotEmpty() && ! $variation)
                || ($variationId && ! $variation)
                || ($purchaseCheck && ! $product->canPurchase($quantity))) {
                return null;
            }

            $unitPrice = (float) ($variation?->price ?? $product->price);

            return [
                'key' => $key,
                'product' => $product,
                'variation' => $variation,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line' => $unitPrice * $quantity,
            ];
        })->filter()->values();
    }

    private function resolveVariation(ShopProduct $product, ?int $variationId, bool $requireSelection = true): ?ProductVariation
    {
        $variations = $product->relationLoaded('variations')
            ? $product->variations
            : $product->variations()->get();

        if ($variations->isEmpty()) {
            if ($variationId !== null) {
                if (! $requireSelection) {
                    return null;
                }

                throw ValidationException::withMessages([
                    'variation_id' => 'That variation is not available for this product.',
                ]);
            }

            return null;
        }

        if ($variationId === null) {
            if ($requireSelection) {
                throw ValidationException::withMessages([
                    'variation_id' => 'Choose a product variation before continuing.',
                ]);
            }

            return null;
        }

        $variation = $variations->firstWhere('id', $variationId);
        if (! $variation) {
            if (! $requireSelection) {
                return null;
            }

            throw ValidationException::withMessages([
                'variation_id' => 'That variation is not available for this product.',
            ]);
        }

        return $variation;
    }

    private function cartKey(string $key): array
    {
        if (preg_match('/^(.*)\|variation\|(\d+)$/', $key, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        return [$key, null];
    }

    private function cartProductSlug(string $key): string
    {
        return $this->cartKey($key)[0];
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
                'items:id,order_id,shop_product_id,vendor_id,product_name,sku,product_variation_id,variation_label,variation_value,unit_price,quantity,line_total,fulfillment_status,assignment_status,created_at,updated_at',
                'items.product:id,product_name,slug,thumbnail',
                'items.vendor:id,business_name',
                'payments:id,order_id,amount,type,method,created_at',
                'histories:id,order_id,from_status,to_status,customer_message,created_at',
                'deliveries:id,order_id,order_item_id,delivery_officer_id,reference,status,customer_message,delivery_method,courier_name,delivery_phone,tracking_reference,dispatched_at,delivered_at,created_at,updated_at',
            ])
            ->firstOrFail();
    }
}
