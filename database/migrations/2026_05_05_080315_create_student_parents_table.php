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
        Schema::create('student_parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('relation', 10);
            $table->string('full_name', 150)->nullable();
            $table->string('occupation', 150)->nullable();
            $table->string('monthly_income_band', 40)->nullable();
            $table->string('nik', 16)->nullable();
            $table->date('birth_date')->nullable();
            $table->timestamps();

            $table->unique(['student_profile_id', 'relation']);
            $table->index('relation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_parents');
    }
};
