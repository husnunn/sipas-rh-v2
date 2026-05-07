<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileDeviceTokenApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function test_guest_cannot_register_device_token(): void
    {
        $this->postJson('/api/mobile/device-token', [
            'token' => 'test-fcm-token',
        ])->assertUnauthorized();
    }

    #[Test]
    public function test_admin_cannot_register_device_token(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/mobile/device-token', [
            'token' => 'test-fcm-token',
        ])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah profil.',
            ]);
    }

    #[Test]
    public function test_student_can_register_device_token(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $student->id]);

        Sanctum::actingAs($student);

        $payload = [
            'token' => 'student-fcm-token-abc',
            'platform' => 'android',
            'device_name' => 'Pixel 8',
            'app_version' => '1.0.0',
            'os_version' => 'Android 14',
        ];

        $this->postJson('/api/mobile/device-token', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Token perangkat berhasil didaftarkan.',
            ]);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $student->id,
            'token' => 'student-fcm-token-abc',
            'platform' => 'android',
            'device_name' => 'Pixel 8',
            'app_version' => '1.0.0',
            'os_version' => 'Android 14',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function test_teacher_can_register_device_token(): void
    {
        $teacher = User::factory()->teacher()->create();
        TeacherProfile::factory()->create(['user_id' => $teacher->id]);

        Sanctum::actingAs($teacher);

        $this->postJson('/api/mobile/device-token', [
            'token' => 'teacher-fcm-token-xyz',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $teacher->id,
            'token' => 'teacher-fcm-token-xyz',
            'platform' => 'android',
        ]);
    }

    #[Test]
    public function test_validation_requires_token(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $student->id]);

        Sanctum::actingAs($student);

        $this->postJson('/api/mobile/device-token', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['token']);
    }

    #[Test]
    public function test_same_token_updates_existing_row(): void
    {
        $student = User::factory()->student()->create();
        StudentProfile::factory()->create(['user_id' => $student->id]);

        Sanctum::actingAs($student);

        $token = 'same-device-token';

        $this->postJson('/api/mobile/device-token', [
            'token' => $token,
            'device_name' => 'Phone A',
        ])->assertOk();

        $this->assertSame(1, UserDeviceToken::query()->where('user_id', $student->id)->count());

        $this->postJson('/api/mobile/device-token', [
            'token' => $token,
            'device_name' => 'Phone B',
        ])->assertOk();

        $this->assertSame(1, UserDeviceToken::query()->where('user_id', $student->id)->count());

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $student->id,
            'token' => $token,
            'device_name' => 'Phone B',
        ]);
    }
}
