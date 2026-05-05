<?php

namespace App\Models;

use Database\Factories\AttendanceValidationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceValidationLog extends Model
{
    /** @use HasFactory<AttendanceValidationLogFactory> */
    use HasFactory;

    protected $fillable = [
        'attendance_record_id',
        'user_id',
        'status',
        'reason_code',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
