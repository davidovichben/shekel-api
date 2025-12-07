<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_number',
        'member_id',
        'total_amount',
        'tax_amount',
        'subtotal',
        'status',
        'payment_method',
        'invoice_date',
        'due_date',
        'paid_date',
        'notes',
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
            'total_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'invoice_date' => 'datetime',
            'due_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    /**
     * Get the member that owns the invoice.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Scope a query to only include invoices with a given status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'overdue']);
    }

    /**
     * Scope a query to only include overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }
}
