<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\PaymentLog;
use App\Models\PaymentTransaction;
use App\Models\Payout;
use App\Services\AuditLogger;
use App\Services\IntouchPayService;
use App\Services\MobileMoneyPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin: mobile money payment records, IntouchPay logs, wallet balance and payouts.
 * Viewing needs "finance.view"; sending payouts additionally needs "payouts.send".
 */
class PaymentAdminController extends Controller
{
    public function __construct(
        private IntouchPayService $intouchPay,
        private MobileMoneyPaymentService $payments,
    ) {}

    /* ============ Payment records ============ */

    public function index(Request $request)
    {
        $this->authorizeView($request);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,completed,failed'],
            'method' => ['nullable', 'in:'.implode(',', MobileMoneyPaymentService::METHODS)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $transactions = PaymentTransaction::query()
            ->with('order:id,reference,customer_name,total,payment_status')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('request_transaction_id', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('order', fn ($order) => $order
                            ->where('reference', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('method', $method))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $completed = PaymentTransaction::query()->where('status', 'completed');
        $stats = [
            'collected' => (float) (clone $completed)->sum('amount'),
            'completed' => (clone $completed)->count(),
            'pending' => PaymentTransaction::query()->where('status', 'pending')->count(),
            'failed' => PaymentTransaction::query()->where('status', 'failed')->count(),
            'today' => (float) (clone $completed)->whereDate('paid_at', today())->sum('amount'),
        ];

        return view('marketplace.payments.index', compact('transactions', 'stats', 'filters'));
    }

    /** Ask IntouchPay again for the status of a pending payment. */
    public function recheck(Request $request, PaymentTransaction $transaction)
    {
        $this->authorizeView($request);

        if ($transaction->status !== 'pending') {
            return back()->with('success', 'Payment '.$transaction->request_transaction_id.' is already '.$transaction->status.'.');
        }

        $response = $this->payments->sync($transaction, 'admin');
        $transaction->refresh();

        return back()->with('success', 'Payment status: '.ucfirst($transaction->status)
            .($response['message'] ?? null ? ' ('.$response['message'].')' : '').'.');
    }

    /* ============ IntouchPay logs ============ */

    public function logs(Request $request)
    {
        $this->authorizeView($request);

        $filters = $request->validate([
            'type' => ['nullable', 'in:'.implode(',', array_keys(PaymentLog::TYPES))],
            'search' => ['nullable', 'string', 'max:120'],
            'unmatched' => ['nullable', 'boolean'],
        ]);

        $logs = PaymentLog::query()
            ->with('transaction.order:id,reference')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('request_transaction_id', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('response_code', $search)
                        ->orWhereHas('transaction.order', fn ($order) => $order->where('reference', 'like', "%{$search}%"));
                });
            })
            ->when($request->boolean('unmatched'), fn ($query) => $query->whereNull('payment_transaction_id'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('marketplace.payments.logs', compact('logs', 'filters'));
    }

    /* ============ Wallet & payouts ============ */

    public function wallet(Request $request)
    {
        $this->authorizeView($request);

        $balanceResponse = $this->intouchPay->getBalance();
        $balance = $this->balanceFrom($balanceResponse);

        $stats = [
            'collected' => (float) PaymentTransaction::query()->where('status', 'completed')->sum('amount'),
            'paid_out' => (float) Payout::query()->where('status', 'successful')->sum('amount'),
            'pending_out' => (float) Payout::query()->where('status', 'pending')->sum('amount'),
            'payout_count' => Payout::query()->where('status', 'successful')->count(),
        ];

        return view('marketplace.payments.wallet', [
            'balance' => $balance,
            'balanceError' => $balance === null ? ($balanceResponse['message'] ?? 'Could not load balance') : null,
            'stats' => $stats,
            'payouts' => Payout::query()->with('user:id,name')->latest('id')->paginate(15),
            'canSendPayouts' => $request->user()->hasPermission('payouts.send'),
            'accountNo' => (string) config('services.intouchpay.account_no'),
        ]);
    }

    /** Live balance as JSON, for the "Check balance" button. */
    public function balance(Request $request)
    {
        $this->authorizeView($request);

        $response = $this->intouchPay->getBalance();
        $balance = $this->balanceFrom($response);

        if ($balance === null) {
            return response()->json(['success' => false, 'message' => $response['message'] ?? 'Could not load balance'], 502);
        }

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'formatted' => 'RWF '.number_format($balance),
            'checked_at' => now()->format('M j, g:i:s A'),
        ]);
    }

