<?php

namespace App\Rules;

use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class NoTeacherScheduleConflict implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    public function __construct(
        private ?int $ignoreScheduleId = null,
    ) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hasConflict = Schedule::query()
            ->where('teacher_profile_id', $this->data['teacher_profile_id'])
            ->where('school_year_id', $this->data['school_year_id'])
            ->where('semester', $this->data['semester'])
            ->where('day_of_week', $this->data['day_of_week'])
            ->where('is_active', true)
            ->when($this->ignoreScheduleId, fn ($q) => $q->where('id', '!=', $this->ignoreScheduleId))
            ->where(function ($query) {
                // Overlap formula: new_start < existing_end AND new_end > existing_start
                $query->where('start_time', '<', $this->data['end_time'])
                    ->where('end_time', '>', $this->data['start_time']);
            })
            ->exists();

        if ($hasConflict) {
            $fail('Guru ini sudah memiliki jadwal pada hari dan jam yang sama. Periksa kembali waktu yang dipilih.');
        }
    }
}
