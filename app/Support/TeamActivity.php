<?php

namespace App\Support;

use App\Models\EmerionActivityLog;
use App\Models\Team;
use App\Models\User;

class TeamActivity
{
    public static function log(
        Team $team,
        string $type,
        string $title,
        ?string $description = null,
        ?User $user = null,
        ?array $metadata = null
    ): void {
        EmerionActivityLog::create([
            'team_id' => $team->id,
            'user_id' => $user?->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}