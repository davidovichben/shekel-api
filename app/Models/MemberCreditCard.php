<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberCreditCard extends Model
{
    protected $fillable = [
        'member_id',
        'last_digits',
        'company',
        'expiration',
        'first_name',
        'last_name',
        'is_default'
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
