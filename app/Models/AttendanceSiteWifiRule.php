<?php

namespace App\Models;

use Database\Factories\AttendanceSiteWifiRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSiteWifiRule extends Model
{
    /** @use HasFactory<AttendanceSiteWifiRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'attendance_site_id',
        'ssid',
        'bssid',
        'ip_subnet',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function attendanceSite(): BelongsTo
    {
        return $this->belongsTo(AttendanceSite::class);
    }
}
