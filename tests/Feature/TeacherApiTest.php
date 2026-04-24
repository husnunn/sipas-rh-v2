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

class TeacherApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function testTeacherCanLoginWithValidCredentials(): void
    {
        $user = User::factory()->teacher()->create([
            'username' => 'guru01',
            'password' => 'password123',
        ]);
        TeacherProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/v1/teacher/login', [
            'username' => 'guru01',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'token', 'must_change_password', 'user']);
    }

    #[Test]
    public function testTeacherCannotLoginWithWrongPassword(): void
    {
        User::factory()->teacher()->create([
            'username' => 'guru01',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/teacher/login', [
            'username' => 'guru01',
            'password' => 'wrong',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    #[Test]
    public function testStudentCannotLoginViaTeacherEndpoint(): void
    {
        User::factory()->student()->create([
            'username' => 'siswa01',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/teacher/login', [
            'username' => 'siswa01',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function testInactiveTeacherCannotLogin(): void
    {
        User::factory()->teacher()->create([
            'username' => 'guru01',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/teacher/login', [
            'username' => 'guru01',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
    }

    #[Test]
    public function testTeacherCanGetProfile(): void
    {
        $user = User::factory()->teacher()->create();
        $profile = TeacherProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/teacher/me');

        $response->assertOk()
            ->assertJsonFragment(['full_name' => $profile->full_name]);
    }

    #[Test]
    public function testTeacherCanGetSchedule(): void
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
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/teacher/schedule');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function testStudentCannotAccessTeacherApi(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/teacher/me');

        $response->assertForbidden();
    }

    #[Test]
    public function testUnauthenticatedCannotAccessTeacherApi(): void
    {
        $response = $this->getJson('/api/v1/teacher/me');

        $response->assertUnauthorized();
    }
}
