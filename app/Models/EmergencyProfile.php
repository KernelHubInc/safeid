<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo, HasMany, HasOne
};
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmergencyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',

        'first_name', 'last_name', 'birthdate', 'photo_path',
        'blood_type', 'height_cm', 'weight_kg', 'zip_code',
        'profile_notes',

        // Health info (JSON)
        'allergies',
        'current_medications',
        'medical_conditions',

        // Insurance & physician
        'insurance_provider',
        'insurance_number',
        'primary_physician_name',
        'primary_physician_phone',
        'additional_medical_notes',

        // Address / meta
        'address_line', 'city', 'province', 'country',
        'is_public', 'is_active', 'last_scanned_at',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'last_scanned_at' => 'datetime',

        'allergies' => 'array',
        'current_medications' => 'array',
        'medical_conditions' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $profile) {
            if (empty($profile->uuid)) {
                $profile->uuid = (string) Str::uuid();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class, 'profile_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(SafeAsset::class, 'profile_id');
    }

    public function scanLogs(): HasMany
    {
        return $this->hasMany(ScanLog::class, 'profile_id');
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class, 'profile_id');
    }

    // Phase 2
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'profile_id');
    }

    public function crashIncidents(): HasMany
    {
        return $this->hasMany(CrashIncident::class, 'profile_id');
    }

    // Helpers
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
