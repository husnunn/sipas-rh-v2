<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AttendanceDayOverrideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDayOverride extends Model
{
    /** @use HasFactory<AttendanceDayOverrideFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'event_type',
        'is_active',
        'attendance_site_id',
        'override_attendance_policy',
        'override_schedule',
        'allow_check_in',
        'allow_check_out',
        'waive_check_out',
        'dismiss_students_early',
        'check_in_open_at',
        'check_in_on_time_until',
        'check_in_close_at',
        'check_out_open_at',
        'check_out_close_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'is_active' => true,
        'event_type' => 'custom',
        'override_attendance_policy' => false,
        'override_schedule' => false,
        'allow_check_in' => true,
        'allow_check_out' => true,
        'waive_check_out' => false,
        'dismiss_students_early' => false,
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
            'override_attendance_policy' => 'boolean',
            'override_schedule' => 'boolean',
            'allow_check_in' => 'boolean',
            'allow_check_out' => 'boolean',
            'waive_check_out' => 'boolean',
            'dismiss_students_early' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('date', $date->toDateString());
    }

    public function attendanceSite(): BelongsTo
    {
        return $this->belongsTo(AttendanceSite::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
