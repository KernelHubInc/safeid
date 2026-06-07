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
         Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_id')
                ->nullable()
                ->after('id')
                ->constrained('teams')
                ->nullOnDelete();

            $table->index(['team_id']);
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Human-friendly code for printing/fulfillment tracking
            $table->string('code', 64)->unique(); // e.g. FEB-2026-STICKERS-001

            // Optional metadata
            $table->string('asset_type', 32)->nullable(); // sticker/card/nfc
            $table->string('notes')->nullable();

            // Who created the batch
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
        });

        Schema::create('kit_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Optional: tie to any billing/order system
            $table->string('order_id')->nullable(); // keep flexible

            $table->foreignId('team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();

            // Each asset should only be assigned once
            $table->integer('qr_asset_id')->unique();

            // Seat number inside a team purchase: 1..N
            $table->unsignedInteger('seat_number')->nullable();

            /**
             * reserved -> invited -> claimed -> revoked
             */
            $table->string('status', 24)->default('reserved');

            // Invite flow
            $table->string('assigned_email')->nullable();
            $table->foreignId('invited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('invited_at')->nullable();

            // Claim flow
            $table->foreignId('claimed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();

            $table->index(['team_id', 'status']);
            $table->index(['assigned_email']);
            $table->index(['claimed_by_user_id']);
            $table->unique(['team_id', 'seat_number']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
        Schema::dropIfExists('batches');
        Schema::dropIfExists('kit_assignments');
    }
};
