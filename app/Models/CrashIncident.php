<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrashIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'device_id',
        'status',
        'peak_g',
        'peak_rotation',
        'speed_kmh',
        'lat',
        'lng',
        'location_text',
        'detected_at',
        'confirmed_at',
        'cancelled_at',
        'notified_at',
        'raw_payload',
    ];

    protected $casts = [
        'peak_g' => 'decimal:2',
        'peak_rotation' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'detected_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmergencyProfile::class, 'profile_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
