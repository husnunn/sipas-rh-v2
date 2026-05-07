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
        Schema::table('user_device_tokens', function (Blueprint $table) {
            $table->string('device_name', 255)->nullable()->after('platform');
            $table->string('app_version', 50)->nullable()->after('device_name');
            $table->string('os_version', 100)->nullable()->after('app_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            $table->dropColumn(['device_name', 'app_version', 'os_version']);
        });
    }
};
