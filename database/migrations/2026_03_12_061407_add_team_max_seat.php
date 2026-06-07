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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('plan_type')->default('premium')->after('owner_user_id');
            $table->integer('included_seats')->default(3)->after('plan_type');
            $table->integer('extra_seats')->default(0)->after('included_seats');
            $table->integer('max_seats')->nullable()->after('extra_seats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('plan_type');
            $table->dropColumn('included_seats');
            $table->dropColumn('extra_seats');
            $table->dropColumn('max_seat');
        });
    }
};
