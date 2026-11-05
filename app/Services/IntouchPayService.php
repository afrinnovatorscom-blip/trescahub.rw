<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the IntouchPay mobile money API (MTN MoMo and Airtel Money, Rwanda).
 * Every method returns an array and never throws.
 */
class IntouchPayService
{
    private string $apiUrl;
    private string $username;
    private string $partnerPassword;
    private string $accountNo;

    public function __construct()
    {
        $this->apiUrl = rtrim((string) config('services.intouchpay.api_url'), '/');
        $this->username = (string) config('services.intouchpay.username');
        $this->partnerPassword = (string) config('services.intouchpay.password');
        $this->accountNo = (string) config('services.intouchpay.account_no');
    }

    public function getCallbackUrl(): string
    {
        $configured = trim((string) config('services.intouchpay.callback_url'));

        return $configured !== ''
            ? $configured
            : rtrim((string) config('app.url'), '/').'/api/intouchpay/callback';
    }

    /**
     * Push a mobile money prompt to the customer's phone.
     *
     * @param  string  $mobilePhone  Local format, e.g. 0788123456
     */
    public function requestPayment(string $mobilePhone, float|int $amount, string $requestTransactionId): array
    {
        $timestamp = $this->timestamp();

        $requestData = [
            'username' => $this->username,
            'timestamp' => $timestamp,
            'amount' => $amount,
            'password' => $this->generatePassword($timestamp),
            'mobilephone' => '25'.$mobilePhone,
            'requesttransactionid' => $requestTransactionId,
            'accountno' => $this->accountNo,
            'callbackurl' => $this->getCallbackUrl(),
        ];

        Log::channel('payment')->info('IntouchPay RequestPayment: Sending request', [
            'data_sent' => array_merge($requestData, ['password' => '***HIDDEN***']),
        ]);

        try {
            $response = $this->http()->asForm()->post($this->apiUrl.'/requestpayment/', $requestData);

            if ($error = $this->htmlError($response)) {
                Log::channel('payment')->error('IntouchPay RequestPayment: HTML response', $error + ['request_transaction_id' => $requestTransactionId]);

                return ['success' => false, 'requesttransactionid' => $requestTransactionId] + $error;
            }

            $json = $response->json();

            if (! is_array($json)) {
                Log::channel('payment')->error('IntouchPay RequestPayment: Invalid JSON response', [
                    'request_transaction_id' => $requestTransactionId,
                    'response_preview' => substr($response->body(), 0, 200),
                ]);

                return [
                    'success' => false,
                    'responsecode' => '5006',
                    'requesttransactionid' => $requestTransactionId,
                    'message' => 'Invalid JSON response from IntouchPay',
                ];
            }

            Log::channel('payment')->info('IntouchPay RequestPayment: Response received', [
                'request_transaction_id' => $requestTransactionId,
                'response' => $json,
                'http_status' => $response->status(),
            ]);

            if ($response->status() === 200 && ($json['success'] ?? false)) {
                return [
                    'success' => true,
                    'responsecode' => $json['responsecode'] ?? '2001',
                    'requesttransactionid' => $json['requesttransactionid'] ?? $requestTransactionId,
                    'transactionid' => $json['transactionid'] ?? null,
                    'referenceid' => $json['referenceid'] ?? null,
                    'message' => $json['message'] ?? 'Request sent',
                ];
            }

            return [
                'success' => false,
                'responsecode' => $json['responsecode'] ?? '2500',
                'requesttransactionid' => $requestTransactionId,
                'message' => $json['message'] ?? ($json['detail'] ?? 'Service Failure'),
                'http_code' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::channel('payment')->error('IntouchPay RequestPayment Error', [
                'error' => $e->getMessage(),
                'request_transaction_id' => $requestTransactionId,
            ]);

            return [
                'success' => false,
                'responsecode' => '2500',
                'requesttransactionid' => $requestTransactionId,
                'message' => 'Service Failure: '.$e->getMessage(),
            ];
        }
    }

    public function getTransactionStatus(string $requestTransactionId, string $transactionId): array
    {
        $timestamp = $this->timestamp();

        $requestData = [
            'username' => $this->username,
            'timestamp' => $timestamp,
            'password' => $this->generatePassword($timestamp),
            'requesttransactionid' => $requestTransactionId,
            'transactionid' => $transactionId,
            'accountno' => $this->accountNo,
        ];

        try {
            // The status API takes JSON, not form data
            $response = $this->http()->asJson()->post($this->apiUrl.'/gettransactionstatus/', $requestData);

            if ($error = $this->htmlError($response)) {
                Log::channel('payment')->error('IntouchPay Status Check: HTML response', $error + ['request_transaction_id' => $requestTransactionId]);

                return [
                    'success' => false,
                    'requesttransactionid' => $requestTransactionId,
                    'transactionid' => $transactionId,
                    'status' => 'Unknown',
                ] + $error;
            }

            $json = $response->json();

            if (! is_array($json)) {
                Log::channel('payment')->error('IntouchPay Status Check: Invalid JSON response', [
                    'request_transaction_id' => $requestTransactionId,
                    'response_preview' => substr($response->body(), 0, 200),
                ]);

                return [
                    'success' => false,
                    'responsecode' => '5003',
                    'requesttransactionid' => $requestTransactionId,
                    'transactionid' => $transactionId,
                    'status' => 'Unknown',
                    'message' => 'Invalid JSON response from IntouchPay',
                ];
            }

            Log::channel('payment')->info('IntouchPay Status Check: Response received', [
                'request_transaction_id' => $requestTransactionId,
                'response' => $json,
                'http_status' => $response->status(),
            ]);

            return $json;
        } catch (\Throwable $e) {
            Log::channel('payment')->error('IntouchPay Status Check Error', [
                'error' => $e->getMessage(),
                'request_transaction_id' => $requestTransactionId,
            ]);

            return [
                'success' => false,
                'responsecode' => '2500',
                'requesttransactionid' => $requestTransactionId,
                'transactionid' => $transactionId,
                'status' => 'Unknown',
                'message' => 'Service Failure: '.$e->getMessage(),
            ];
        }
    }

    private function timestamp(): string
    {
        return now()->utc()->format('YmdHis');
    }

    /**
     * SHA256(username + accountno + partnerpassword + timestamp)
     */
    private function generatePassword(string $timestamp): string
    {
        return hash('sha256', $this->username.$this->accountNo.$this->partnerPassword.$timestamp);
    }

    private function http(int $timeout = 60)
    {
        $request = Http::timeout($timeout)->acceptJson();

        $caBundle = (string) config('services.intouchpay.ca_bundle');
        if ($caBundle !== '' && is_file($caBundle)) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        return $request;
    }

    /**
     * Detect an HTML page (bot protection / error page) returned instead of JSON.
     */
    private function htmlError(Response $response): ?array
    {
        $body = $response->body();
        $isHtml = stripos($body, '<!DOCTYPE') !== false || stripos($body, '<html') !== false;
        $isBotProtection = stripos($body, 'Bot Verification') !== false || stripos($body, 'robots') !== false;

        if (! $isHtml && ! $isBotProtection) {
            return null;
        }

        return [
            'responsecode' => $isBotProtection ? '5008' : '5005',
            'message' => $isBotProtection
                ? 'IntouchPay bot protection triggered. Ask IntouchPay support to whitelist the server IP address.'
                : 'IntouchPay returned an HTML error page instead of JSON.',
            'response_preview' => substr(strip_tags($body), 0, 200),
        ];
    }
}
