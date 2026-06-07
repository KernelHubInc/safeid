<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AlertLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'contact_id',
        'scan_log_id',
        'event',
        'channel',
        'to',
        'subject',
        'message',
        'status',
        'provider',
        'provider_message_id',
        'error',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(EmergencyProfile::class, 'profile_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(EmergencyContact::class, 'contact_id');
    }

    public function scanLog(): BelongsTo
    {
        return $this->belongsTo(ScanLog::class, 'scan_log_id');
    }
}
