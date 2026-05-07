<?php

namespace Tests\Feature\Admin;

use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SchoolYearActiveSwitchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_set_active_school_year_and_it_deactivates_others(): void
    {
        $admin = User::factory()->admin()->create();

        $y1 = SchoolYear::factory()->create(['name' => '2024/2025', 'is_active' => true]);
        $y2 = SchoolYear::factory()->create(['name' => '2025/2026', 'is_active' => false]);

        $this->actingAs($admin)
            ->patch(route('admin.school-years.set-active', $y2))
            ->assertRedirect(route('admin.school-years.index'));

        $this->assertDatabaseHas('school_years', ['id' => $y1->id, 'is_active' => false]);
        $this->assertDatabaseHas('school_years', ['id' => $y2->id, 'is_active' => true]);
    }

    #[Test]
    public function admin_can_deactivate_currently_active_school_year(): void
    {
        $admin = User::factory()->admin()->create();
        $active = SchoolYear::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.school-years.set-active', $active))
            ->assertRedirect(route('admin.school-years.index'));

        $this->assertDatabaseHas('school_years', ['id' => $active->id, 'is_active' => false]);
    }
}
