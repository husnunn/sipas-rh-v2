<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\StudentProfileExtension;
use App\Models\TeacherProfile;
use App\Models\TeacherProfileExtension;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileProfileApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_guest_cannot_access_mobile_profile(): void
    {
        $this->getJson('/api/mobile/profile')->assertUnauthorized();
    }

    #[Test]
    public function test_admin_cannot_access_mobile_profile(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/mobile/profile')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah profil.',
            ]);
    }

    #[Test]
    public function test_student_can_get_mobile_profile(): void
    {
        $schoolYear = SchoolYear::factory()->active()->create(['name' => '2025/2026']);
        $classRoom = ClassRoom::factory()->create([
            'school_year_id' => $schoolYear->id,
            'name' => 'X RPL 1',
        ]);
        $student = User::factory()->student()->create([
            'name' => 'Siswa Satu',
            'email' => 'siswa@test.test',
        ]);
        $profile = StudentProfile::factory()->create(['user_id' => $student->id]);
        $profile->classes()->attach($classRoom->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/mobile/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile loaded successfully')
            ->assertJsonPath('data.role', 'student')
            ->assertJsonPath('data.name', 'Siswa Satu')
            ->assertJsonPath('data.email', 'siswa@test.test')
            ->assertJsonPath('data.user.roles.0', 'student')
            ->assertJsonPath('data.profile.id', $profile->id)
            ->assertJsonPath('data.class.id', $classRoom->id)
            ->assertJsonPath('data.class.name', 'X RPL 1')
            ->assertJsonPath('data.school.id', $schoolYear->id)
            ->assertJsonPath('data.school.name', '2025/2026');
    }

    #[Test]
    public function test_teacher_can_get_mobile_profile_with_null_class(): void
    {
        config(['app.school_id' => 99, 'app.school_name' => 'Sekolah Default']);

        $teacher = User::factory()->teacher()->create();
        TeacherProfile::factory()->create(['user_id' => $teacher->id]);

        Sanctum::actingAs($teacher);

        $this->getJson('/api/mobile/profile')
            ->assertOk()
            ->assertJsonPath('data.role', 'teacher')
            ->assertJsonPath('data.user.roles.0', 'teacher')
            ->assertJsonPath('data.profile.id', $teacher->teacherProfile->id)
            ->assertJsonPath('data.class', null)
            ->assertJsonPath('data.school.id', 99)
            ->assertJsonPath('data.school.name', 'Sekolah Default');
    }

    #[Test]
    public function test_student_can_update_profile_photo(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create(['user_id' => $student->id, 'photo' => null]);
        Sanctum::actingAs($student);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $response = $this->post('/api/mobile/profile/photo', [
            'photo' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Foto profil berhasil diperbarui.');

        $url = $response->json('data.profile_photo_url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/', $url);

        $student->refresh();
        $path = StudentProfileExtension::query()
            ->where('student_profile_id', $profile->id)
            ->value('profile_photo_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    #[Test]
    public function test_student_photo_update_deletes_old_file_and_uses_extension_path(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create([
            'user_id' => $student->id,
            'photo' => 'profile-photos/legacy-student.jpg',
        ]);
        Storage::disk('public')->put('profile-photos/legacy-student.jpg', 'old');

        Sanctum::actingAs($student);

        $response = $this->post('/api/mobile/profile/photo', [
            'photo' => UploadedFile::fake()->image('new-avatar.jpg', 100, 100),
        ], ['Accept' => 'application/json']);

        $response->assertOk();

        $newPath = StudentProfileExtension::query()
            ->where('student_profile_id', $profile->id)
            ->value('profile_photo_path');

        $this->assertIsString($newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('profile-photos/legacy-student.jpg');
    }

    #[Test]
    public function test_teacher_mobile_photo_updates_same_extension_field_as_admin(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->teacher()->create();
        $profile = TeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'photo' => 'profile-photos/legacy-teacher.jpg',
        ]);
        TeacherProfileExtension::query()->create([
            'teacher_profile_id' => $profile->id,
            'profile_photo_path' => 'teacher-profile-extensions/'.$profile->id.'/old.png',
        ]);
        Storage::disk('public')->put('teacher-profile-extensions/'.$profile->id.'/old.png', 'old');

        Sanctum::actingAs($teacher);

        $response = $this->post('/api/mobile/profile/photo', [
            'photo' => UploadedFile::fake()->image('teacher-new.jpg', 100, 100),
        ], ['Accept' => 'application/json']);

        $response->assertOk();

        $newPath = TeacherProfileExtension::query()
            ->where('teacher_profile_id', $profile->id)
            ->value('profile_photo_path');

        $this->assertIsString($newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('teacher-profile-extensions/'.$profile->id.'/old.png');
    }

    #[Test]
    public function test_photo_validation_rejects_pdf(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $student->id]);
        Sanctum::actingAs($student);

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->post('/api/mobile/profile/photo', [
            'photo' => $file,
        ], [
            'Accept' => 'application/json',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Foto profil tidak valid.');
    }

    #[Test]
    public function test_photo_validation_rejects_oversized_image(): void
    {
        Storage::fake('public');

        $student = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $student->id]);
        Sanctum::actingAs($student);

        $file = UploadedFile::fake()->create('huge.jpg', 3000, 'image/jpeg');

        $this->post('/api/mobile/profile/photo', [
            'photo' => $file,
        ], [
            'Accept' => 'application/json',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Foto profil tidak valid.');
    }

    #[Test]
    public function test_student_can_update_password_and_clears_plain_password(): void
    {
        $user = User::factory()->student()->create([
            'password' => 'oldpassword123',
            'plain_password' => 'temp-plain',
            'must_change_password' => true,
        ]);
        StudentProfile::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password berhasil diperbarui.');

        $user->refresh();

        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertNull($user->plain_password);
        $this->assertFalse($user->must_change_password);
    }

    #[Test]
    public function test_password_update_rejects_wrong_current_password(): void
    {
        $user = User::factory()->student()->create([
            'password' => 'oldpassword123',
        ]);
        StudentProfile::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Password lama tidak sesuai.')
            ->assertJsonPath('errors.current_password.0', 'Password lama tidak sesuai.');
    }

    #[Test]
    public function test_password_update_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->student()->create([
            'password' => 'oldpassword123',
        ]);
        StudentProfile::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->postJson('/api/mobile/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'different-value',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data tidak valid.');
    }
}
