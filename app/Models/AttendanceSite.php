<?php

namespace App\Models;

use Database\Factories\AttendanceSiteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSite extends Model
{
    /** @use HasFactory<AttendanceSiteFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_m',
        'check_in_open_at',
        'check_in_on_time_until',
        'check_in_close_at',
        'check_out_open_at',
        'check_out_close_at',
        'is_active',
        'notes',
    ];

    protected $attributes = [
        'radius_m' => 100,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_m' => 'integer',
            'check_in_open_at' => 'string',
            'check_in_on_time_until' => 'string',
            'check_in_close_at' => 'string',
            'check_out_open_at' => 'string',
            'check_out_close_at' => 'string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Titik absensi aktif untuk pemilih di aplikasi mobile (siswa/guru).
     */
    public function scopeForApiPicker(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('name');
    }

    public function wifiRules(): HasMany
    {
        return $this->hasMany(AttendanceSiteWifiRule::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceDayOverrides(): HasMany
    {
        return $this->hasMany(AttendanceDayOverride::class);
    }
}
