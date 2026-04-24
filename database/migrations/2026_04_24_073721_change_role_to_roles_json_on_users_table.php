<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add new JSON column
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('password');
        });

        // 2. Migrate existing data: role string → roles JSON array
        DB::table('users')->orderBy('id')->each(function ($user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['roles' => json_encode([$user->role])]);
        });

        // 3. Drop old column and indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_active']);
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('password');
        });

        // Migrate back: take first role from JSON array
        DB::table('users')->orderBy('id')->each(function ($user) {
            $roles = json_decode($user->roles, true);
            DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $roles[0] ?? 'admin']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
            $table->index('role');
            $table->index(['role', 'is_active']);
        });
    }
};
