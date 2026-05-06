<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah administratif Indonesia (provinsi → kab/kota → kecamatan → kelurahan/desa).
 *
 * Struktur mengikuti {@see database/sql/indonesia.sql}. File tersebut berisi INSERT besar;
 * setelah migrate, impor data ke MySQL dengan menjalankan dump tersebut atau menyalin
 * hanya blok INSERT ke klien SQL Anda (lewati DROP/CREATE di dalam dump agar tidak bentrok).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->char('id', 2)->primary();
            $table->string('name');
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->char('id', 4)->primary();
            $table->char('province_id', 2);
            $table->string('name');

            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index('province_id');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->char('id', 7)->primary();
            $table->char('regency_id', 4);
            $table->string('name');

            $table->foreign('regency_id')
                ->references('id')
                ->on('regencies')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index('regency_id');
        });

        Schema::create('villages', function (Blueprint $table) {
            $table->char('id', 10)->primary();
            $table->char('district_id', 7);
            $table->string('name');

            $table->foreign('district_id')
                ->references('id')
                ->on('districts')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->index('district_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
