<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberBankDetails;
use App\Models\MemberCreditCard;
use App\Models\Receipt;
use App\Services\TranzilaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    /**
     * Create an iframe payment session for charging a member.
     */
    public function memberPayment(Request $request, TranzilaService $tranzila): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'installments' => 'nullable|integer|min:1|max:32',
            'description' => 'nullable|string|max:500',
            'type' => 'nullable|in:general,vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
        ]);

        $member = Member::where('id', $validated['member_id'])
            ->where('business_id', current_business_id())
            ->firstOrFail();

        return $this->createPaymentSession($tranzila, [
            'amount' => $validated['amount'],
            'member_id' => $member->id,
            'installments' => $validated['installments'] ?? 1,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
        ]);
    }

    /**
     * Create an iframe payment session for business payment.
     */
    public function businessPayment(Request $request, TranzilaService $tranzila): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'installments' => 'nullable|integer|min:1|max:32',
            'description' => 'nullable|string|max:500',
            'type' => 'nullable|in:general,vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
        ]);

        return $this->createPaymentSession($tranzila, [
            'amount' => $validated['amount'],
            'member_id' => null,
            'installments' => $validated['installments'] ?? 1,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
        ]);
    }

    /**
     * Create payment session with receipt and iframe URL.
     */
    private function createPaymentSession(TranzilaService $tranzila, array $data): JsonResponse
    {
        $installments = $data['installments'] ?? 1;

        $receipt = $this->createReceipt([
            'member_id' => $data['member_id'],
            'amount' => $data['amount'],
            'installments' => $installments,
            'description' => $data['description'],
            'type' => $data['type'],
            'status' => 'pending',
        ]);

        // Build URLs
        $baseUrl = config('app.tunnel_url') ?: url('/');
        $callbackUrl = rtrim($baseUrl, '/') . '/api/billing/callback?receipt_id=' . $receipt->id;
        $successUrl = rtrim($baseUrl, '/') . '/api/billing/success';
        $failUrl = rtrim($baseUrl, '/') . '/api/billing/fail';

        $result = $tranzila->createIframeSession(
            amount: (float) $data['amount'],
            callbackUrl: $callbackUrl,
            successUrl: $successUrl,
            failUrl: $failUrl,
            installments: $installments
        );

        if (!$result['success']) {
            $receipt->delete();

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to create iframe session',
            ], 400);
        }

        // Store the handshake token to verify in callback
        $receipt->update([
            'external_id' => $result['raw']['token'],
        ]);

        return response()->json([
            'iframe_url' => $result['raw']['iframe_url'],
            'receipt_id' => $receipt->id,
        ]);
    }

    /**
     * Handle Tranzila payment callback.
     */
    public function callback(Request $request, TranzilaService $tranzila): JsonResponse
    {
        Log::info('Tranzila callback', $request->all());

        $response = $request->input('Response');
        $token = $request->input('TranzilaTK');
        $handshakeToken = $request->input('thtk');
        $amount = $request->input('sum');
        $receiptId = $request->input('receipt_id');
        $expMonth = $request->input('expmonth');
        $expYear = $request->input('expyear');
        $cardType = $request->input('cardtype');
        $index = $request->input('index');
        $confirmationCode = $request->input('ConfirmationCode');
        $lastDigits = $token ? substr($token, -4) : '****';

        $success = $response === '000';

        if (!$receiptId) {
            return response()->json(['success' => false, 'error' => 'Missing receipt_id']);
        }

        $receipt = Receipt::find($receiptId);

        if (!$receipt) {
            return response()->json(['success' => false, 'error' => 'Receipt not found']);
        }

        // Verify handshake token, credit card token, and amount
        $handshakeMatches = $handshakeToken && $handshakeToken === $receipt->external_id;
        $amountMatches = $amount && (float) $amount === (float) $receipt->total;
        $tokenValid = !empty($token);

        if (!$success || !$tokenValid || !$amountMatches || !$handshakeMatches) {
            $reasons = [];
            if (!$success) {
                $reasons[] = "Payment declined (Response: {$response})";
            }
            if (!$tokenValid) {
                $reasons[] = 'No token received';
            }
            if (!$handshakeMatches) {
                $reasons[] = 'Handshake token mismatch';
            }
            if (!$amountMatches) {
                $reasons[] = "Amount mismatch (expected: {$receipt->total}, received: {$amount})";
            }

            Log::warning('Tranzila callback validation failed', [
                'receipt_id' => $receiptId,
                'success' => $success,
                'token_valid' => $tokenValid,
                'handshake_matches' => $handshakeMatches,
                'amount_matches' => $amountMatches,
                'expected_amount' => $receipt->total,
                'received_amount' => $amount,
            ]);

            $receipt->update([
                'status' => 'failed',
                'failure_reason' => implode('; ', $reasons),
            ]);

            return response()->json(['success' => false]);
        }

        // Save credit card if member exists
        $creditCardId = null;
        if ($receipt->member_id) {
            $member = $receipt->member;

            $creditCard = MemberCreditCard::where('member_id', $member->id)
                ->where('token', $token)
                ->first();

            if (!$creditCard) {
                $isFirstCard = MemberCreditCard::where('member_id', $member->id)->count() === 0;

                $creditCard = MemberCreditCard::create([
                    'member_id' => $member->id,
                    'token' => $token,
                    'last_digits' => $lastDigits,
                    'company' => $tranzila->mapCardType($cardType),
                    'expiration' => $expMonth . '/' . $expYear,
                    'full_name' => $member->first_name . ' ' . $member->last_name,
                    'is_default' => $isFirstCard,
                ]);
            }

            $creditCardId = $creditCard->id;
        }

        // Update receipt
        $receipt->update([
            'credit_card_id' => $creditCardId,
            'external_id' => $index ?? $confirmationCode,
            'status' => 'paid',
            'date' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Charge an existing credit card token.
     */
    public function charge(Request $request, TranzilaService $tranzila): JsonResponse
    {
        $validated = $request->validate([
            'credit_card_id' => 'required|exists:member_credit_cards,id',
            'amount' => 'required|numeric|min:0.01',
            'installments' => 'nullable|integer|min:1|max:32',
            'description' => 'nullable|string|max:500',
            'type' => 'nullable|in:general,vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
        ]);

        $creditCard = MemberCreditCard::findOrFail($validated['credit_card_id']);
        $installments = $validated['installments'] ?? 1;

        // Convert expiration from MM/YY to MMYY format
        $expdate = str_replace('/', '', $creditCard->expiration);

        // Charge the token via Tranzila
        $result = $tranzila->chargeToken($creditCard->token, (float) $validated['amount'], $expdate, $installments);

        if (!$result['success']) {
            // Create failed receipt
            $this->createReceipt([
                'member_id' => $creditCard->member_id,
                'credit_card_id' => $creditCard->id,
                'amount' => $validated['amount'],
                'installments' => $installments,
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'] ?? null,
                'status' => 'failed',
                'failure_reason' => "Payment declined (Response: {$result['response_code']})",
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment failed',
                'response_code' => $result['response_code'] ?? null,
            ], 400);
        }

        // Create paid receipt
        $receipt = $this->createReceipt([
            'member_id' => $creditCard->member_id,
            'credit_card_id' => $creditCard->id,
            'external_id' => $result['index'] ?? $result['confirmation_code'],
            'amount' => $validated['amount'],
            'installments' => $installments,
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => 'paid',
            'date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'receipt' => [
                'id' => $receipt->id,
                'number' => $receipt->number,
                'total' => number_format($receipt->total, 2, '.', ''),
                'status' => $receipt->status,
            ],
            'transaction' => [
                'confirmation_code' => $result['confirmation_code'] ?? null,
                'index' => $result['index'] ?? null,
            ],
        ]);
    }

    /**
     * Charge via MASAV (direct debit from bank account).
     */
    public function masav(Request $request, TranzilaService $tranzila): JsonResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'type' => 'nullable|in:general,vows,community_donations,external_donations,ascensions,online_donations,membership_fees,other',
        ]);

        $member = Member::where('id', $validated['member_id'])
            ->where('business_id', current_business_id())
            ->firstOrFail();

        $bankDetails = MemberBankDetails::where('member_id', $member->id)
            ->with('bank')
            ->first();

        if (!$bankDetails) {
            return response()->json([
                'success' => false,
                'error' => 'Member has no bank details',
            ], 400);
        }

        // Charge via MASAV
        $result = $tranzila->chargeMasav([
            'bank_code' => $bankDetails->bank->code,
            'branch_number' => $bankDetails->branch_number,
            'account_number' => $bankDetails->account_number,
            'id_number' => $bankDetails->id_number,
            'first_name' => $bankDetails->first_name,
            'last_name' => $bankDetails->last_name,
        ], (float) $validated['amount']);

        if (!$result['success']) {
            // Create failed receipt
            $this->createReceipt([
                'member_id' => $member->id,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'] ?? null,
                'status' => 'failed',
                'failure_reason' => "MASAV declined (Response: {$result['response_code']})",
            ]);

            return response()->json([
                'success' => false,
                'error' => 'MASAV payment failed',
                'response_code' => $result['response_code'] ?? null,
            ], 400);
        }

        // Create paid receipt
        $receipt = $this->createReceipt([
            'member_id' => $member->id,
            'external_id' => $result['index'] ?? $result['confirmation_code'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => 'paid',
            'date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'receipt' => [
                'id' => $receipt->id,
                'number' => $receipt->number,
                'total' => number_format($receipt->total, 2, '.', ''),
                'status' => $receipt->status,
            ],
            'transaction' => [
                'confirmation_code' => $result['confirmation_code'] ?? null,
                'index' => $result['index'] ?? null,
            ],
        ]);
    }

    /**
     * Create a receipt with auto-generated number.
     */
    private function createReceipt(array $data): Receipt
    {
        $businessId = current_business_id();

        // Generate receipt number starting from 1000
        $lastReceipt = Receipt::where('business_id', $businessId)
            ->whereNotNull('number')
            ->where('number', 'regexp', '^[0-9]+$')
            ->orderByRaw('CAST(number AS UNSIGNED) DESC')
            ->first();

        $nextNumber = $lastReceipt ? (int) $lastReceipt->number + 1 : 1000;

        return Receipt::create([
            'business_id' => $businessId,
            'member_id' => $data['member_id'] ?? null,
            'credit_card_id' => $data['credit_card_id'] ?? null,
            'external_id' => $data['external_id'] ?? null,
            'number' => (string) $nextNumber,
            'total' => $data['amount'],
            'installments' => $data['installments'] ?? 1,
            'status' => $data['status'] ?? 'pending',
            'failure_reason' => $data['failure_reason'] ?? null,
            'payment_method' => 'credit_card',
            'date' => $data['date'] ?? null,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'general',
        ]);
    }

    /**
     * Success page for iframe redirect.
     */
    public function success(): \Illuminate\Http\Response
    {
        return response($this->buildResultPage(true), 200)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Fail page for iframe redirect.
     */
    public function fail(): \Illuminate\Http\Response
    {
        return response($this->buildResultPage(false), 200)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Build HTML result page with postMessage script.
     */
    private function buildResultPage(bool $success): string
    {
        $type = $success ? 'paymentSuccess' : 'paymentFailed';
        $status = $success ? 'ok' : 'error';
        $bgColor = $success ? '#f0fdf4' : '#fef2f2';
        $iconColor = $success ? '#22c55e' : '#ef4444';
        $titleColor = $success ? '#166534' : '#991b1b';
        $icon = $success ? '✓' : '✗';
        $titleHe = $success ? 'התשלום בוצע בהצלחה' : 'התשלום נכשל';
        $titleEn = $success ? 'Payment completed successfully' : 'Payment failed';

        return "<!DOCTYPE html>
<html dir=\"rtl\" lang=\"he\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{$titleEn}</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background-color: {$bgColor}; }
        .container { text-align: center; padding: 40px; }
        .icon { font-size: 64px; color: {$iconColor}; margin-bottom: 20px; }
        h1 { color: {$titleColor}; margin-bottom: 10px; }
        p { color: #4b5563; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"icon\">{$icon}</div>
        <h1>{$titleHe}</h1>
        <p>{$titleEn}</p>
    </div>
    <script>
        window.parent.postMessage({
            tranzilaEvent: { type: '{$type}', data: { status: '{$status}' } }
        }, '*');
    </script>
</body>
</html>";
    }
}
