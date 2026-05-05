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
        Schema::create('daily_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('attendance_site_id')->constrained('attendance_sites')->restrictOnDelete();
            $table->date('date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('status', 20)->nullable();
            $table->unsignedSmallInteger('late_minutes')->nullable();
            $table->string('check_in_reason_code', 100)->nullable();
            $table->string('check_in_reason_detail')->nullable();
            $table->json('network_payload')->nullable();
            $table->json('location_payload')->nullable();
            $table->json('device_payload')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['student_profile_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_attendances');
    }
};
