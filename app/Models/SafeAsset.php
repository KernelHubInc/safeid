<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SafeAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'type',
        'public_token',
        'nfc_uid',
        'label',
        'is_primary',
        'qr_path',
        'activated_at',
        'deactivated_at',
        'kit_plan',
        'batch_id',
        'claim_code',
        'status',
        'sold_at',
        'registered_at',
        'owner_user_id',
        'team_id',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'sold_at' => 'datetime',
        'is_primary' => 'boolean',
        'activated_at' => 'datetime',
        'registered_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $asset) {
            if (empty($asset->public_token)) {
                // URL-safe token
                $asset->public_token = Str::random(48);
            }
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmergencyProfile::class, 'profile_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(ScanLog::class, 'asset_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deactivated_at');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
