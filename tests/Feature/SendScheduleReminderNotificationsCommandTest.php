<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Schedule;
use App\Models\ScheduleNotificationLog;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\UserDeviceToken;
use App\Services\Notifications\FcmPushNotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendScheduleReminderNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('queue.default', 'sync');
        Config::set('app.school_timezone', 'Asia/Jakarta');
        $this->mock(FcmPushNotificationSender::class, function ($mock) {
            $mock->shouldReceive('sendToToken')->andReturn();
        });
    }

    #[Test]
    public function it_sends_teacher_start_reminder_once_per_minute_window(): void
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $subject = Subject::factory()->create(['name' => 'PJOK']);
        $classRoom = ClassRoom::factory()->recycle($schoolYear)->create();
        $teacher = TeacherProfile::factory()->create();

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'semester' => 1,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => '07:00:00',
            'end_time' => '09:15:00',
            'is_active' => true,
        ]);

        UserDeviceToken::query()->create([
            'user_id' => $teacher->user_id,
            'token' => 'teacher-device-token',
            'platform' => 'android',
            'is_active' => true,
        ]);

        $this->artisan('notifications:send-schedule-reminders', ['--at' => '2026-05-04 06:45:10'])
            ->assertSuccessful();

        $this->artisan('notifications:send-schedule-reminders', ['--at' => '2026-05-04 06:45:50'])
            ->assertSuccessful();

        $this->assertDatabaseCount('schedule_notification_logs', 1);
        $this->assertDatabaseHas('schedule_notification_logs', [
            'event_type' => 'teacher_schedule_start_reminder',
            'target_role' => 'teacher',
            'recipient_id' => $teacher->user_id,
            'status' => 'sent',
        ]);
    }

    #[Test]
    public function it_sends_students_only_for_first_schedule_of_the_day(): void
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $subject = Subject::factory()->create(['name' => 'Matematika']);
        $teacher = TeacherProfile::factory()->create();
        $classRoom = ClassRoom::factory()->recycle($schoolYear)->create();

        $firstSchedule = Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'semester' => 1,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
            'is_active' => true,
        ]);

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'semester' => 1,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'is_active' => true,
        ]);

        $students = StudentProfile::factory()->count(2)->create();
        foreach ($students as $student) {
            $classRoom->students()->attach($student->id, [
                'school_year_id' => $schoolYear->id,
                'is_active' => true,
            ]);

            UserDeviceToken::query()->create([
                'user_id' => $student->user_id,
                'token' => 'student-device-token-'.$student->id,
                'platform' => 'android',
                'is_active' => true,
            ]);
        }

        $this->artisan('notifications:send-schedule-reminders', ['--at' => '2026-05-04 06:45:15'])
            ->assertSuccessful();

        $this->assertSame(
            2,
            ScheduleNotificationLog::query()
                ->where('event_type', 'student_first_schedule_reminder')
                ->count()
        );
        $this->assertDatabaseHas('schedule_notification_logs', [
            'event_type' => 'student_first_schedule_reminder',
            'schedule_id' => $firstSchedule->id,
            'target_role' => 'student',
            'status' => 'sent',
        ]);
    }
}
