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
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('emergency_profiles')->cascadeOnDelete();

            // Either external contact or linked user
            $table->foreignId('linked_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->string('relationship')->nullable(); // e.g. Mother, Friend
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Alert preferences
            $table->boolean('notify_on_scan')->default(true);
            $table->boolean('notify_on_manual_alert')->default(true);
            $table->boolean('notify_on_crash')->default(true);

            // Ordering
            $table->unsignedTinyInteger('priority')->default(1); // 1 = highest priority

            $table->timestamps();

            $table->index(['profile_id']);
            $table->index(['linked_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
