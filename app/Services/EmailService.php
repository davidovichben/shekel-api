<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send an email.
     *
     * @return bool
     */
    public function send(string $recipient, string $subject, string $body): bool
    {
        try {
            Mail::raw($body, function ($message) use ($recipient, $subject) {
                $message->to($recipient)
                    ->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'recipient' => $recipient,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send an HTML email.
     *
     * @return bool
     */
    public function sendHtml(string $recipient, string $subject, string $htmlBody): bool
    {
        try {
            Mail::html($htmlBody, function ($message) use ($recipient, $subject) {
                $message->to($recipient)
                    ->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'recipient' => $recipient,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
