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
        Schema::create('student_profile_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('profile_photo_path')->nullable()->comment('Foto profil formal admin; terpisah dari photo di profil untuk mobile');
            $table->text('street_address')->nullable()->comment('Alamat jalan / gedung');
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('village', 120)->nullable()->comment('Kelurahan / desa');
            $table->string('district', 120)->nullable()->comment('Kecamatan');
            $table->string('city', 120)->nullable()->comment('Kota / kabupaten');
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
        Schema::dropIfExists('student_profile_extensions');
    }
};
