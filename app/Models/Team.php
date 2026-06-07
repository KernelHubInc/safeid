<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Team extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function linkedMembers(): HasMany
    {
        return $this->hasMany(User::class, 'team_id')
            ->where('id', '!=', $this->owner_user_id);
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function linkedMembersCount(): int
    {
        return $this->linkedMembers()->count();
    }

    public function allowedSeats(): ?int
    {
        if ($this->plan_type === 'enterprise' && is_null($this->max_seats)) {
            return null; // unlimited cap, but still controlled by purchased slots if you want
        }

        return $this->included_seats + $this->extra_seats;
    }

    public function availableSeats(): ?int
    {
        $allowed = $this->allowedSeats();

        if (is_null($allowed)) {
            return null;
        }

        return max(0, $allowed - $this->linkedMembersCount());
    }

    public function canAddMoreMembers(): bool
    {
        $available = $this->availableSeats();

        if (is_null($available)) {
            return true;
        }

        return $available > 0;
    }
}