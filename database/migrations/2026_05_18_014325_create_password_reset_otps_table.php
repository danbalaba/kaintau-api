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
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('otp'); // Stores the OTP code
            $table->integer('attempts')->default(0); // Tracks failed attempts in the current lockout phase
            $table->integer('lockout_phase')->default(0); // 0 = no lock, 1 = 1m lock, 2 = 2m lock, 3 = 24h lock
            $table->timestamp('locked_until')->nullable(); // Time when lockout expires
            $table->timestamp('expires_at')->nullable(); // Time when OTP code expires
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
