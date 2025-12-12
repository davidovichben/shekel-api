<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'features',
        'paid_features',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'features' => 'array',
            'paid_features' => 'array',
        ];
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
