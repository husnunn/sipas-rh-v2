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
        Schema::create('attendance_site_wifi_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_site_id');
            $table->string('ssid');
            $table->string('bssid', 17)->nullable();
            $table->string('ip_subnet', 43)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['attendance_site_id', 'is_active']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_site_wifi_rules');
    }
};
