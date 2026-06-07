<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class SetTrialOnRegistered
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        // Only set if empty (don’t override)
        if (!$user->trial_ends_at) {
            $user->forceFill([
                'trial_ends_at' => now()->addDays(15),
            ])->save();
        }
    }
}
