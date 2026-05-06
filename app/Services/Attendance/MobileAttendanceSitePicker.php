<?php

namespace App\Services\Attendance;

use App\Http\Resources\AttendanceSitePickerApiResource;
use App\Models\AttendanceSite;

/**
 * Daftar titik absensi untuk pemilih pada API mobile guru saja. Nanti filter/aturan
 * khusus guru bisa ditambahkan di sini tanpa menyentuh endpoint siswa.
 */
final class MobileAttendanceSitePicker
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forTeacher(): array
    {
        $sites = AttendanceSite::query()
            ->forApiPicker()
            ->get(['id', 'name', 'latitude', 'longitude', 'radius_m']);

        return AttendanceSitePickerApiResource::collection($sites)->resolve();
    }
}
