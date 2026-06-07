<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use HasFactory;
    protected $fillable = [
        'profile_id',
        'device_uuid',
        'name',
        'type',
        'api_key',
        'paired_at',
        'last_seen_at',
        'is_active',
    ];

    protected $casts = [
        'paired_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $device) {
            if (empty($device->device_uuid)) {
                $device->device_uuid = (string) Str::uuid();
            }
            if (empty($device->api_key)) {
                $device->api_key = Str::random(64);
            }
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmergencyProfile::class, 'profile_id');
    }

    public function crashIncidents(): HasMany
    {
        return $this->hasMany(CrashIncident::class, 'device_id');
    }
}
