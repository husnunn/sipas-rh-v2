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
        Schema::create('attendance_day_overrides', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name');
            $table->string('event_type', 50)->default('custom');
            $table->boolean('is_active')->default(true);
            $table->foreignId('attendance_site_id')->nullable()->constrained('attendance_sites')->nullOnDelete();
            $table->boolean('override_attendance_policy')->default(false);
            $table->boolean('override_schedule')->default(false);
            $table->boolean('allow_check_in')->default(true);
            $table->boolean('allow_check_out')->default(true);
            $table->boolean('waive_check_out')->default(false);
            $table->boolean('dismiss_students_early')->default(false);
            $table->time('check_in_open_at')->nullable();
            $table->time('check_in_on_time_until')->nullable();
            $table->time('check_in_close_at')->nullable();
            $table->time('check_out_open_at')->nullable();
            $table->time('check_out_close_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_day_overrides');
    }
};
