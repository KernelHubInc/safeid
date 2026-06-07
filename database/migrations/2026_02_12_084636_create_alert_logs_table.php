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
        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('profile_id')->constrained('emergency_profiles')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('emergency_contacts')->nullOnDelete();
            $table->foreignId('scan_log_id')->nullable()->constrained('scan_logs')->nullOnDelete();

            $table->enum('event', ['scan', 'manual_alert', 'crash'])->default('scan');
            $table->enum('channel', ['sms', 'email', 'in_app'])->default('sms');

            $table->string('to')->nullable();          // phone/email
            $table->string('subject')->nullable();     // email subject
            $table->text('message')->nullable();       // what you sent (or template id)

            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->string('provider')->nullable();    // twilio, semaphore, etc
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['profile_id', 'created_at']);
            $table->index(['event', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
    }
};
