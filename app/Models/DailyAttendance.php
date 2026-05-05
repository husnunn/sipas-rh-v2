<?php

namespace App\Models;

use App\Casts\UtcDatetimeCast;
use App\Enums\DailyAttendancePhysicalStatus;
use Database\Factories\DailyAttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAttendance extends Model
{
    /** @use HasFactory<DailyAttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_profile_id',
        'attendance_site_id',
        'date',
        'check_in_at',
        'check_out_at',
        'status',
        'late_minutes',
        'check_in_reason_code',
        'check_in_reason_detail',
        'network_payload',
        'location_payload',
        'device_payload',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_at' => UtcDatetimeCast::class,
            'check_out_at' => UtcDatetimeCast::class,
            'late_minutes' => 'integer',
            'network_payload' => 'array',
            'location_payload' => 'array',
            'device_payload' => 'array',
            'status' => DailyAttendancePhysicalStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function attendanceSite(): BelongsTo
    {
        return $this->belongsTo(AttendanceSite::class);
    }
}
