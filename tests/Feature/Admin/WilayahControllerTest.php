<?php

namespace Tests\Feature\Admin;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WilayahControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{province_id: string, regency_id: string, district_id: string, village_id: string}
     */
    private function seedMinimalWilayahHierarchy(): array
    {
        Province::query()->create([
            'id' => '99',
            'name' => 'Provinsi Test',
        ]);
        Regency::query()->create([
            'id' => '9901',
            'province_id' => '99',
            'name' => 'Kabupaten Test',
        ]);
        District::query()->create([
            'id' => '9901010',
            'regency_id' => '9901',
            'name' => 'Kecamatan Test',
        ]);
        Village::query()->create([
            'id' => '9901010001',
            'district_id' => '9901010',
            'name' => 'Desa Test',
        ]);

        return [
            'province_id' => '99',
            'regency_id' => '9901',
            'district_id' => '9901010',
            'village_id' => '9901010001',
        ];
    }

    public function test_guest_is_redirected_from_wilayah_endpoints(): void
    {
        $this->get(route('admin.wilayah.provinces'))
            ->assertRedirect();
    }

    public function test_admin_can_list_provinces_as_json(): void
    {
        $admin = User::factory()->admin()->create();
        Province::query()->create(['id' => '11', 'name' => 'Aceh']);

        $response = $this->actingAs($admin)->getJson(route('admin.wilayah.provinces'));

        $response->assertOk();
        $response->assertJsonFragment(['id' => '11', 'name' => 'Aceh']);
    }

    public function test_admin_can_list_child_regions_filtered(): void
    {
        $admin = User::factory()->admin()->create();
        $ids = $this->seedMinimalWilayahHierarchy();

        $this->actingAs($admin)->getJson(route('admin.wilayah.regencies', ['province_id' => $ids['province_id']]))
            ->assertOk()
            ->assertJsonFragment(['id' => '9901', 'name' => 'Kabupaten Test']);

        $this->actingAs($admin)->getJson(route('admin.wilayah.districts', ['regency_id' => $ids['regency_id']]))
            ->assertOk()
            ->assertJsonFragment(['id' => '9901010', 'name' => 'Kecamatan Test']);

        $this->actingAs($admin)->getJson(route('admin.wilayah.villages', ['district_id' => $ids['district_id']]))
            ->assertOk()
            ->assertJsonFragment(['id' => '9901010001', 'name' => 'Desa Test']);
    }

    public function test_regencies_validation_requires_existing_province(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson(route('admin.wilayah.regencies', ['province_id' => 'ZZ']))
            ->assertUnprocessable();
    }

    public function test_village_context_returns_parent_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $ids = $this->seedMinimalWilayahHierarchy();

        $this->actingAs($admin)->getJson(route('admin.wilayah.village-context', [
            'wilayah_village_id' => $ids['village_id'],
        ]))
            ->assertOk()
            ->assertExactJson([
                'wilayah_province_id' => $ids['province_id'],
                'wilayah_regency_id' => $ids['regency_id'],
                'wilayah_district_id' => $ids['district_id'],
                'wilayah_village_id' => $ids['village_id'],
            ]);
    }
}
