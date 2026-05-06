<?php

namespace App\Services\Wilayah;

use App\Models\Village;

final class WilayahAddressSnapshot
{
    /**
     * @return array{wilayah_village_id: string, province: string, city: string, district: string, village: string}|null
     */
    public static function fromVillageId(string $villageId): ?array
    {
        $village = Village::query()
            ->with(['district.regency.province'])
            ->find($villageId);

        if ($village === null || $village->district === null || $village->district->regency === null || $village->district->regency->province === null) {
            return null;
        }

        return [
            'wilayah_village_id' => $village->id,
            'province' => $village->district->regency->province->name,
            'city' => $village->district->regency->name,
            'district' => $village->district->name,
            'village' => $village->name,
        ];
    }
}
