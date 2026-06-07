<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'asset_id',
        'scanned_by_user_id',
        'ip_address',
        'user_agent',
        'lat',
        'lng',
        'location_text',
        'trigger',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmergencyProfile::class, 'profile_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(SafeAsset::class, 'asset_id');
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class, 'scan_log_id');
    }
}
