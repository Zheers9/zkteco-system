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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->integer('day_of_week')->unique(); // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_off_day')->default(false);
            $table->timestamps();
        });

        // Seed default schedule (Mon-Fri 09:00 - 17:00)
        $days = [
            0 => 'Sunday',  // Off by default maybe? Or work day? Let's assume standard Middle East week Sun-Thu or Sat-Thu? Or Mon-Fri?
            // User location is likely Iraq/Kurdistan based on "Her/zkteco" path and user name/context in previous conversations (Kurdish translations).
            // Usually Sat-Thu or Sun-Thu. Let's make all days 08:00-16:00 open initially except Friday (5).
            // Actually, best to just create rows and let user edit.
        ];

        // I will insert default rows in a seeder or just let the user save them. 
        // But to be safe, I'll insert them here so the table isn't empty.
        $now = now();
        $data = [];
        for ($i = 0; $i <= 6; $i++) {
            $data[] = [
                'day_of_week' => $i,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'is_off_day' => ($i === 5), // Friday is usually off in Islamic countries
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        \Illuminate\Support\Facades\DB::table('schedules')->insert($data);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
