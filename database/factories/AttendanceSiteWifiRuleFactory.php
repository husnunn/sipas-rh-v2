<?php

namespace Database\Factories;

use App\Models\AttendanceSite;
use App\Models\AttendanceSiteWifiRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSiteWifiRule>
 */
class AttendanceSiteWifiRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_site_id' => AttendanceSite::factory(),
            'ssid' => 'SCHOOL-WIFI',
            'bssid' => strtoupper(fake()->numerify('AA:BB:CC:##:##:##')),
            'ip_subnet' => '192.168.1.0/24',
            'is_active' => true,
        ];
    }
}
