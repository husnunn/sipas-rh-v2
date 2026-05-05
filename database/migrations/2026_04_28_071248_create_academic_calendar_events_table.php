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
        Schema::create('academic_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('event_type', 50);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_attendance')->default(false);
            $table->boolean('override_schedule')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date', 'is_active'], 'academic_calendar_events_date_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_events');
    }
};
