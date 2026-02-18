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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip')->unique();
            $table->integer('port')->default(4370);
            $table->string('location')->nullable();
            $table->boolean('status')->default(true); // Active/Inactive
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('device_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->string('user_id_on_device'); // The ID stored on the device (often numeric but sometimes string)
            $table->string('name')->nullable();
            $table->string('role')->nullable(); // User, Admin
            $table->string('password')->nullable();
            $table->string('card_number')->nullable();
            $table->timestamps();

            // A user on a device is unique by device_id + user_id_on_device
            $table->unique(['device_id', 'user_id_on_device']);
        });

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->string('user_id_on_device'); // Reference to the user ID on that device
            $table->timestamp('timestamp');
            $table->integer('status')->default(0); // Check-in, Check-out, etc.
            $table->integer('type')->default(0);
            $table->timestamps();

            // Avoid duplicates
            $table->unique(['device_id', 'user_id_on_device', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('device_users');
        Schema::dropIfExists('devices');
    }
};
