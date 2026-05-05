<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AcademicCalendarEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicCalendarEvent extends Model
{
    /** @use HasFactory<AcademicCalendarEventFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'event_type',
        'is_active',
        'allow_attendance',
        'override_schedule',
        'notes',
    ];

    protected $attributes = [
        'is_active' => true,
        'allow_attendance' => false,
        'override_schedule' => false,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'allow_attendance' => 'boolean',
            'override_schedule' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOverlapsDate(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString());
    }
}
