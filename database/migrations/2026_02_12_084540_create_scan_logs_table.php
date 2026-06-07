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
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained('emergency_profiles')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('safe_assets')->nullOnDelete();

            // Who scanned (optional)
            $table->foreignId('scanned_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            // Optional location data (if you capture it)
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('location_text')->nullable();

            $table->enum('trigger', ['qr_scan', 'nfc_tap'])->default('qr_scan');

            $table->timestamps();

            $table->index(['profile_id', 'created_at']);
            $table->index(['asset_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
