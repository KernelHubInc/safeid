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
        Schema::table('users', function (Blueprint $table) {
            // who owns the subscription (null = self owner)
            $table->foreignId('subscription_owner_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();

            // subscription expiry
            $table->timestamp('subscription_expires_at')
                ->nullable()
                ->after('subscription_owner_id');

            // plan type (optional but useful)
            $table->string('plan_type')
                ->nullable()
                ->after('subscription_expires_at');

            $table->index('subscription_owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subscription_owner_id']);
            $table->dropColumn([
                'subscription_owner_id',
                'subscription_expires_at',
                'plan_type'
            ]);
        });
    }
};
