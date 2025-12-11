<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            $group->business_id = current_business_id();
        });
    }

    protected $fillable = [
        'name',
        'description',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_groups');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}