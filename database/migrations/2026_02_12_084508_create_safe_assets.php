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
        Schema::create('safe_assets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained('emergency_profiles')->cascadeOnDelete();

            // sticker/card/nfc_tag
            $table->enum('type', ['qr_sticker', 'qr_card', 'nfc_card', 'nfc_tag'])->default('qr_sticker');

            // Each asset has its own public token (for QR URL)
            $table->string('public_token', 64)->unique();

            // NFC UID / serial (optional)
            $table->string('nfc_uid')->nullable()->unique();

            // print/batch fulfillment (optional)
            $table->string('label')->nullable(); // "Helmet", "Wallet Card"
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();

            $table->timestamps();

            $table->index(['profile_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safe_assets');
    }
};
