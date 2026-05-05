<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicCalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicCalendarEventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_academic_calendar_event(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post(route('admin.academic-calendar-events.store'), [
            'name' => 'Libur Semester',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'event_type' => 'school_holiday',
            'is_active' => true,
            'allow_attendance' => false,
            'override_schedule' => true,
        ]);

        $response->assertRedirect(route('admin.academic-calendar-events.index'));
        $event = AcademicCalendarEvent::query()->first();
        $this->assertNotNull($event);
        $this->assertEquals('Libur Semester', $event->name);
        $this->assertTrue($event->override_schedule);
    }
}
