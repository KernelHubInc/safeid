<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emergency_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();               // used for public scan url
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Basic identity
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('photo_path')->nullable();

            // Medical info
            $table->string('blood_type', 10)->nullable();
            $table->string('height_cm', 10)->nullable();
            $table->string('weight_kg', 10)->nullable();
            $table->string('zip_code', 10)->nullable();            
            $table->json('allergies')->nullable();
            $table->json('medical_conditions')->nullable();
            $table->json('current_medications')->nullable();
            $table->text('additional_medical_notes')->nullable();
            $table->text('profile_notes')->nullable();

            // Insurance & doctor
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_number')->nullable();

            $table->string('primary_physician_name')->nullable();
            $table->string('primary_physician_phone', 32)->nullable();

            // Address / info
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->default('PH');

            // Privacy / status
            $table->boolean('is_public')->default(true); // show scan page
            $table->boolean('is_active')->default(true); // disabled if subscription inactive
            $table->timestamp('last_scanned_at')->nullable();
            

            $table->timestamps();

            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_profiles');
    }
};
