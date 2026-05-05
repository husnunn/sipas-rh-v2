<?php

namespace App\Models;

use App\Services\Attendance\SchoolAttendanceTime;
use Carbon\Carbon;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_site_id',
        'schedule_id',
        'attendance_type',
        'status',
        'attendance_at',
        'client_time',
        'reason_code',
        'reason_detail',
        'distance_m',
        'network_payload',
        'location_payload',
    ];

    protected function casts(): array
    {
        return [
            'attendance_at' => 'datetime',
            'client_time' => 'datetime',
            'distance_m' => 'float',
            'network_payload' => 'array',
            'location_payload' => 'array',
        ];
    }

    /**
     * ISO8601 mirror of attendance_at for API and Inertia (field name attendance_time), in school timezone.
     */
    protected function attendanceTime(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $raw = $this->getRawOriginal('attendance_at');
                if ($raw === null || $raw === '') {
                    return null;
                }

                return Carbon::parse($raw, SchoolAttendanceTime::timezone())
                    ->toIso8601String();
            },
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceSite(): BelongsTo
    {
        return $this->belongsTo(AttendanceSite::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function validationLogs(): HasMany
    {
        return $this->hasMany(AttendanceValidationLog::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
