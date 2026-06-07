<?php

namespace Database\Factories;

use App\Models\SafeAsset;
use App\Models\EmergencyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SafeAssetFactory extends Factory
{
    protected $model = SafeAsset::class;

    public function definition(): array
    {
        return [
            'profile_id' => EmergencyProfile::factory(),
            'type' => $this->faker->randomElement(['qr_sticker', 'qr_card', 'nfc_card', 'nfc_tag']),
            'public_token' => Str::random(48),
            'nfc_uid' => null,
            'label' => $this->faker->randomElement(['Helmet', 'Wallet', 'Keychain', 'Backpack']),
            'activated_at' => now(),
            'deactivated_at' => null,
        ];
    }

    public function sticker(): self
    {
        return $this->state(fn () => ['type' => 'qr_sticker']);
    }

    public function active(): self
    {
        return $this->state(fn () => ['deactivated_at' => null, 'activated_at' => now()]);
    }

    public function forProfile(\App\Models\EmergencyProfile $profile): self
    {
        return $this->state(fn () => ['profile_id' => $profile->id]);
    }
}
