<?php

namespace Tests\Feature\Api;

use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeacherScheduleByDayApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_by_day_returns_only_days_with_schedules(): void
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $user = User::factory()->teacher()->create();
        $profile = TeacherProfile::factory()->create(['user_id' => $user->id]);
        $class = ClassRoom::factory()->recycle($schoolYear)->create();
        $subject = Subject::factory()->create();

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'teacher_profile_id' => $profile->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'semester' => 1,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'teacher_profile_id' => $profile->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'semester' => 1,
            'day_of_week' => 5,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/teacher/schedule/by-day');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.day_of_week', 1)
            ->assertJsonPath('data.0.day_name', 'Senin')
            ->assertJsonPath('data.0.schedules.0.day_of_week', 1)
            ->assertJsonPath('data.1.day_of_week', 5)
            ->assertJsonPath('data.1.day_name', 'Jumat');
    }

    #[Test]
    public function test_by_day_orders_slots_by_start_time_within_each_day(): void
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $user = User::factory()->teacher()->create();
        $profile = TeacherProfile::factory()->create(['user_id' => $user->id]);
        $class = ClassRoom::factory()->recycle($schoolYear)->create();
        $subject = Subject::factory()->create();

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'teacher_profile_id' => $profile->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'semester' => 1,
            'day_of_week' => 2,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'teacher_profile_id' => $profile->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'semester' => 1,
            'day_of_week' => 2,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/teacher/schedule/by-day');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.schedules.0.start_time', '07:00')
            ->assertJsonPath('data.0.schedules.1.start_time', '10:00');
    }

    #[Test]
    public function test_student_cannot_access_teacher_schedule_by_day(): void
    {
        $student = User::factory()->student()->create();
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/teacher/schedule/by-day')->assertForbidden();
    }

    #[Test]
    public function test_unauthenticated_cannot_access_schedule_by_day(): void
    {
        $this->getJson('/api/v1/teacher/schedule/by-day')->assertUnauthorized();
    }
}
