<?php

namespace Tests\Feature\Admin;

use App\Models\ClassRoom;
use App\Models\SchoolYear;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_import_form(): void
    {
        $response = $this->get(route('admin.students.import'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_import_students_from_excel(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolYear = SchoolYear::factory()->create();
        ClassRoom::factory()->create([
            'school_year_id' => $schoolYear->id,
            'name' => 'X IPA 1',
            'level' => 10,
            'is_active' => true,
        ]);

        $path = $this->makeSampleSpreadsheetPath();

        $this->actingAs($admin);

        $response = $this->post(route('admin.students.import.store'), [
            'school_year_id' => $schoolYear->id,
            'file' => new UploadedFile($path, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);

        unlink($path);

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('flash.type', 'success');

        $this->assertSame(1, StudentProfile::query()->count());
        $this->assertDatabaseHas('student_profiles', [
            'nis' => '9876543210',
            'nisn' => '9876543210',
        ]);
        $this->assertDatabaseHas('users', [
            'username' => '9876543210',
        ]);

        $student = StudentProfile::query()->first();
        $this->assertNotNull($student);
        $this->assertTrue($student->classes()->wherePivot('is_active', true)->exists());
    }

    public function test_import_validates_missing_file(): void
    {
        $admin = User::factory()->admin()->create();
        $schoolYear = SchoolYear::factory()->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.students.import.store'), [
            'school_year_id' => $schoolYear->id,
        ]);

        $response->assertSessionHasErrors('file');
    }

    /**
     * Minimal workbook matching importer header rules (row 1 = header).
     */
    private function makeSampleSpreadsheetPath(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['No', 'Nama', 'JK', 'Tempat Lahir', 'Tanggal Lahir', 'kelas', 'NISN', 'NIK'],
            [1, 'Budi Import Test', 'L', 'Jakarta', ExcelDate::PHPToExcel(new \DateTimeImmutable('2010-05-15')), 'X IPA 1', '9876543210', '3201010101010001'],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($path === false) {
            self::fail('Could not create temp file.');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path.'.xlsx');
        unlink($path);

        return $path.'.xlsx';
    }
}
