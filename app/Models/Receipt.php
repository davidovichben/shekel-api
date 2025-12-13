<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Receipt $receipt) {
            $receipt->business_id = current_business_id();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'member_id',
        'credit_card_id',
        'external_id',
        'number',
        'total',
        'installments',
        'status',
        'failure_reason',
        'payment_method',
        'date',
        'description',
        'type',
        'pdf_file',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'date' => 'datetime',
        ];
    }

    /**
     * Get the member that owns the receipt.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user that owns the receipt (old schema - for backward compatibility).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the credit card used for the receipt.
     */
    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(MemberCreditCard::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
