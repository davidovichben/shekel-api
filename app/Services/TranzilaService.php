<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TranzilaService
{
    protected string $terminal;
    protected string $password;
    protected string $apiUrl;

    public function __construct()
    {
        $this->terminal = config('tranzila.terminal');
        $this->password = config('tranzila.password');
        $this->apiUrl = config('tranzila.api_url');
    }

    /**
     * Charge a credit card.
     */
    public function charge(array $params): array
    {
        $data = array_merge([
            'supplier' => $this->terminal,
            'TranzilaPW' => $this->password,
            'response_return_format' => 'json',
        ], $params);

        return $this->sendRequest($data);
    }

    /**
     * Charge using a token.
     */
    public function chargeToken(string $token, float $amount, string $currency = 'ILS'): array
    {
        return $this->charge([
            'TranzilaTK' => $token,
            'sum' => $amount,
            'currency' => $this->getCurrencyCode($currency),
            'tranmode' => 'A',
        ]);
    }

    /**
     * Create a token from credit card details.
     */
    public function createToken(string $cardNumber, string $expDate, string $cvv): array
    {
        return $this->charge([
            'ccno' => $cardNumber,
            'expdate' => $expDate,
            'mycvv' => $cvv,
            'TranzilaTK' => '1',
        ]);
    }

    /**
     * Refund a transaction.
     */
    public function refund(string $index, float $amount): array
    {
        return $this->charge([
            'tranmode' => 'C',
            'index' => $index,
            'sum' => $amount,
        ]);
    }

    /**
     * Send request to Tranzila API.
     */
    protected function sendRequest(array $data): array
    {
        $response = Http::asForm()->post($this->apiUrl, $data);

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Connection failed',
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
