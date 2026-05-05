<?php

namespace Tests\Feature\Admin;

use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StudentIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_students_index(): void
    {
        $response = $this->get(route('admin.students.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_list_filters_by_active_class_placement(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolYear = SchoolYear::factory()->create();
        $classAlpha = ClassRoom::factory()->create([
            'school_year_id' => $schoolYear->id,
            'name' => 'Kelas Alpha',
        ]);
        $classBeta = ClassRoom::factory()->create([
            'school_year_id' => $schoolYear->id,
            'name' => 'Kelas Beta',
        ]);

        $inAlpha = StudentProfile::factory()->create([
            'nis' => '100001',
        ]);
        $inAlpha->classes()->attach($classAlpha->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

        $inBeta = StudentProfile::factory()->create([
            'nis' => '100002',
        ]);
        $inBeta->classes()->attach($classBeta->id, [
            'school_year_id' => $schoolYear->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.students.index', ['class_id' => $classAlpha->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Students/Index')
                ->has('students.data', 1)
                ->where('students.data.0.id', $inAlpha->id)
                ->where('filters.class_id', $classAlpha->id)
            );

        $this->get(route('admin.students.index', [
            'school_year_id' => $schoolYear->id,
            'class_id' => $classBeta->id,
        ]))->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.id', $inBeta->id)
            );
    }

    public function test_student_list_filters_only_by_school_year(): void
    {
        $admin = User::factory()->admin()->create();
        $yearA = SchoolYear::factory()->create(['name' => 'Unique SY A '.uniqid()]);
        $yearB = SchoolYear::factory()->create(['name' => 'Unique SY B '.uniqid()]);
        $classA = ClassRoom::factory()->create(['school_year_id' => $yearA->id]);
        $classB = ClassRoom::factory()->create(['school_year_id' => $yearB->id]);

        $pYearA = StudentProfile::factory()->create();
        $pYearA->classes()->attach($classA->id, [
            'school_year_id' => $yearA->id,
            'is_active' => true,
        ]);

        $pYearB = StudentProfile::factory()->create();
        $pYearB->classes()->attach($classB->id, [
            'school_year_id' => $yearB->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.students.index', ['school_year_id' => $yearB->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.id', $pYearB->id)
            );
    }

    public function test_student_search_matches_nis(): void
    {
        $admin = User::factory()->admin()->create();
        StudentProfile::factory()->create([
            'nis' => '88887777',
            'nisn' => '1112223334',
        ]);
        StudentProfile::factory()->count(3)->create();

        $this->actingAs($admin);

        $this->get(route('admin.students.index', ['search' => '88887777']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('students.data', 1));
    }

    public function test_student_list_filters_account_status(): void
    {
        $admin = User::factory()->admin()->create();
        StudentProfile::factory()->create([
            'user_id' => User::factory()->student()->create(['is_active' => true])->id,
        ]);
        StudentProfile::factory()->create([
            'user_id' => User::factory()->student()->create(['is_active' => false])->id,
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.students.index', ['account_status' => 'inactive']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('students.data', 1));
    }
}
