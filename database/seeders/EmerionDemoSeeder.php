<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EmergencyProfile;
use App\Models\EmergencyContact;
use App\Models\SafeAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmerionDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 demo users with profiles
        User::factory()
            ->count(10)
            ->create()
            ->each(function (User $user) {
                // Profile with health info (JSON)
                $profile = EmergencyProfile::factory()->for($user)->create();

                // Give them 1-2 assets (QR sticker + optional card)
                SafeAsset::factory()->sticker()->active()->forProfile($profile)->create([
                    'label' => 'Primary Sticker',
                    'public_token' => Str::random(48),
                ]);

                if (random_int(0, 1) === 1) {
                    SafeAsset::factory()->active()->forProfile($profile)->create([
                        'type' => 'nfc_card',
                        'label' => 'Wallet Card',
                        'public_token' => Str::random(48),
                        'nfc_uid' => strtoupper(Str::random(12)),
                    ]);
                }

                // 0-3 contacts (Connect plan feature; we still seed for demo)
                $count = random_int(0, 3);
                if ($count > 0) {
                    EmergencyContact::factory()
                        ->count($count)
                        ->forProfile($profile)
                        ->create();
                }
            });
    }
}
