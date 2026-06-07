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
        Schema::create('crash_incidents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained('emergency_profiles')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();

            $table->enum('status', ['detected', 'confirmed', 'cancelled', 'notified'])->default('detected');

            // sensor readings snapshot
            $table->decimal('peak_g', 6, 2)->nullable();
            $table->decimal('peak_rotation', 8, 2)->nullable();
            $table->integer('speed_kmh')->nullable();

            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('location_text')->nullable();

            $table->timestamp('detected_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('notified_at')->nullable();

            $table->text('raw_payload')->nullable(); // store JSON string if needed

            $table->timestamps();

            $table->index(['profile_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crash_incidents');
    }
};
