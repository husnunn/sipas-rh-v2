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
        Schema::table('attendance_site_wifi_rules', function (Blueprint $table) {
            $table->foreign('attendance_site_id')
                ->references('id')
                ->on('attendance_sites')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_site_wifi_rules', function (Blueprint $table) {
            $table->dropForeign(['attendance_site_id']);
        });
    }
};
