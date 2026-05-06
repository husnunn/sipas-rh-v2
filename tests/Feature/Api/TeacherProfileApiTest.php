<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceSite;
use App\Models\ClassRoom;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherProfileExtension;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_me_returns_complete_profile_payload(): void
    {
        Province::query()->create(['id' => '35', 'name' => 'Jawa Timur']);
        Regency::query()->create(['id' => '3516', 'province_id' => '35', 'name' => 'Kabupaten Mojokerto']);
        District::query()->create(['id' => '3516120', 'regency_id' => '3516', 'name' => 'Trowulan']);
        Village::query()->create(['id' => '3516120009', 'district_id' => '3516120', 'name' => 'Trowulan']);

        $teacherUser = User::factory()->teacher()->create();
        $teacherProfile = TeacherProfile::factory()->create([
            'user_id' => $teacherUser->id,
            'full_name' => 'Guru Lengkap',
        ]);
        TeacherProfileExtension::query()->create([
            'teacher_profile_id' => $teacherProfile->id,
            'birth_place' => 'Mojokerto',
            'city' => 'Mojokerto',
            'province' => 'Jawa Timur',
            'wilayah_village_id' => '3516120009',
            'religion' => 'Islam',
        ]);

        $subject = Subject::factory()->create(['name' => 'Matematika']);
        $teacherProfile->subjects()->attach($subject->id);

        $schoolYear = SchoolYear::factory()->active()->create();
        ClassRoom::factory()->create([
            'school_year_id' => $schoolYear->id,
            'name' => '9A',
            'level' => 9,
            'homeroom_teacher_id' => $teacherProfile->id,
        ]);

        $site = AttendanceSite::factory()->create([
            'name' => 'Gerbang Guru',
            'is_active' => true,
        ]);

        Sanctum::actingAs($teacherUser);

        $response = $this->getJson('/api/v1/teacher/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nip',
                    'full_name',
                    'subjects',
                    'extension' => ['birth_place', 'city', 'province', 'wilayah_village_id', 'religion'],
                    'homeroom_classes' => [
                        '*' => ['id', 'name', 'level'],
                    ],
                    'user' => ['id', 'name', 'username', 'email', 'roles', 'is_active', 'must_change_password'],
                ],
                'attendance_sites' => [
                    '*' => ['id', 'name', 'latitude', 'longitude', 'radius_m'],
                ],
            ])
            ->assertJsonPath('data.full_name', 'Guru Lengkap')
            ->assertJsonPath('data.extension.city', 'Mojokerto')
            ->assertJsonPath('data.homeroom_classes.0.name', '9A')
            ->assertJsonPath('data.user.roles.0', 'teacher')
            ->assertJsonPath('attendance_sites.0.id', $site->id);
    }
}
