<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Models\PaymentTransaction;
use App\Services\MobileMoneyPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderPaymentController extends Controller
{
    public function __construct(private MobileMoneyPaymentService $payments) {}

    /**
     * Payment page for an order: shows the latest attempt, or the form to pay / try again.
     */
    public function show(Request $request, string $reference)
    {
        $order = $this->customerOrder($request, $reference);
        $transaction = $order->paymentTransactions()->latest('id')->first();

        if ($transaction?->status === 'pending') {
            return redirect()->route('checkout.payment.status', $transaction);
        }

        return $this->render($order, $transaction);
    }

    /**
     * Send a new mobile money prompt for an existing unpaid order.
     */
    public function pay(Request $request, string $reference)
    {
        $order = $this->customerOrder($request, $reference);

        if (! $this->payments->canPay($order)) {
            return redirect()->route('account.orders.show', $order->reference)
                ->withErrors(['payment' => 'This order does not need a payment.']);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:'.implode(',', MobileMoneyPaymentService::METHODS)],
            'payment_phone' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) {
                    if (MobileMoneyPaymentService::normalizePhone((string) $value) === null) {
                        $fail('Enter a valid Rwandan mobile number, such as 078 123 4567 or +250 78 123 4567.');
                    }
                },
            ],
        ]);

        $transaction = $this->payments->start($order, $data['payment_method'], $data['payment_phone'], $request->ip());

        return redirect()->route('checkout.payment.status', $transaction);
    }

    /**
     * Status page for one payment attempt. Answers JSON when polled by the page itself.
     */
    public function status(Request $request, PaymentTransaction $transaction)
    {
        $order = $transaction->order;
        $this->authorizeOrder($request, $order);
        session(['placed_order' => $order->reference]);

        $response = [];
        if ($transaction->status === 'pending') {
            $response = $this->payments->sync($transaction);
            $transaction->refresh();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $transaction->status,
                'order_payment_status' => $order->fresh()->payment_status,
                'message' => match ($transaction->status) {
                    'completed' => 'Payment completed successfully!',
                    'pending' => 'Approve the payment of RWF '.number_format((float) $transaction->amount).' on your phone.',
                    default => $transaction->failure_reason ?: ($response['message'] ?? 'The payment was not completed.'),
                },
            ]);
        }

        return $this->render($order->fresh(), $transaction);
    }

    /**
     * IntouchPay webhook. Always answers HTTP 200 so IntouchPay does not keep retrying.
     *
     * The callback is unauthenticated, so its claimed status is never trusted:
     * the transaction is re-checked against the IntouchPay status API before anything changes.
     */
    public function callback(Request $request)
    {
        Log::channel('payment')->info('IntouchPay Callback: Received', [
            'ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => $request->getContent(),
        ]);

        $requestTransactionId = null;
        $callbackData = [];
        $transaction = null;
        $error = null;

        try {
            $callbackData = $this->parseCallbackData($request);

            $requestTransactionId = $callbackData['requesttransactionid']
                ?? $callbackData['requestTransactionId']
                ?? $callbackData['request_transaction_id']
                ?? null;

            // IntouchPay may send transactionid as an integer, or only referenceno
            $transactionId = isset($callbackData['transactionid'])
                ? (string) $callbackData['transactionid']
                : ($callbackData['referenceno'] ?? null);

            $transaction = $requestTransactionId
                ? PaymentTransaction::query()->where('request_transaction_id', (string) $requestTransactionId)->first()
                : null;

            if (! $transaction) {
                Log::channel('payment')->warning('IntouchPay Callback: Transaction not found', [
                    'request_transaction_id' => $requestTransactionId,
                    'callback_data' => $callbackData,
                ]);
            } else {
                if (! $transaction->transaction_id && $transactionId) {
                    $transaction->update(['transaction_id' => $transactionId]);
                }

                $this->payments->sync($transaction, 'callback');
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();

            Log::channel('payment')->error('IntouchPay Callback: Error processing callback', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        $reply = [
            'message' => 'success',
            'success' => true,
            'request_id' => $requestTransactionId ?? 'unknown',
        ];

        // IntouchPay sometimes sends the "satausdesc" typo
        $statusDescription = $callbackData['statusdesc'] ?? ($callbackData['satausdesc'] ?? null);

        PaymentLog::record('callback', $transaction, [
            'request_transaction_id' => $requestTransactionId,
            'response_code' => isset($callbackData['responsecode']) ? (string) $callbackData['responsecode'] : null,
            'status' => $callbackData['status'] ?? null,
            'message' => $error
                ? 'Error: '.$error
                : ($transaction ? $statusDescription : 'Transaction not found'),
            'payload' => $callbackData ?: ['raw' => $request->getContent()],
            'response' => $reply,
            'ip_address' => $request->ip(),
        ]);

        return response()->json($reply, 200);
    }

    private function render(Order $order, ?PaymentTransaction $transaction)
    {
        $customer = request()->user('customers');

        // Lets the order confirmation page open once the payment succeeds.
        session(['placed_order' => $order->reference]);

        return view('front.pages.order-payment', [
            'order' => $order,
            'transaction' => $transaction,
            'canPay' => $this->payments->canPay($order),
            'amountDue' => $this->payments->outstandingAmount($order),
            'paymentMethod' => old('payment_method', $transaction?->method ?? $order->payment_method),
            'paymentPhone' => old('payment_phone', $transaction?->phone ?? $order->payment_phone ?? $order->phone ?? $customer?->phone ?? ''),
        ]);
    }

    /**
     * Callback body may be raw JSON, JSON wrapped in "jsonpayload", or form data.
     */
    private function parseCallbackData(Request $request): array
    {
        $decoded = json_decode($request->getContent(), true);

        if (is_array($decoded) && ! empty($decoded)) {
            return is_array($decoded['jsonpayload'] ?? null) ? $decoded['jsonpayload'] : $decoded;
        }

        if ($request->has('jsonpayload')) {
            $payload = $request->input('jsonpayload');

            return is_string($payload) ? (json_decode($payload, true) ?? []) : (array) $payload;
        }

        return $request->all();
    }

    private function customerOrder(Request $request, string $reference): Order
    {
        return $request->user('customers')->orders()->where('reference', $reference)->firstOrFail();
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless((int) $order->customer_id === (int) $request->user('customers')?->id, 404);
    }
}
