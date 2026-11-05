<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentLog;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Collects order payments by mobile money through IntouchPay.
 *
 * Flow: start() pushes a prompt to the customer's phone -> the customer approves with their PIN ->
 * IntouchPay calls our webhook and/or the status page polls -> sync() asks IntouchPay for the real
 * status -> on success the order gets an OrderPayment and becomes "paid".
 */
class MobileMoneyPaymentService
{
    public const METHODS = ['mtn_mobile_money', 'airtel_money'];

    /** A pending prompt younger than this is reused instead of sending a second one. */
    private const PENDING_REUSE_MINUTES = 3;

    public function __construct(
        private IntouchPayService $intouchPay,
        private MarketplaceNotifier $notifier,
    ) {}

    /**
     * Convert +2507XXXXXXXX / 2507XXXXXXXX / 7XXXXXXXX / 07XXXXXXXX to 07XXXXXXXX.
     */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '250')) {
            $digits = substr($digits, 3);
        }
        $digits = ltrim($digits, '0');

        return preg_match('/^7\d{8}$/', $digits) ? '0'.$digits : null;
    }

    /** Amount still owed on the order, in whole RWF. */
    public function outstandingAmount(Order $order): int
    {
        $outstandingCents = (int) round((float) $order->total * 100) - $order->recordedPaymentAmountInCents();

        return max(0, (int) ceil($outstandingCents / 100));
    }

    public function canPay(Order $order): bool
    {
        return $order->payment_status !== 'paid'
            && ! in_array($order->fulfillment_status, ['cancelled', 'cancellation_requested', 'returned'], true)
            && $this->outstandingAmount($order) > 0;
    }

    /**
     * Send a mobile money prompt for the order's outstanding balance.
     * Always returns the transaction; check its status ("failed" when IntouchPay rejected the request).
     */
    public function start(Order $order, string $method, string $phone, ?string $ip = null): PaymentTransaction
    {
        $localPhone = self::normalizePhone($phone);
        if ($localPhone === null || ! in_array($method, self::METHODS, true)) {
            throw new \InvalidArgumentException('Invalid mobile money method or phone number.');
        }

        // Protect against double submits: reuse a prompt that is still waiting for the customer.
        $recent = $order->paymentTransactions()
            ->where('status', 'pending')
            ->where('phone', $localPhone)
            ->where('created_at', '>', now()->subMinutes(self::PENDING_REUSE_MINUTES))
            ->latest('id')
            ->first();
        if ($recent) {
            return $recent;
        }

        $transaction = PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'provider' => 'intouchpay',
            'method' => $method,
            'phone' => $localPhone,
            'amount' => $this->outstandingAmount($order),
            'currency' => 'RWF',
            'status' => 'pending',
            'request_transaction_id' => 'TH_'.$order->reference.'_'.now()->format('YmdHis').'_'.Str::upper(Str::random(6)),
        ]);

        $response = $this->intouchPay->requestPayment($localPhone, (int) $transaction->amount, $transaction->request_transaction_id);
        $success = ($response['success'] ?? false) === true;

        PaymentLog::record('request', $transaction, [
            'response_code' => isset($response['responsecode']) ? (string) $response['responsecode'] : null,
            'status' => $success ? 'Pending' : 'Failed',
            'message' => $response['message'] ?? null,
            'payload' => [
                'mobilephone' => '25'.$localPhone,
                'amount' => (int) $transaction->amount,
                'requesttransactionid' => $transaction->request_transaction_id,
                'callbackurl' => $this->intouchPay->getCallbackUrl(),
            ],
            'response' => $response,
            'ip_address' => $ip,
        ]);

        if ($success) {
            $transaction->update([
                'transaction_id' => ($response['referenceid'] ?? null) ?: ($response['transactionid'] ?? null),
            ]);
        } else {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => mb_substr((string) ($response['message'] ?? 'Payment request failed.'), 0, 255),
            ]);
        }

        $order->update(['payment_method' => $method, 'payment_phone' => $phone]);

        return $transaction->refresh();
    }

    /**
     * Ask IntouchPay for the real status of a pending transaction and apply it.
     * $trigger is "callback" or "status_page" (only used for logging).
     */
    public function sync(PaymentTransaction $transaction, string $trigger = 'status_page'): array
    {
        if ($transaction->status !== 'pending' || ! $transaction->transaction_id) {
            return [];
        }

        $previousStatus = $transaction->status;
        $response = $this->intouchPay->getTransactionStatus($transaction->request_transaction_id, (string) $transaction->transaction_id);

        $responseCode = (string) ($response['responsecode'] ?? '');
        $status = strtoupper(trim((string) ($response['status'] ?? '')));
        $ok = (bool) ($response['success'] ?? false);

        if ($ok && $responseCode === '01' && in_array($status, ['SUCCESSFUL', 'SUCCESSFULL'], true)) {
            $this->markCompleted($transaction);
        } elseif ($ok && in_array($status, ['FAILED', 'FAILURE'], true)) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => mb_substr((string) ($response['message'] ?? 'The payment was declined or cancelled.'), 0, 255),
            ]);

            Log::channel('payment')->warning('IntouchPay: Payment failed', [
                'payment_transaction_id' => $transaction->id,
                'request_transaction_id' => $transaction->request_transaction_id,
                'status_response' => $response,
            ]);
        }

        $transaction->refresh();

        // The status page polls every few seconds, so only log checks that matter:
        // callback-triggered ones, ones that changed the status, and errors.
        if ($trigger === 'callback' || $transaction->status !== $previousStatus || ! $ok) {
            PaymentLog::record('status_check', $transaction, [
                'response_code' => $responseCode ?: null,
                'status' => $response['status'] ?? null,
                'message' => trim(($response['message'] ?? '')." (via {$trigger}, {$previousStatus} → {$transaction->status})"),
                'payload' => [
                    'requesttransactionid' => $transaction->request_transaction_id,
                    'transactionid' => $transaction->transaction_id,
                ],
                'response' => $response,
            ]);
        }

        return $response;
    }

    /**
     * Mark the transaction completed and record the payment on the order. Safe to call twice.
     */
    private function markCompleted(PaymentTransaction $transaction): void
    {
        $paidOrder = null;

        DB::transaction(function () use ($transaction, &$paidOrder) {
            $locked = PaymentTransaction::query()->whereKey($transaction->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === 'completed') {
                return;
            }

            $locked->update(['status' => 'completed', 'paid_at' => now(), 'failure_reason' => null]);

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();
            $outstanding = $this->outstandingAmount($order);

            if ($outstanding <= 0) {
                // Money arrived for an order that is already settled (e.g. the customer approved two prompts).
                Log::channel('payment')->warning('IntouchPay: Payment received for an already paid order. Refund may be needed.', [
                    'order' => $order->reference,
                    'payment_transaction_id' => $locked->id,
                    'amount' => $locked->amount,
                ]);

                return;
            }

            $amount = min((float) $locked->amount, (float) $outstanding);
            OrderPayment::query()->create([
                'order_id' => $order->id,
                'recorded_by' => null,
                'amount' => number_format($amount, 2, '.', ''),
                'type' => $amount >= $outstanding ? 'full' : 'partial',
                'method' => $locked->method,
                'submission_key' => (string) Str::uuid(),
            ]);

            $previousStatus = $order->payment_status;
            $order->payment_status = $amount >= $outstanding ? 'paid' : 'partially_paid';
            $order->payment_method = $locked->method;
            $order->save();

            AuditLogger::log(null, 'order.payment_recorded', $order, [
                'amount' => number_format($amount, 2, '.', ''),
                'type' => $amount >= $outstanding ? 'full' : 'partial',
                'method' => $locked->method,
                'from_status' => $previousStatus,
                'to_status' => $order->payment_status,
            ], [
                'source' => 'intouchpay',
                'request_transaction_id' => $locked->request_transaction_id,
                'transaction_id' => $locked->transaction_id,
            ]);

            Log::channel('payment')->info('IntouchPay: Payment completed', [
                'order' => $order->reference,
                'payment_transaction_id' => $locked->id,
                'amount' => $amount,
            ]);

            $paidOrder = [$order, $amount, $locked->method];
        });

        if ($paidOrder) {
            try {
                $this->notifier->orderPaid(...$paidOrder);
            } catch (\Throwable $e) {
                Log::channel('payment')->error('Payment notification failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
