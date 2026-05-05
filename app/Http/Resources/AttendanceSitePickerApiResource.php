<?php

namespace App\Http\Resources;

use App\Models\AttendanceSite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Titik absensi aktif untuk pemilih di aplikasi mobile siswa/guru (id + geo ringkas).
 *
 * @mixin AttendanceSite
 */
class AttendanceSitePickerApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_m' => $this->radius_m,
        ];
    }
}
