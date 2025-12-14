<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Member;

class NotificationService
{
    public function __construct(
        protected SmsService $sms,
        protected EmailService $email
    ) {}

    /**
     * Send notification to a member.
     *
     * @return array{success: bool, error?: string}
     */
    public function sendToMember(Member $member, string $message): array
    {
        if (empty($member->mobile)) {
            return ['success' => false, 'error' => 'Member has no mobile number'];
        }

        $message = $this->replacePlaceholders($message, $member);

        $success = $this->sms->send($member->mobile, $message);

        if (!$success) {
            return ['success' => false, 'error' => 'Failed to send SMS'];
        }

        return ['success' => true];
    }

    /**
     * Send email notification to a member.
     *
     * @return array{success: bool, error?: string}
     */
    public function emailToMember(Member $member, string $subject, string $message): array
    {
        if (empty($member->email)) {
            return ['success' => false, 'error' => 'Member has no email address'];
        }

        $message = $this->replacePlaceholders($message, $member);

        $success = $this->email->send($member->email, $subject, $message);

        if (!$success) {
            return ['success' => false, 'error' => 'Failed to send email'];
        }

        return ['success' => true];
    }

    /**
     * Replace placeholders in message with member-specific data.
     */
    public function replacePlaceholders(string $message, Member $member): string
    {
        // Replace [שם החוב] with member's total pending debts sum
        if (str_contains($message, '[שם החוב]')) {
            $totalDebts = Debt::where('member_id', $member->id)
                ->where('status', 'pending')
                ->sum('amount');
            $message = str_replace('[שם החוב]', number_format($totalDebts, 2), $message);
        }

        return $message;
    }
}