    /** Send money from the IntouchPay account to a mobile money number. Cannot be undone. */
    public function payout(Request $request)
    {
        $this->authorizePayouts($request);

        $request->merge([
            'phone' => preg_replace('/\s+/', '', (string) $request->input('phone')),
            'phone_confirmation' => preg_replace('/\s+/', '', (string) $request->input('phone_confirmation')),
        ]);
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20', 'regex:#^(07|\+?2507)[0-9]{8}$#'],
            // The number is typed twice to avoid sending money to a mistyped number.
            'phone_confirmation' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) use ($request) {
                $first = MobileMoneyPaymentService::normalizePhone((string) $request->input('phone'));
                if ($first === null || MobileMoneyPaymentService::normalizePhone((string) $value) !== $first) {
                    $fail('The two phone numbers do not match. Please type the recipient number again.');
                }
            }],
            'amount' => ['required', 'numeric', 'min:100', 'max:5000000'],
            'reason' => ['required', 'string', 'max:100'],
            'confirm' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a valid MTN or Airtel number, e.g. 0788123456.',
            'phone_confirmation.required' => 'Type the recipient number a second time to confirm it.',
            'confirm.accepted' => 'Please confirm the payout details.',
        ]);

        $amount = (int) round((float) $data['amount']);
        $phone = MobileMoneyPaymentService::normalizePhone($data['phone']);

        // Refuse payouts larger than the available balance.
        $balance = $this->balanceFrom($this->intouchPay->getBalance());
        if ($balance !== null && $amount > $balance) {
            return back()
                ->withErrors(['amount' => 'Amount is higher than the available balance (RWF '.number_format($balance).').'])
                ->withInput();
        }

        $payout = Payout::query()->create([
            'user_id' => $request->user()->id,
            'mobile_phone' => $phone,
            'amount' => $amount,
            'reason' => $data['reason'],
            'status' => 'pending',
            'request_transaction_id' => 'OUT_'.now()->format('YmdHis').'_'.strtoupper(bin2hex(random_bytes(4))),
        ]);

        $response = $this->intouchPay->requestDeposit($phone, $amount, $payout->request_transaction_id, $data['reason']);
        $code = (string) ($response['responsecode'] ?? '');

        $payout->update([
            // 2001 = deposit completed; any other accepted reply is still being processed.
            'status' => ($response['success'] ?? false) ? ($code === '2001' ? 'successful' : 'pending') : 'failed',
            'reference_id' => ($response['referenceid'] ?? null) ?: ($response['transactionid'] ?? null),
            'response_code' => $code ?: null,
            'message' => mb_substr((string) ($response['message'] ?? $response['statusdesc'] ?? ''), 0, 255) ?: null,
            'response' => $response,
        ]);

        AuditLogger::log($request->user(), 'payout.sent', $payout, [
            'amount' => $amount,
            'phone' => $phone,
            'status' => $payout->status,
            'response_code' => $payout->response_code,
        ]);
        Log::channel('payment')->info('Admin payout', [
            'payout_id' => $payout->id,
            'admin_id' => $request->user()->id,
            'status' => $payout->status,
        ]);

        if ($payout->status === 'failed') {
            return back()->withErrors(['payout' => 'Payout failed: '.($payout->message ?: 'IntouchPay rejected the request.')])->withInput();
        }

        return back()->with('success', ($payout->status === 'successful' ? 'Sent' : 'Payout submitted:').' RWF '.number_format($amount).' to '.$phone.'.');
    }

    /** Re-check a payout with the IntouchPay status API. */
    public function refreshPayout(Request $request, Payout $payout)
    {
        $this->authorizeView($request);

        if (! $payout->reference_id) {
            return back()->withErrors(['payout' => 'This payout has no IntouchPay reference to check.']);
        }

        $response = $this->intouchPay->getTransactionStatus($payout->request_transaction_id, $payout->reference_id);
        $status = strtoupper(trim((string) ($response['status'] ?? '')));
        $ok = (bool) ($response['success'] ?? false);
        $code = (string) ($response['responsecode'] ?? '');

        $newStatus = match (true) {
            $ok && ($code === '2001' || in_array($status, ['SUCCESSFUL', 'SUCCESSFULL'], true)) => 'successful',
            $ok && in_array($status, ['FAILED', 'FAILURE'], true) => 'failed',
            $ok && $status === 'PENDING' => 'pending',
            default => $payout->status,
        };

        $payout->update([
            'status' => $newStatus,
            'message' => mb_substr((string) ($response['message'] ?? $payout->message), 0, 255),
        ]);

        return back()->with('success', 'Payout status: '.ucfirst($newStatus).'.');
    }

    private function balanceFrom(array $response): ?float
    {
        // The balance reply is {"status": "success", "balance": 125000} — it may have no "success" field.
        return isset($response['balance']) && is_numeric($response['balance']) ? (float) $response['balance'] : null;
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('finance.view'), 403);
    }

    private function authorizePayouts(Request $request): void
    {
        abort_unless($request->user()?->hasPermission('finance.view') && $request->user()->hasPermission('payouts.send'), 403);
    }
}
