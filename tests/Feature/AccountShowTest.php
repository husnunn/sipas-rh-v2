<?php

namespace Tests\Feature;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AccountShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_account_show(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('admin.accounts.show', $user));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_view_account_detail(): void
    {
        $teacher = User::factory()->teacher()->create();
        $target = User::factory()->create();

        $this->actingAs($teacher);

        $response = $this->get(route('admin.accounts.show', $target));

        $response->assertForbidden();
    }

    public function test_admin_can_view_account_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->teacher()->create();

        $this->actingAs($admin);

        $response = $this->get(route('admin.accounts.show', $target));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Accounts/Show')
            ->has('user')
            ->where('user.id', $target->id)
            ->where('user.name', $target->name)
            ->where('user.username', $target->username)
            ->where('user.email', $target->email)
        );
    }

    public function test_account_detail_includes_teacher_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();

        TeacherProfile::factory()->create([
            'user_id' => $teacher->id,
            'full_name' => 'Budi Santoso',
            'nip' => '123456',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.accounts.show', $teacher));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Accounts/Show')
            ->where('user.teacher_profile.full_name', 'Budi Santoso')
            ->where('user.teacher_profile.nip', '123456')
        );
    }

    public function test_account_detail_without_profile_returns_null(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin);

        $response = $this->get(route('admin.accounts.show', $target));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Accounts/Show')
            ->where('user.teacher_profile', null)
            ->where('user.student_profile', null)
        );
    }

    public function test_account_detail_shows_plain_password(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create([
            'plain_password' => 'secret123',
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.accounts.show', $target));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Accounts/Show')
            ->where('user.plain_password', 'secret123')
        );
    }

    public function test_store_saves_plain_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->post(route('admin.accounts.store'), [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['teacher'],
        ]);

        $user = User::where('username', 'testuser')->first();
        $this->assertNotNull($user);
        $this->assertEquals('password123', $user->plain_password);
    }

    public function test_reset_password_updates_plain_password(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create([
            'plain_password' => 'oldpassword',
        ]);

        $this->actingAs($admin);

        $this->post(route('admin.accounts.reset-password', $target), [
            'new_password' => 'newpass99',
        ]);

        $target->refresh();
        $this->assertEquals('newpass99', $target->plain_password);
        $this->assertTrue($target->must_change_password);
    }
}
