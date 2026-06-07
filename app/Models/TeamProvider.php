<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamProvider extends Model
{
    protected $fillable = [
        'team_id',
        'type',
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'created_by',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}