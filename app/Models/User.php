<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'username', 'password', 'plain_password', 'roles', 'is_active', 'must_change_password', 'last_login_at'])]
#[Hidden(['password', 'plain_password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $attributes = [
        'roles' => '["admin"]',
        'is_active' => true,
        'must_change_password' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plain_password' => 'encrypted',
            'roles' => 'array',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // --- Relationships ---

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function passwordResetAudits(): HasMany
    {
        return $this->hasMany(PasswordResetAudit::class);
    }

    public function passwordResetsPerformed(): HasMany
    {
        return $this->hasMany(PasswordResetAudit::class, 'reset_by_admin_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function dailyAttendances(): HasMany
    {
        return $this->hasMany(DailyAttendance::class);
    }

    public function attendanceManualStatuses(): HasMany
    {
        return $this->hasMany(AttendanceManualStatus::class);
    }

    public function attendanceDayOverridesCreated(): HasMany
    {
        return $this->hasMany(AttendanceDayOverride::class, 'created_by');
    }

    public function attendanceDayOverridesUpdated(): HasMany
    {
        return $this->hasMany(AttendanceDayOverride::class, 'updated_by');
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRole(Builder $query, string $role): Builder
    {
        return $query->whereJsonContains('roles', $role);
    }

    // --- Helpers ---

    /**
     * Check if user has the given role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? [], true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    /**
     * Hapus audit reset password yang mereferensi salah satu user pada daftar
     * (sebagai akun yang di-reset atau sebagai admin yang mereset), agar baris
     * {@see User} dapat dihapus (termasuk bulk delete lewat query builder).
     *
     * @param  array<int|string>  $userIds
     */
    public static function purgePasswordResetAuditsForUserIds(array $userIds): void
    {
        /** @var array<int, int> */
        $normalized = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $userIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($normalized === []) {
            return;
        }

        PasswordResetAudit::query()
            ->where(function ($query) use ($normalized): void {
                $query->whereIn('user_id', $normalized)
                    ->orWhereIn('reset_by_admin_id', $normalized);
            })
            ->delete();
    }
}
