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
        Schema::table('attendance_sites', function (Blueprint $table) {
            $table->time('check_in_open_at')->nullable()->after('radius_m');
            $table->time('check_in_on_time_until')->nullable()->after('check_in_open_at');
            $table->time('check_in_close_at')->nullable()->after('check_in_on_time_until');
            $table->time('check_out_open_at')->nullable()->after('check_in_close_at');
            $table->time('check_out_close_at')->nullable()->after('check_out_open_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sites', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_open_at',
                'check_in_on_time_until',
                'check_in_close_at',
                'check_out_open_at',
                'check_out_close_at',
            ]);
        });
    }
};
