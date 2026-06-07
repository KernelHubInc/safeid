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
            $table->boolean('is_primary')->default(false);
            $table->string('qr_path')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safe_assets', function (Blueprint $table) {
            $table->dropColumn('is_primary');
            $table->dropColumn('qr_path');
        });
    }
};
