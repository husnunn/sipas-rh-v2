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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('attendance_type', 20);
            $table->string('status', 20)->default('rejected');
            $table->timestamp('attendance_at');
            $table->timestamp('client_time')->nullable();
            $table->string('reason_code', 100)->nullable();
            $table->string('reason_detail')->nullable();
            $table->decimal('distance_m', 10, 2)->nullable();
            $table->json('network_payload')->nullable();
            $table->json('location_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attendance_at']);
            $table->index(['status', 'attendance_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
