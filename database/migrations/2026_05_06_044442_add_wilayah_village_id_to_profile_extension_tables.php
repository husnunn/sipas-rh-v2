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
        Schema::table('student_profile_extensions', function (Blueprint $table) {
            $table->char('wilayah_village_id', 10)->nullable()->after('postal_code');
            $table->foreign('wilayah_village_id')
                ->references('id')
                ->on('villages')
                ->nullOnDelete();
        });

        Schema::table('teacher_profile_extensions', function (Blueprint $table) {
            $table->char('wilayah_village_id', 10)->nullable()->after('postal_code');
            $table->foreign('wilayah_village_id')
                ->references('id')
                ->on('villages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profile_extensions', function (Blueprint $table) {
            $table->dropForeign(['wilayah_village_id']);
            $table->dropColumn('wilayah_village_id');
        });

        Schema::table('teacher_profile_extensions', function (Blueprint $table) {
            $table->dropForeign(['wilayah_village_id']);
            $table->dropColumn('wilayah_village_id');
        });
    }
};
