<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IndonesiaAdministrativeRegionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_region_tables(): void
    {
        self::assertTrue(Schema::hasTable('provinces'));
        self::assertTrue(Schema::hasTable('regencies'));
        self::assertTrue(Schema::hasTable('districts'));
        self::assertTrue(Schema::hasTable('villages'));
    }

    public function test_region_models_persist_hierarchy_with_string_keys(): void
    {
        Province::query()->create([
            'id' => '32',
            'name' => 'JAWA BARAT',
        ]);

        Regency::query()->create([
            'id' => '3273',
            'province_id' => '32',
            'name' => 'KOTA BANDUNG',
        ]);

        District::query()->create([
            'id' => '3273010',
            'regency_id' => '3273',
            'name' => 'CICENDO',
        ]);

        Village::query()->create([
            'id' => '3273010001',
            'district_id' => '3273010',
            'name' => 'KELURAHAN CONTOH',
        ]);

        $village = Village::query()
            ->with(['district.regency.province'])
            ->whereKey('3273010001')
            ->first();

        self::assertNotNull($village);
        self::assertSame('CICENDO', $village->district?->name);
        self::assertSame('KOTA BANDUNG', $village->district?->regency?->name);
        self::assertSame('JAWA BARAT', $village->district?->regency?->province?->name);
    }
}
