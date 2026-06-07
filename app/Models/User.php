<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Wave\Traits\HasProfileKeyValues;
use Wave\User as WaveUser;
use Illuminate\Support\Carbon;

class User extends WaveUser
{
    use HasFactory, HasProfileKeyValues, Notifiable, SoftDeletes;

    public $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'avatar',
        'password',
        'role_id',
        'verification_code',
        'verified',
        'trial_ends_at',
        'subscription_owner_id',
        'subscription_expires_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_preferences' => 'array',
            'social_links' => 'array',
            'privacy_settings' => 'array',
            'deletion_scheduled_at' => 'datetime',
            'trial_ends_at' => 'datetime'
        ];
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Listen for the creating event of the model
        static::creating(function ($user) {
            // Check if the username attribute is empty
            if (empty($user->username)) {
                // Use the name to generate a slugified username
                $username = Str::slug($user->name, '');
                $i = 1;
                while (self::where('username', $username)->exists()) {
                    $username = Str::slug($user->name, '').$i;
                    $i++;
                }
                $user->username = $username;
            }

            static::creating(function ($user) {
                // Wave/Cashier uses trial_ends_at
                if (empty($user->trial_ends_at)) {
                    $user->trial_ends_at = Carbon::now()->addDays(14);
                }
            });
        });

        // Listen for the created event of the model
        static::created(function ($user) {
            // Remove all roles
            $user->syncRoles([]);

            // Assign the default role if it exists
            $defaultRole = config('wave.default_user_role', 'registered');
            if (\Spatie\Permission\Models\Role::where('name', $defaultRole)->where('guard_name', 'web')->exists()) {
                $user->assignRole($defaultRole);
            }
        });
    }

    public function emergencyProfile()
    {
        return $this->hasOne(\App\Models\EmergencyProfile::class);
    }

    public function emerionTrialActive(): bool
    {
        // Cashier-style
        if (method_exists($this, 'onTrial')) {
            return $this->onTrial();
        }

        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function emerionTrialExpired(): bool
    {
        if (!$this->trial_ends_at) {
            return true;
        }
        
        return $this->trial_ends_at && now()->gte($this->trial_ends_at);
    }

    public function emerionHasActiveSubscription(): bool
    {
        // Cashier-style: subscribed()
        if (method_exists($this, 'subscriber')) {
            return $this->subscriber();
        }

        // If Wave uses a different method, fallback safely
        return false;
    }

    public function emerionAccessLocked(): bool
    {
        // Lock only when trial is expired AND no active subscription
        return $this->emerionTrialExpired() && !$this->emerionHasActiveSubscription();
    }

    public function emerionTrialDaysLeft(): int
    {
        if (!$this->trial_ends_at) return 0;
        if ($this->emerionTrialExpired()) return 0;

        // “days left” as a friendly number
        return max(0, now()->startOfDay()->diffInDays($this->trial_ends_at->startOfDay()));
    }

    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    public function ownedTeam()
    {
        return $this->hasOne(\App\Models\Team::class, 'owner_user_id');
    }

    // public function isPremium(): bool
    // {
    //     return $this->hasRole('premium');
    // }

    public function isTeamCaptain(): bool
    {
        return $this->ownedTeam()->exists();
    }

    public function hasTeamAccess(): bool
    {
        return $this->hasRole(['premium', 'enterprise']);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'subscription_owner_id');
    }

    public function members()
    {
        return $this->hasMany(User::class, 'subscription_owner_id');
    }

    public function getSubscriptionOwner()
    {
        return $this->subscription_owner_id 
            ? $this->owner 
            : $this;
    }

    public function isPremium()
    {
        $owner = $this->getSubscriptionOwner();

        return $owner->subscription_expires_at 
            && $owner->subscription_expires_at > now() && $owner->hasRole('premium');
    }
}
