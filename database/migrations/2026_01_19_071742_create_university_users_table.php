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
        Schema::create('university_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user_sid')->unique(); // Student ID or University ID
            $table->foreignId('device_user_id')->nullable()->constrained('device_users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_users');
    }
};
