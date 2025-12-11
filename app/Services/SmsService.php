<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $recipient, string $msg): bool
    {
        $response = Http::post(config('sms.endpoint'), [
            'user' => config('sms.user'),
            'pass' => config('sms.pass'),
            'key' => config('sms.key'),
            'sender' => config('sms.sender'),
            'recipient' => $recipient,
            'msg' => $msg,
        ]);

        $status = $response->json('status', -1);

        if ($status <= 0) {
            Log::error('SMS sending failed', [
                'recipient' => $recipient,
                'status' => $status,
                'response' => $response->json(),
            ]);
            return false;
        }

        return true;
    }
}
