<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamRegistrationService
{
    public function activateTeam(User $user, $plan, $createTeam = false)
    {
        return DB::transaction(function () use ($user, $plan, $createTeam) {
            // OPTION A: if using users.role
            // $user->role = 'premium';
            // $user->save();

            // OPTION B: if using spatie permission
            if ($plan) {
                if ($plan === 'premium') {
                    $user->assignRole('premium');
                } elseif ($plan === 'enterprise') {
                    $user->assignRole('enterprise');
                }
            }

            $team = $user->ownedTeam;

            if ($createTeam) {                    
                if (!$team) {
                    $teamName = $user->name . "'s Team";

                    $team = Team::create([
                        'name' => $teamName,
                        'slug' => Team::generateUniqueSlug($teamName),
                        'owner_user_id' => $user->id,
                    ]);
                }

                if ($user->team_id !== $team->id) {
                    $user->team_id = $team->id;
                    $user->save();
                }
            }

            return $team;
        });
    }

    public function validateMemberCanBeLinked(User $owner, Team $team, User $member): void
    {
        $linkedCount = User::query()
            ->where('team_id', $team->id)
            ->where('id', '!=', $team->owner_user_id)
            ->count();

        if ($linkedCount >= 3) {
            throw ValidationException::withMessages([
                'email' => 'Premium plan allows only up to 3 linked accounts.',
            ]);
        }

        if ($member->id === $owner->id) {
            throw ValidationException::withMessages([
                'email' => 'You cannot link your own account.',
            ]);
        }

        if ($member->role !== 'premium') {
            throw ValidationException::withMessages([
                'email' => 'Only premium accounts can be linked.',
            ]);
        }

        if (! is_null($member->team_id) && $member->team_id !== $team->id) {
            throw ValidationException::withMessages([
                'email' => 'This account is already linked to another team.',
            ]);
        }

        $alreadyLinked = User::query()
            ->where('team_id', $team->id)
            ->where('id', $member->id)
            ->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                'email' => 'This account is already linked to your team.',
            ]);
        }
    }
}
