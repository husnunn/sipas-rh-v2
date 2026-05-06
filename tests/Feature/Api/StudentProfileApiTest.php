<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceSite;
use App\Models\ClassRoom;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SchoolYear;
use App\Models\StudentParent;
use App\Models\StudentProfile;
use App\Models\StudentProfileExtension;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_me_includes_attendance_sites(): void
    {
        Province::query()->create(['id' => '32', 'name' => 'Jawa Barat']);
        Regency::query()->create(['id' => '3201', 'province_id' => '32', 'name' => 'Kabupaten Bogor']);
        District::query()->create(['id' => '3201010', 'regency_id' => '3201', 'name' => 'Cibinong']);
        Village::query()->create(['id' => '3201010001', 'district_id' => '3201010', 'name' => 'Pakansari']);

        $schoolYear = SchoolYear::factory()->active()->create();
        $classRoom = ClassRoom::factory()->create(['school_year_id' => $schoolYear->id]);
        TeacherProfile::factory()->create();
        Subject::factory()->create();

        $student = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create(['user_id' => $student->id]);
        StudentProfileExtension::query()->create([
            'student_profile_id' => $profile->id,
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'wilayah_village_id' => '3201010001',
            'religion' => 'Islam',
        ]);
        StudentParent::query()->create([
            'student_profile_id' => $profile->id,
            'relation' => 'mother',
            'full_name' => 'Ibu Siswa',
            'occupation' => 'Guru',
            'monthly_income_band' => 'm3_to_8m',
        ]);
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
                'data' => [
                    'id',
                    'nis',
                    'full_name',
                    'extension' => ['city', 'province', 'wilayah_village_id', 'religion'],
                    'parents' => [
                        '*' => ['relation', 'full_name', 'occupation', 'monthly_income_band'],
                    ],
                    'user' => ['id', 'name', 'username', 'email', 'roles', 'is_active', 'must_change_password'],
                ],
                'attendance_sites' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'radius_m'],
                ],
            ])
            ->assertJsonPath('data.extension.city', 'Bandung')
            ->assertJsonPath('data.parents.0.relation', 'mother')
            ->assertJsonPath('data.user.roles.0', 'student')
            ->assertJsonPath('attendance_sites.0.id', $site->id)
            ->assertJsonPath('attendance_sites.0.name', 'Gerbang A');
    }
}
