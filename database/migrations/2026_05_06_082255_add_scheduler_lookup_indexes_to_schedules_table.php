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
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(
                ['school_year_id', 'day_of_week', 'is_active', 'start_time'],
                'schedules_scheduler_start_lookup_index'
            );
            $table->index(
                ['school_year_id', 'day_of_week', 'is_active', 'end_time'],
                'schedules_scheduler_end_lookup_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_scheduler_start_lookup_index');
            $table->dropIndex('schedules_scheduler_end_lookup_index');
        });
    }
};
