<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberBillingSettings extends Model
{
    protected $fillable = [
        'member_id',
        'should_bill',
        'billing_date',
        'billing_type',
        'selected_credit_card',
    ];

    protected function casts(): array
    {
        return [
            'should_bill' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(MemberCreditCard::class, 'selected_credit_card');
    }
}
