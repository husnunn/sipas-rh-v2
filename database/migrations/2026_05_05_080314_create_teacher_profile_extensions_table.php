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
        Schema::create('teacher_profile_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profile_photo_path')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 120)->nullable();
            $table->text('street_address')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('village', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_profile_extensions');
    }
};
