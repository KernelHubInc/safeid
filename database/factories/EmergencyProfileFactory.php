<?php

namespace Database\Factories;

use App\Models\EmergencyProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmergencyProfileFactory extends Factory
{
    protected $model = EmergencyProfile::class;

    public function definition(): array
    {
        $faker = $this->faker;

        $allergiesPool = ['Peanuts', 'Penicillin', 'Latex', 'Seafood', 'Dust', 'Pollen'];
        $conditionsPool = ['Hypertension', 'Asthma', 'Diabetes', 'Heart Condition', 'Epilepsy'];
        $medsPool = [
            ['name' => 'Aspirin', 'dosage' => '100mg', 'frequency' => 'Daily'],
            ['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => '2x Daily'],
            ['name' => 'Salbutamol', 'dosage' => '2 puffs', 'frequency' => 'As needed'],
        ];

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),

            'first_name' => $faker->firstName(),
            'last_name' => $faker->lastName(),
            'birthdate' => $faker->dateTimeBetween('-60 years', '-10 years')->format('Y-m-d'),
            'photo_path' => null,

            'blood_type' => $faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),

            // JSON fields for your UI
            'allergies' => $faker->randomElements($allergiesPool, $faker->numberBetween(0, 2)),
            'current_medications' => $faker->randomElements($medsPool, $faker->numberBetween(0, 2)),
            'medical_conditions' => $faker->randomElements($conditionsPool, $faker->numberBetween(0, 2)),

            'insurance_provider' => $faker->boolean(60) ? $faker->randomElement(['PhilHealth', 'Maxicare', 'Intellicare']) : null,
            'insurance_number' => $faker->boolean(60) ? strtoupper($faker->bothify('??########')) : null,

            'primary_physician_name' => $faker->boolean(50) ? 'Dr. ' . $faker->name() : null,
            'primary_physician_phone' => $faker->boolean(50) ? $faker->phoneNumber() : null,

            'additional_medical_notes' => $faker->boolean(50)
                ? 'History of minor heart condition. Regular checkups required.'
                : null,

            'address_line' => $faker->streetAddress(),
            'city' => $faker->city(),
            'province' => $faker->state(),
            'country' => 'PH',

            'is_public' => true,
            'is_active' => true,
            'last_scanned_at' => null,
        ];
    }
}
