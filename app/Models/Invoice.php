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
        'member_id',
        'invoice_number',
        'total_amount',
        'tax_amount',
        'subtotal',
        'payment_method',
        'gregorian_date',
        'hebrew_date',
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
            'gregorian_date' => 'date',
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

}
