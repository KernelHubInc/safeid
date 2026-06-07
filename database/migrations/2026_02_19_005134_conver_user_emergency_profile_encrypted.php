<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\EmergencyProfile;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        EmergencyProfile::chunkById(200, function ($profiles) {
            foreach ($profiles as $p) {
                // Grab current raw values without triggering decryption
                $rawMedical = $p->getRawOriginal('medical_conditions');
                $rawAllergies = $p->getRawOriginal('allergies');
                $rawContacts = $p->getRawOriginal('current_medications');

                // If they already look encrypted, skip (simple heuristic)
                $alreadyEncrypted = fn($v) => is_string($v) && str_starts_with($v, '{') && str_contains($v, '"iv"');

                // Re-assign using decoded plaintext (so the cast will encrypt on save)
                if ($rawMedical && !$alreadyEncrypted($rawMedical)) {
                    $p->medical_conditions = json_decode($rawMedical, true) ?? $rawMedical;
                }
                if ($rawAllergies && !$alreadyEncrypted($rawAllergies)) {
                    $p->allergies = json_decode($rawAllergies, true) ?? $rawAllergies;
                }
                if ($rawContacts && !$alreadyEncrypted($rawContacts)) {
                    $p->current_medications = json_decode($rawContacts, true) ?? $rawContacts;
                }

                $p->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
