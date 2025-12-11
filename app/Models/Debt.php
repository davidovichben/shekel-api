<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Debt $debt) {
            $debt->business_id = current_business_id();
        });
    }

    protected $fillable = [
        'member_id',
        'type',
        'amount',
        'description',
        'due_date',
        'status',
        'last_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
