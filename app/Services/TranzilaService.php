<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TranzilaService
{
    protected string $terminal;
    protected string $password;
    protected string $secretKey;
    protected string $publicKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->terminal = config('tranzila.terminal');
        $this->password = config('tranzila.password');
        $this->secretKey = config('tranzila.secret_key');
        $this->publicKey = config('tranzila.public_key');
        $this->apiUrl = config('tranzila.api_url');
    }

    /**
     * Create a token from credit card details using API V2.
     */
    public function createToken(string $cardNumber, string $expDate, string $cvv): array
    {
        $payload = [
            'terminal_name' => $this->terminal,
            'ccno' => $cardNumber,
            'expdate' => $expDate,
            'mycvv' => $cvv,
            'TranzilaTK' => '1',
        ];

        return $this->sendApiV2Request('https://api.tranzila.com/v1/transaction/tokenize', $payload);
    }

    /**
     * Charge using a token.
     */
    public function chargeToken(string $token, float $amount, string $currency = 'ILS'): array
    {
        $payload = [
            'terminal_name' => $this->terminal,
            'TranzilaTK' => $token,
            'sum' => $amount,
            'currency' => $this->getCurrencyCode($currency),
            'tranmode' => 'A',
        ];

        return $this->sendApiV2Request('https://api.tranzila.com/v1/transaction/charge', $payload);
    }

    /**
     * Refund a transaction.
     */
    public function refund(string $index, float $amount): array
    {
        $payload = [
            'terminal_name' => $this->terminal,
            'tranmode' => 'C',
            'index' => $index,
            'sum' => $amount,
        ];

        return $this->sendApiV2Request('https://api.tranzila.com/v1/transaction/refund', $payload);
    }

    /**
     * Generate authentication headers for API V2.
     */
    protected function generateAuthHeaders(): array
    {
        $time = time();
        $nonce = bin2hex(random_bytes(40));
        $accessToken = hash_hmac('sha256', $this->publicKey, $this->secretKey . $time . $nonce);

        return [
            'Content-Type' => 'application/json',
            'X-tranzila-api-app-key' => $this->publicKey,
            'X-tranzila-api-request-time' => $time,
            'X-tranzila-api-nonce' => $nonce,
            'X-tranzila-api-access-token' => $accessToken,
        ];
    }

    /**
     * Send request to Tranzila API V2.
     */
    protected function sendApiV2Request(string $url, array $payload): array
    {
        $headers = $this->generateAuthHeaders();

        $response = Http::withHeaders($headers)->post($url, $payload);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Connection failed',
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        }

        $result = $response->json() ?? [];

        return [
            'success' => isset($result['Response']) && $result['Response'] === '000',
            'response_code' => $result['Response'] ?? null,
            'index' => $result['index'] ?? null,
            'token' => $result['TranzilaTK'] ?? null,
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
}
