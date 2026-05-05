<?php

namespace Database\Factories;

use App\Models\AcademicCalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicCalendarEvent>
 */
class AcademicCalendarEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'event_type' => fake()->randomElement(['national_holiday', 'school_holiday', 'school_event', 'exam', 'special_date']),
            'is_active' => true,
            'allow_attendance' => false,
            'override_schedule' => true,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
