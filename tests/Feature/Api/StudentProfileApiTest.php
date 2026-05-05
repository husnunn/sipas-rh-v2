<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceSite;
use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_me_includes_attendance_sites(): void
    {
        $schoolYear = SchoolYear::factory()->active()->create();
        $classRoom = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        TeacherProfile::factory()->create();
        Subject::factory()->create();

        $student = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create(['user_id' => $student->id]);
        $profile->classes()->attach($classRoom->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

        $site = AttendanceSite::factory()->create([
            'name' => 'Gerbang A',
            'latitude' => -6.2,
            'longitude' => 106.81,
            'radius_m' => 200,
            'is_active' => true,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/v1/student/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'nis', 'full_name'],
                'attendance_sites' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'radius_m'],
                ],
            ])
            ->assertJsonPath('attendance_sites.0.id', $site->id)
            ->assertJsonPath('attendance_sites.0.name', 'Gerbang A');
    }
}
