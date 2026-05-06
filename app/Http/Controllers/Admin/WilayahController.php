<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function provinces(): JsonResponse
    {
        $rows = Province::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows);
    }

    public function regencies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => ['required', 'string', 'size:2', 'exists:provinces,id'],
        ]);

        $rows = Regency::query()
            ->where('province_id', $validated['province_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows);
    }

    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'regency_id' => ['required', 'string', 'size:4', 'exists:regencies,id'],
        ]);

        $rows = District::query()
            ->where('regency_id', $validated['regency_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows);
    }

    public function villages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => ['required', 'string', 'size:7', 'exists:districts,id'],
        ]);

        $rows = Village::query()
            ->where('district_id', $validated['district_id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows);
    }

    /**
     * Untuk inisialisasi select berjenjang saat edit (dari {@see Village} tersimpan).
     */
    public function villageContext(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wilayah_village_id' => ['required', 'string', 'size:10', 'exists:villages,id'],
        ]);

        $village = Village::query()
            ->with(['district.regency.province'])
            ->findOrFail($validated['wilayah_village_id']);

        return response()->json([
            'wilayah_province_id' => $village->district->regency->province_id,
            'wilayah_regency_id' => $village->district->regency_id,
            'wilayah_district_id' => $village->district_id,
            'wilayah_village_id' => $village->id,
        ]);
    }
}
