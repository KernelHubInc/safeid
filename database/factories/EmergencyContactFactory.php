<?php

namespace Database\Factories;

use App\Models\EmergencyContact;
use App\Models\EmergencyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmergencyContactFactory extends Factory
{
    protected $model = EmergencyContact::class;

    public function definition(): array
    {
        $faker = $this->faker;

        return [
            'profile_id' => EmergencyProfile::factory(),
            'linked_user_id' => null,

            'name' => $faker->name(),
            'relationship' => $faker->randomElement(['Mother', 'Father', 'Sibling', 'Partner', 'Friend']),
            'phone' => $faker->phoneNumber(),
            'email' => $faker->safeEmail(),

            'notify_on_scan' => true,
            'notify_on_manual_alert' => true,
            'notify_on_crash' => true,
            'priority' => $faker->numberBetween(1, 3),
        ];
    }

    public function forProfile(\App\Models\EmergencyProfile $profile): self
    {
        return $this->state(fn () => ['profile_id' => $profile->id]);
    }

}
