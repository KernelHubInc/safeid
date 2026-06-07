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
        Schema::table('batches', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->integer('total_assets')->default(0);
            $table->integer('generated')->default(0);
            $table->integer('remaining')->default(0);
            $table->enum('status', ['active', 'completed', 'pending', 'cancelled'])->default('pending');
        });
                
            
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('total_assets');
            $table->dropColumn('generated');
            $table->dropColumn('remaining');
            $table->dropColumn('status');
        });
    }
};
