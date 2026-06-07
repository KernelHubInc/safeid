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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained('emergency_profiles')->cascadeOnDelete();

            $table->string('device_uuid', 36)->unique();
            $table->string('name')->nullable(); // "Helmet Sensor", "Motorcycle Sensor"
            $table->enum('type', ['phone', 'helmet', 'motorcycle', 'car'])->default('phone');

            $table->string('api_key', 64)->unique(); // device -> backend auth
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
