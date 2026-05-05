<?php

namespace Tests\Feature\Admin;

use App\Models\ClassRoom;
use App\Models\PasswordResetAudit;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDestroyTest extends TestCase
{
    use RefreshDatabase;

    // ── Students ──────────────────────────────────────────────

    public function test_guest_cannot_bulk_destroy_students(): void
    {
        $response = $this->delete(route('admin.students.bulk-destroy'), ['ids' => [1]]);
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_bulk_destroy_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher);

        $response = $this->delete(route('admin.students.bulk-destroy'), ['ids' => [1]]);
        $response->assertForbidden();
    }

    public function test_admin_can_bulk_destroy_students(): void
    {
        $admin = User::factory()->admin()->create();
        $students = StudentProfile::factory()->count(3)->create();
        $ids = $students->pluck('id')->toArray();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.students.bulk-destroy'), ['ids' => $ids]);

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('flash.type', 'success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('student_profiles', ['id' => $id]);
        }
    }

    public function test_bulk_destroy_students_requires_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->delete(route('admin.students.bulk-destroy'), ['ids' => []]);
        $response->assertSessionHasErrors('ids');
    }

    // ── Teachers ──────────────────────────────────────────────

    public function test_guest_cannot_bulk_destroy_teachers(): void
    {
        $response = $this->delete(route('admin.teachers.bulk-destroy'), ['ids' => [1]]);
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_bulk_destroy_teachers(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAs($student);

        $response = $this->delete(route('admin.teachers.bulk-destroy'), ['ids' => [1]]);
        $response->assertForbidden();
    }

    public function test_admin_can_bulk_destroy_teachers(): void
    {
        $admin = User::factory()->admin()->create();
        $teachers = TeacherProfile::factory()->count(3)->create();
        $ids = $teachers->pluck('id')->toArray();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.teachers.bulk-destroy'), ['ids' => $ids]);

        $response->assertRedirect(route('admin.teachers.index'));
        $response->assertSessionHas('flash.type', 'success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('teacher_profiles', ['id' => $id]);
        }
    }

    // ── Classes ───────────────────────────────────────────────

    public function test_guest_cannot_bulk_destroy_classes(): void
    {
        $response = $this->delete(route('admin.classes.bulk-destroy'), ['ids' => [1]]);
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_bulk_destroy_classes(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolYear = SchoolYear::factory()->create();
        $classes = ClassRoom::factory()
            ->count(3)
            ->sequence(
                ['name' => '7A', 'level' => 7],
                ['name' => '8A', 'level' => 8],
                ['name' => '9A', 'level' => 9],
            )
            ->recycle($schoolYear)
            ->create();
        $ids = $classes->pluck('id')->toArray();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.classes.bulk-destroy'), ['ids' => $ids]);

        $response->assertRedirect(route('admin.classes.index'));
        $response->assertSessionHas('flash.type', 'success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('classes', ['id' => $id]);
        }
    }

    // ── Subjects ──────────────────────────────────────────────

    public function test_guest_cannot_bulk_destroy_subjects(): void
    {
        $response = $this->delete(route('admin.subjects.bulk-destroy'), ['ids' => [1]]);
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_bulk_destroy_subjects(): void
    {
        $admin = User::factory()->admin()->create();
        $subjects = Subject::factory()->count(3)->create();
        $ids = $subjects->pluck('id')->toArray();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.subjects.bulk-destroy'), ['ids' => $ids]);

        $response->assertRedirect(route('admin.subjects.index'));
        $response->assertSessionHas('flash.type', 'success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('subjects', ['id' => $id]);
        }
    }

    // ── Schedules ─────────────────────────────────────────────

    public function test_guest_cannot_bulk_destroy_schedules(): void
    {
        $response = $this->delete(route('admin.schedules.bulk-destroy'), ['ids' => [1]]);
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_bulk_destroy_schedules(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolYear = SchoolYear::factory()->create();
        $class = ClassRoom::factory()->recycle($schoolYear)->create();
        $schedules = Schedule::factory()
            ->count(3)
            ->sequence(
                ['day_of_week' => 1],
                ['day_of_week' => 2],
                ['day_of_week' => 3],
            )
            ->recycle([$schoolYear, $class])
            ->create();
        $ids = $schedules->pluck('id')->toArray();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.schedules.bulk-destroy'), ['ids' => $ids]);

        $response->assertRedirect(route('admin.schedules.index'));
        $response->assertSessionHas('flash.type', 'success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('schedules', ['id' => $id]);
        }
    }

    // ── Accounts ──────────────────────────────────────────────

    public function test_guest_cannot_bulk_destroy_accounts(): void
    {
        $response = $this->delete(route('admin.accounts.bulk-destroy'), ['ids' => [1]]);
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_bulk_destroy_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $users = User::factory()->count(3)->create();
        $ids = $users->pluck('id')->toArray();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.accounts.bulk-destroy'), ['ids' => $ids]);

        $response->assertRedirect(route('admin.accounts.index'));
        $response->assertSessionHas('flash.type', 'success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('users', ['id' => $id]);
        }
    }

    public function test_admin_cannot_bulk_destroy_self(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.accounts.bulk-destroy'), ['ids' => [$admin->id]]);

        $response->assertSessionHas('flash.type', 'error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_bulk_destroy_accounts_excludes_self(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.accounts.bulk-destroy'), [
            'ids' => [$admin->id, $other->id],
        ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $response->assertSessionHas('flash.type', 'success');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_admin_bulk_destroy_accounts_succeeds_and_drops_related_password_audits(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        PasswordResetAudit::create([
            'user_id' => $user->id,
            'reset_by_admin_id' => $admin->id,
        ]);

        $this->actingAs($admin);
        $response = $this->from(route('admin.accounts.index'))->delete(route('admin.accounts.bulk-destroy'), [
            'ids' => [$user->id],
        ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $response->assertSessionHas('flash.type', 'success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_audits', ['user_id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_bulk_destroy_classes_fails_with_friendly_flash_when_class_has_schedules(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolYear = SchoolYear::factory()->create();
        $class = ClassRoom::factory()->recycle($schoolYear)->create();
        $subject = Subject::factory()->create();
        $teacher = TeacherProfile::factory()->create();

        Schedule::factory()->create([
            'school_year_id' => $schoolYear->id,
            'semester' => 1,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_profile_id' => $teacher->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $this->actingAs($admin);
        $response = $this->from(route('admin.classes.index'))->delete(route('admin.classes.bulk-destroy'), [
            'ids' => [$class->id],
        ]);

        $response->assertRedirect(route('admin.classes.index'));
        $response->assertSessionHas('flash.type', 'error');
        $this->assertIsString(session('flash.message'));
        $this->assertDatabaseHas('classes', ['id' => $class->id]);
    }
}
