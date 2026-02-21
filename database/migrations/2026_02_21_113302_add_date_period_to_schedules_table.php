<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique(['day_of_week']);
            $table->date('start_date')->nullable()->after('day_of_week');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('name')->nullable()->after('id'); // e.g. "Default", "Ramadan"
        });

        // Set name to 'Default' for existing records
        \Illuminate\Support\Facades\DB::table('schedules')->whereNull('name')->update(['name' => 'Default']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'name']);
            $table->unique('day_of_week');
        });
    }
};
