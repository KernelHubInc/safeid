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
        Schema::table('safe_assets', function (Blueprint $table) {
            $table->string('kit_plan')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('claim_code')->nullable();
            $table->enum('status', ['generated', 'packaged', 'sold', 'activated', 'registered', 'disabled'])->default('generated');
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->integer('owner_user_id')->nullable();
            $table->integer('team_id')->nullable();
            $table->json('meta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safe_assets', function (Blueprint $table) {
            $table->dropColumn('kit_plan');
            $table->dropColumn('batch_id');
            $table->dropColumn('claim_code');
            $table->dropColumn('status');
            $table->dropColumn('sold_at');
            $table->dropColumn('registered_at');
            $table->dropColumn('owner_user_id');
            $table->dropColumn('team_id');
            $table->dropColumn('meta');
        });
    }
};
