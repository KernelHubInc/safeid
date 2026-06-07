<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberLocation extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'latitude',
        'longitude',
        'address',
        'accuracy',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}