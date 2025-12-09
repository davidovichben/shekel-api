<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Member extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'mobile',
        'phone',
        'email',
        'gender',
        'address',
        'address_2',
        'city',
        'country',
        'zipcode',
        'gregorian_birth_date',
        'hebrew_birth_date',
        'gregorian_wedding_date',
        'hebrew_wedding_date',
        'gregorian_death_date',
        'hebrew_death_date',
        'contact_person',
        'contact_person_type',
        'tag',
        'title',
        'type',
        'member_number',
        'has_website_account',
        'should_mail',
        'balance',
        'last_message_date',
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
            'gregorian_birth_date' => 'date',
            'gregorian_wedding_date' => 'date',
            'gregorian_death_date' => 'date',
            'has_website_account' => 'boolean',
            'should_mail' => 'boolean',
        ];
    }

    /**
     * Get the member's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Scope a query to only include members of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include members with website accounts.
     */
    public function scopeWithWebsiteAccount($query)
    {
        return $query->where('has_website_account', true);
    }

    /**
     * Scope a query to only include members who should receive mail.
     */
    public function scopeShouldMail($query)
    {
        return $query->where('should_mail', true);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'member_groups');
    }
}
