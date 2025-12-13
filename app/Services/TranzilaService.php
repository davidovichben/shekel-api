<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TranzilaService
{
    protected string $terminal;
    protected string $password;

    public function __construct()
    {
        $this->terminal = config('tranzila.terminal');
        $this->password = config('tranzila.password');
    }

    /**
     * Charge using a stored token.
     *
     * @param string $expdate Expiration date in MMYY format (e.g., "1234" for 12/34)
     * @param int $installments Number of installments (1-32)
     */
    public function chargeToken(string $token, float $amount, string $expdate, int $installments = 1, string $currency = 'ILS'): array
    {
        $params = [
            'supplier' => $this->terminal,
            'TranzilaPW' => $this->password,
            'TranzilaTK' => $token,
            'expdate' => $expdate,
            'sum' => $amount,
            'currency' => $this->getCurrencyCode($currency),
            'tranmode' => 'A', // Regular transaction
            'cred_type' => $installments > 1 ? 8 : 1, // 8 = installments, 1 = regular
            'fpay' => $installments > 1 ? $installments : null,
        ];

        // Remove null values
        $params = array_filter($params, fn($v) => $v !== null);

        $url = 'https://secure5.tranzila.com/cgi-bin/tranzila71u.cgi';

        $response = Http::asForm()->post($url, $params);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Connection failed',
                'status' => $response->status(),
            ];
        }

        // Parse response - returns key=value pairs
        parse_str($response->body(), $result);

        $responseCode = $result['Response'] ?? null;

        return [
            'success' => $responseCode === '000',
            'response_code' => $responseCode,
            'index' => $result['index'] ?? null,
            'confirmation_code' => $result['ConfirmationCode'] ?? null,
            'raw' => $result,
        ];
    }

    /**
     * Refund a transaction.
     */
    public function refund(string $index, float $amount): array
    {
        $params = [
            'supplier' => $this->terminal,
            'TranzilaPW' => $this->password,
            'tranmode' => 'C', // Credit/Refund
            'index' => $index,
            'sum' => $amount,
        ];

        $url = 'https://secure5.tranzila.com/cgi-bin/tranzila71u.cgi';

        $response = Http::asForm()->post($url, $params);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Connection failed',
                'status' => $response->status(),
            ];
        }

        parse_str($response->body(), $result);

        $responseCode = $result['Response'] ?? null;

        return [
            'success' => $responseCode === '000',
            'response_code' => $responseCode,
            'index' => $result['index'] ?? null,
            'confirmation_code' => $result['ConfirmationCode'] ?? null,
            'raw' => $result,
        ];
    }

    /**
     * Charge via MASAV (direct debit from bank account).
     */
    public function chargeMasav(array $bankDetails, float $amount, string $currency = 'ILS'): array
    {
        $params = [
            'supplier' => $this->terminal,
            'TranzilaPW' => $this->password,
            'sum' => $amount,
            'currency' => $this->getCurrencyCode($currency),
            'tranmode' => 'D', // Direct debit (MASAV)
            'bank_code' => $bankDetails['bank_code'],
            'branch_num' => $bankDetails['branch_number'],
            'account_num' => $bankDetails['account_number'],
            'id_number' => $bankDetails['id_number'],
            'first_name' => $bankDetails['first_name'],
            'last_name' => $bankDetails['last_name'],
        ];

        $url = 'https://secure5.tranzila.com/cgi-bin/tranzila71u.cgi';

        $response = Http::asForm()->post($url, $params);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Connection failed',
                'status' => $response->status(),
            ];
        }

        parse_str($response->body(), $result);

        $responseCode = $result['Response'] ?? null;

        return [
            'success' => $responseCode === '000' || $responseCode === '700',
            'response_code' => $responseCode,
            'index' => $result['index'] ?? null,
            'confirmation_code' => $result['ConfirmationCode'] ?? null,
            'raw' => $result,
        ];
    }

    /**
     * Get currency code for Tranzila.
     */
    protected function getCurrencyCode(string $currency): int
    {
        return match (strtoupper($currency)) {
            'ILS' => 1,
            'USD' => 2,
            'EUR' => 3,
            'GBP' => 4,
            default => 1,
        };
    }

    /**
     * Map Tranzila card type code to company name.
     */
    public function mapCardType(?string $cardType): string
    {
        return match ($cardType) {
            '1' => 'visa',
            '2' => 'mastercard',
            '3' => 'diners',
            '4' => 'amex',
            '5' => 'jcb',
            '6' => 'discover',
            default => 'unknown',
        };
    }

    /**
     * Create an iframe payment session via handshake.
     *
     * @param int $installments Number of installments (1-32)
     */
    public function createIframeSession(float $amount, string $callbackUrl, ?string $successUrl = null, ?string $failUrl = null, int $installments = 1): array
    {
        // Step 1: Get handshake token
        $handshakeParams = [
            'supplier' => $this->terminal,
            'TranzilaPW' => $this->password,
            'sum' => $amount,
            'op' => 1,
        ];

        $handshakeUrl = 'https://secure5.tranzila.com/cgi-bin/tranzila71dt.cgi?' . http_build_query($handshakeParams);

        $response = Http::get($handshakeUrl);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Handshake failed',
                'status' => $response->status(),
            ];
        }

        // Parse response - it returns key=value pairs
        $body = $response->body();
        parse_str($body, $result);

        if (empty($result['thtk'])) {
            return [
                'success' => false,
                'error' => 'No handshake token received',
                'raw' => $result,
            ];
        }

        $token = $result['thtk'];

        // Step 2: Build iframe URL
        $iframeParams = [
            'sum' => $amount,
            'currency' => 1, // ILS
            'supplier' => $this->terminal,
            'thtk' => $token,
            'tranmode' => 'A', // Regular transaction
            'cred_type' => $installments > 1 ? 8 : 1, // 8 = installments, 1 = regular
            'TranzilaTK' => 1, // Request token creation for future charges
            'lang' => 'il',
            'maxpay' => $installments,
            'nologo' => 1,
            'trButtonColor' => '3b82f6',
            'notify_url_address' => $callbackUrl,
        ];

        if ($installments > 1) {
            $iframeParams['fpay'] = $installments;
        }

        if ($successUrl) {
            $iframeParams['success_url_address'] = $successUrl;
        }

        if ($failUrl) {
            $iframeParams['fail_url_address'] = $failUrl;
        }

        $iframeUrl = 'https://direct.tranzila.com/' . $this->terminal . '/iframenew.php?' . http_build_query($iframeParams);

        return [
            'success' => true,
            'raw' => [
                'iframe_url' => $iframeUrl,
                'token' => $token,
                'handshake_response' => $result,
            ],
        ];
    }
}
