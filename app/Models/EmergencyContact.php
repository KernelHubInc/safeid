<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmergencyContact extends Model
{
    use HasFactory;
    protected $fillable = [
        'profile_id',
        'linked_user_id',
        'name',
        'relationship',
        'phone',
        'email',
        'notify_on_scan',
        'notify_on_manual_alert',
        'notify_on_crash',
        'priority',
    ];

    protected $casts = [
        'notify_on_scan' => 'boolean',
        'notify_on_manual_alert' => 'boolean',
        'notify_on_crash' => 'boolean',
        'priority' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmergencyProfile::class, 'profile_id');
    }

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }
}
