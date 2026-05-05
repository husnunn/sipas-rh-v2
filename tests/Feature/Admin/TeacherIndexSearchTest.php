<?php

namespace Tests\Feature\Admin;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TeacherIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_teachers_index(): void
    {
        $this->get(route('admin.teachers.index'))->assertRedirect(route('login'));
    }

    public function test_teacher_index_can_search_by_distinct_nip(): void
    {
        $admin = User::factory()->admin()->create();
        TeacherProfile::factory()->create(['nip' => '198765543210987654321']);
        TeacherProfile::factory()->count(2)->create();

        $this->actingAs($admin);

        $this->get(route('admin.teachers.index', ['search' => '198765543210']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Teachers/Index')
                ->has('teachers.data', 1)
                ->where('teachers.data.0.nip', '198765543210987654321')
            );
    }

    public function test_teacher_index_can_search_by_linked_user_display_name(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->teacher()->create([
            'name' => 'Guru Nama Khusus Cari Ini',
            'username' => 'uniqusr12345',
        ]);
        TeacherProfile::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'LN Lain Tidak Cocok',
        ]);
        TeacherProfile::factory()->count(2)->create();

        $this->actingAs($admin);

        $this->get(route('admin.teachers.index', ['search' => 'Khusus Cari']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('teachers.data', 1)
                ->where('teachers.data.0.full_name', 'LN Lain Tidak Cocok')
            );
    }

    public function test_teacher_index_can_filter_account_inactive_only(): void
    {
        $admin = User::factory()->admin()->create();
        TeacherProfile::factory()->create([
            'user_id' => User::factory()->teacher()->create(['is_active' => false])->id,
        ]);
        TeacherProfile::factory()->create([
            'user_id' => User::factory()->teacher()->create(['is_active' => true])->id,
        ]);

        $this->actingAs($admin);

        $this->get(route('admin.teachers.index', ['account_status' => 'inactive']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('teachers.data', 1));
    }
}
