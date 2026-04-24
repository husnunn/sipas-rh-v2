<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private SchoolYear $schoolYear;

    private TeacherProfile $teacher;

    private ClassRoom $class;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->schoolYear = SchoolYear::factory()->active()->create();
        $this->teacher = TeacherProfile::factory()->create();
        $this->class = ClassRoom::factory()->recycle($this->schoolYear)->create();
        $this->subject = Subject::factory()->create();
    }

    #[Test]
    public function testAdminCanCreateScheduleWithoutConflict(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_profile_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertRedirect(route('admin.schedules.index'));
        $this->assertDatabaseHas('schedules', [
            'teacher_profile_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
        ]);
    }

    #[Test]
    public function testTeacherScheduleConflictIsRejected(): void
    {
        // Create existing schedule
        Schedule::factory()->create([
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'teacher_profile_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $otherClass = ClassRoom::factory()->recycle($this->schoolYear)->create();

        // Try to create overlapping schedule for same teacher
        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'class_id' => $otherClass->id,
            'subject_id' => $this->subject->id,
            'teacher_profile_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:30',
            'end_time' => '09:30',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    #[Test]
    public function testClassScheduleConflictIsRejected(): void
    {
        // Create existing schedule
        Schedule::factory()->create([
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'teacher_profile_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $otherTeacher = TeacherProfile::factory()->create();

        // Try to create overlapping schedule for same class
        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_profile_id' => $otherTeacher->id,
            'day_of_week' => 2,
            'start_time' => '10:15',
            'end_time' => '11:15',
        ]);

        $response->assertSessionHasErrors('start_time');
    }

    #[Test]
    public function testAdjacentSchedulesDoNotConflict(): void
    {
        // Create existing schedule 08:00-09:00
        Schedule::factory()->create([
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'teacher_profile_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // Schedule starting exactly at 09:00 should NOT conflict
        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_profile_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertRedirect(route('admin.schedules.index'));
        $this->assertDatabaseCount('schedules', 2);
    }

    #[Test]
    public function testDifferentDayDoesNotConflict(): void
    {
        // Create existing schedule on Monday
        Schedule::factory()->create([
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'teacher_profile_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // Same time on Tuesday should NOT conflict
        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_profile_id' => $this->teacher->id,
            'day_of_week' => 2,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertRedirect(route('admin.schedules.index'));
    }

    #[Test]
    public function testDifferentSemesterDoesNotConflict(): void
    {
        // Create existing schedule in semester 1
        Schedule::factory()->create([
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'teacher_profile_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        // Same time in semester 2 should NOT conflict
        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'school_year_id' => $this->schoolYear->id,
            'semester' => 2,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_profile_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response->assertRedirect(route('admin.schedules.index'));
    }

    #[Test]
    public function testEndTimeMustBeAfterStartTime(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
            'school_year_id' => $this->schoolYear->id,
            'semester' => 1,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_profile_id' => $this->teacher->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '09:00',
        ]);

        $response->assertSessionHasErrors('end_time');
    }
}
