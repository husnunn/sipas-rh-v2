<?php

namespace Tests\Feature\Admin;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\StudentParent;
use App\Models\StudentProfile;
use App\Models\StudentProfileExtension;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentParentsAndExtensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_student_parent_and_extension_data_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $student = StudentProfile::factory()->create();
        $user = $student->user;

        $response = $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'name' => 'Nama Siswa Baru',
            'username' => $user->username,
            'email' => $user->email,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'gender' => $student->gender ?? 'male',
            'birth_date' => $student->birth_date?->format('Y-m-d'),
            'birth_place' => $student->birth_place,
            'phone' => $student->phone,
            'address' => $student->address,
            'parent_name' => $student->parent_name,
            'parent_phone' => $student->parent_phone,
            'street_address' => 'Jl. Melati No. 10',
            'rt' => '001',
            'rw' => '002',
            'village' => 'Sukamaju',
            'district' => 'Cicendo',
            'city' => 'Kota Bandung',
            'province' => 'Jawa Barat',
            'postal_code' => '40121',
            'religion' => 'Islam',
            'blood_type' => 'O',
            'mother_full_name' => 'Ibu Siti',
            'mother_occupation' => 'ASN',
            'mother_monthly_income_band' => 'm3_to_8m',
            'mother_nik' => str_repeat('5', 16),
            'mother_birth_date' => '1988-03-15',
            'father_full_name' => 'Bapa Badu',
            'father_occupation' => 'Wiraswasta',
            'father_monthly_income_band' => 'gte_30m',
            'father_nik' => str_repeat('7', 16),
            'father_birth_date' => '1985-11-20',
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('student_profile_extensions', [
            'student_profile_id' => $student->id,
            'street_address' => 'Jl. Melati No. 10',
            'province' => 'Jawa Barat',
        ]);

        $this->assertDatabaseHas('student_parents', [
            'student_profile_id' => $student->id,
            'relation' => 'mother',
            'full_name' => 'Ibu Siti',
            'monthly_income_band' => 'm3_to_8m',
            'nik' => str_repeat('5', 16),
        ]);

        $this->assertDatabaseHas('student_parents', [
            'student_profile_id' => $student->id,
            'relation' => 'father',
            'full_name' => 'Bapa Badu',
            'occupation' => 'Wiraswasta',
            'monthly_income_band' => 'gte_30m',
        ]);

        $rec = StudentParent::query()->where('student_profile_id', $student->id)->first();
        self::assertNotNull($rec);
    }

    public function test_student_update_creates_extension_row_if_missing(): void
    {
        $admin = User::factory()->admin()->create();
        $student = StudentProfile::factory()->create();
        $user = $student->user;

        self::assertNull($student->extension);

        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'gender' => 'male',
            'city' => 'Bandung',
        ])->assertRedirect(route('admin.students.index'));

        $student->refresh();
        self::assertInstanceOf(StudentProfileExtension::class, $student->extension);
        self::assertSame('Bandung', $student->extension->city);
    }

    public function test_admin_update_removes_parent_rows_when_all_parent_fields_are_empty(): void
    {
        $admin = User::factory()->admin()->create();
        $student = StudentProfile::factory()->create();
        $user = $student->user;

        StudentParent::query()->create([
            'student_profile_id' => $student->id,
            'relation' => 'mother',
            'full_name' => 'Sebelumnya Terisi',
            'occupation' => 'ASN',
            'monthly_income_band' => 'lt_3m',
            'nik' => str_repeat('1', 16),
            'birth_date' => '1990-01-01',
        ]);

        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'gender' => 'male',
            'mother_full_name' => '',
            'mother_occupation' => '',
            'mother_monthly_income_band' => '',
            'mother_nik' => '',
            'mother_birth_date' => '',
            'father_full_name' => '',
            'father_occupation' => '',
            'father_monthly_income_band' => '',
            'father_nik' => '',
            'father_birth_date' => '',
        ])->assertRedirect(route('admin.students.index'));

        self::assertSame(
            0,
            StudentParent::query()->where('student_profile_id', $student->id)->count(),
        );
    }

    public function test_admin_can_save_wilayah_village_id_and_sync_address_snapshot(): void
    {
        Province::query()->create(['id' => '77', 'name' => 'ProvinsiSnap']);
        Regency::query()->create(['id' => '7701', 'province_id' => '77', 'name' => 'KabSnap']);
        District::query()->create(['id' => '7701010', 'regency_id' => '7701', 'name' => 'KecSnap']);
        Village::query()->create(['id' => '7701010001', 'district_id' => '7701010', 'name' => 'DesaSnap']);

        $admin = User::factory()->admin()->create();
        $student = StudentProfile::factory()->create();
        $user = $student->user;

        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'gender' => 'male',
            'wilayah_village_id' => '7701010001',
        ])->assertRedirect(route('admin.students.index'));

        $student->refresh();

        self::assertSame('7701010001', $student->extension?->wilayah_village_id);
        self::assertSame('ProvinsiSnap', $student->extension?->province);
        self::assertSame('KabSnap', $student->extension?->city);
        self::assertSame('KecSnap', $student->extension?->district);
        self::assertSame('DesaSnap', $student->extension?->village);
    }
}
