<?php

namespace App\Models;

use App\Enums\AttendanceManualRecordStatus;
use App\Enums\AttendanceManualType;
use Database\Factories\AttendanceManualStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceManualStatus extends Model
{
    /** @use HasFactory<AttendanceManualStatusFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_profile_id',
        'attendance_site_id',
        'date',
        'type',
        'reason',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => AttendanceManualType::class,
            'status' => AttendanceManualRecordStatus::class,
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

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isApproved(): bool
    {
        return $this->status === AttendanceManualRecordStatus::Approved;
    }
}
