<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSiteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_attendance_site_with_wifi_rules(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post(route('admin.attendance-sites.store'), [
            'name' => 'SMA Negeri 1',
            'latitude' => -6.2,
            'longitude' => 106.81,
            'radius_m' => 150,
            'is_active' => true,
            'wifi_rules' => [
                [
                    'ssid' => 'SCHOOL-WIFI',
                    'bssid' => 'AA:BB:CC:11:22:33',
                    'ip_subnet' => '192.168.1.0/24',
                    'is_active' => true,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.attendance-sites.index'));
        $site = AttendanceSite::query()->first();
        $this->assertNotNull($site);
        $this->assertEquals('SMA Negeri 1', $site->name);
        $this->assertCount(1, $site->wifiRules);
    }

    public function test_admin_can_store_attendance_policy_times_from_browser_his_format(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post(route('admin.attendance-sites.store'), [
            'name' => 'Gerbang',
            'latitude' => -6.2,
            'longitude' => 106.81,
            'radius_m' => 100,
            'is_active' => true,
            'check_in_open_at' => '06:00:00',
            'check_in_on_time_until' => '07:15:00',
            'check_in_close_at' => '09:30:59',
            'check_out_open_at' => '14:00:00',
            'check_out_close_at' => '17:45:00',
            'wifi_rules' => [],
        ]);

        $response->assertRedirect(route('admin.attendance-sites.index'));
        $site = AttendanceSite::query()->first();
        $this->assertNotNull($site);
        $this->assertTrue(str_starts_with((string) $site->check_in_open_at, '06:00'));
        $this->assertTrue(str_starts_with((string) $site->check_in_on_time_until, '07:15'));
    }
}
