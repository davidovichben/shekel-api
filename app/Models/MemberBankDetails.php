<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberBankDetails extends Model
{
    protected $fillable = [
        'member_id',
        'bank_id',
        'account_number',
        'branch_number',
        'id_number',
        'first_name',
        'last_name',
        'billing_cap',
    ];

    protected function casts(): array
    {
        return [
            'billing_cap' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }
}
