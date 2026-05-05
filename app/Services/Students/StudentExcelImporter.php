<?php

namespace App\Services\Students;

use App\Models\ClassRoom;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\Students\StudentImportResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class StudentExcelImporter
{
    /**
     * Recognized header labels (normalized) → internal field keys.
     * Matches common exports such as database/DATASISWA.xlsx (Nama, NISN, kelas, …).
     *
     * @var array<string, string>
     */
    private const HEADER_TO_FIELD = [
        'no' => '_skip',
        'no.' => '_skip',
        'nomor' => '_skip',
        'nama' => 'name',
        'nama siswa' => 'name',
        'nama lengkap' => 'name',
        'nis' => 'nis',
        'nisn' => 'nisn',
        'jk' => 'gender',
        'jenis kelamin' => 'gender',
        'l/p' => 'gender',
        'tempat lahir' => 'birth_place',
        'ttl' => 'birth_place',
        'tanggal lahir' => 'birth_date',
        'tgl lahir' => 'birth_date',
        'tgl. lahir' => 'birth_date',
        'kelas' => 'class_name',
        'rombel' => 'class_name',
        'nama kelas' => 'class_name',
        'nik' => '_nik',
    ];

    public function import(string $absolutePath, int $schoolYearId): StudentImportResult
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $title = $worksheet->getTitle();
            $header = $this->detectHeaderRow($worksheet);

            if ($header === null) {
                continue;
            }

            [$headerRow, $columnMap] = $header;
            $row = $headerRow + 1;
            $maxRow = $worksheet->getHighestDataRow();

            while ($row <= $maxRow) {
                $currentRow = $row;
                $row++;

                try {
                    $record = $this->readRow($worksheet, $currentRow, $columnMap);

                    if ($this->rowIsEmpty($record)) {
                        continue;
                    }

                    $this->importOneRow($record, $schoolYearId);
                    $created++;
                } catch (Throwable $e) {
                    $errors[] = [
                        'sheet' => $title,
                        'row' => $currentRow,
                        'message' => $e->getMessage(),
                    ];
                    $skipped++;
                }
            }
        }

        return new StudentImportResult(
            created: $created,
            skipped: $skipped,
            errors: $errors,
        );
    }

    /**
     * @return null|array{0: int, 1: array<string, string>}
     */
    private function detectHeaderRow(Worksheet $worksheet): ?array
    {
        $highestRow = min($worksheet->getHighestDataRow(), 40);

        for ($r = 1; $r <= $highestRow; $r++) {
            $map = $this->mapHeaderColumns($worksheet, $r);
            if (isset($map['name']) && (isset($map['nis']) || isset($map['nisn']))) {
                return [$r, $map];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function mapHeaderColumns(Worksheet $worksheet, int $headerRow): array
    {
        $highestColumn = $worksheet->getHighestColumn($headerRow);
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $map = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $label = $this->cellToNormalizedHeader($worksheet, $col, $headerRow);
            if ($label === '') {
                continue;
            }

            if (! isset(self::HEADER_TO_FIELD[$label])) {
                continue;
            }

            $field = self::HEADER_TO_FIELD[$label];
            $letter = Coordinate::stringFromColumnIndex($col);
            $map[$field] = $letter;
        }

        return $map;
    }

    private function cellToNormalizedHeader(Worksheet $worksheet, int $col, int $row): string
    {
        $cell = $worksheet->getCell([$col, $row]);
        $raw = trim((string) $this->stringifyCell($cell));

        return $this->normalizeHeaderKey($raw);
    }

    private function normalizeHeaderKey(string $value): string
    {
        $v = Str::lower(trim(preg_replace('/\s+/u', ' ', $value) ?? ''));

        return $v;
    }

    /**
     * @param  array<string, string>  $columnMap
     * @return array<string, string>
     */
    private function readRow(Worksheet $worksheet, int $row, array $columnMap): array
    {
        $out = [];

        foreach ($columnMap as $field => $columnLetter) {
            $cell = $worksheet->getCell($columnLetter.$row);

            if ($field === 'birth_date') {
                $out[$field] = $this->readBirthDate($cell);

                continue;
            }

            if ($field === '_skip' || $field === '_nik') {
                continue;
            }

            $out[$field] = $this->stringifyCell($cell);
        }

        return $out;
    }

    private function readBirthDate(Cell $cell): string
    {
        $value = $cell->getValue();

        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
            $dt = ExcelDate::excelToDateTimeObject((float) $value);

            return $dt->format('Y-m-d');
        }

        $str = trim((string) $this->stringifyCell($cell));
        if ($str === '') {
            return '';
        }

        try {
            $parsed = Carbon::parse($str);

            return $parsed->format('Y-m-d');
        } catch (Throwable) {
            return '';
        }
    }

    private function stringifyCell(Cell $cell): string
    {
        $value = $cell->getCalculatedValue();

        if ($value === null) {
            return '';
        }

        if (is_numeric($value) && ! is_string($value)) {
            $float = (float) $value;
            if (floor($float) === $float && $float < 1e12) {
                return (string) (int) $float;
            }

            return rtrim(rtrim(sprintf('%.15F', $float), '0'), '.');
        }

        return trim((string) $value);
    }

    /**
     * @param  array<string, string>  $record
     */
    private function rowIsEmpty(array $record): bool
    {
        $name = trim($record['name'] ?? '');

        return $name === '';
    }

    /**
     * @param  array<string, string>  $record
     */
    private function importOneRow(array $record, int $schoolYearId): void
    {
        $name = Str::limit(trim($record['name'] ?? ''), 150, '');

        $nisRaw = $this->digitsOrString(trim($record['nis'] ?? ''));
        $nisnRaw = $this->digitsOrString(trim($record['nisn'] ?? ''));

        if ($nisRaw === '' && $nisnRaw === '') {
            throw new \InvalidArgumentException('NIS atau NISN wajib diisi.');
        }

        $nis = $nisRaw !== '' ? $nisRaw : $nisnRaw;
        $nisn = $nisnRaw !== '' ? $nisnRaw : null;

        if (StudentProfile::query()->where('nis', $nis)->exists()) {
            throw new \InvalidArgumentException("NIS {$nis} sudah terdaftar.");
        }

        if ($nisn !== null && $nisn !== '' && StudentProfile::query()->where('nisn', $nisn)->exists()) {
            throw new \InvalidArgumentException("NISN {$nisn} sudah terdaftar.");
        }

        $username = $this->makeUsername($nis, $name);

        $gender = $this->normalizeGender($record['gender'] ?? '');

        $birthDate = null;
        $bd = trim($record['birth_date'] ?? '');
        if ($bd !== '') {
            $birthDate = $bd;
        }

        $classId = $this->resolveClassId(trim($record['class_name'] ?? ''), $schoolYearId);

        DB::transaction(function () use ($name, $username, $nis, $nisn, $gender, $birthDate, $record, $schoolYearId, $classId): void {
            $user = User::create([
                'name' => $name,
                'username' => $username,
                'email' => null,
                'password' => Hash::make($nis),
                'roles' => ['student'],
            ]);

            $student = StudentProfile::create([
                'user_id' => $user->id,
                'nis' => $nis,
                'nisn' => $nisn,
                'full_name' => $name,
                'gender' => $gender,
                'birth_date' => ($birthDate !== null && $birthDate !== '') ? $birthDate : null,
                'birth_place' => $this->emptyToNull(Str::limit(trim($record['birth_place'] ?? ''), 100, '')),
                'phone' => null,
                'address' => null,
                'parent_name' => null,
                'parent_phone' => null,
            ]);

            if ($classId !== null) {
                $student->classes()->attach($classId, [
                    'school_year_id' => $schoolYearId,
                    'is_active' => true,
                ]);
            }
        });
    }

    private function digitsOrString(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return $value;
    }

    private function makeUsername(string $nis, string $name): string
    {
        $base = preg_replace('/[^a-zA-Z0-9._-]/', '', $nis) ?? '';
        $base = trim($base);
        if ($base === '') {
            $base = Str::slug(Str::limit($name, 40, ''), '');
        }
        $base = Str::limit($base, 50, '');

        $candidate = $base;
        $i = 1;
        while (User::query()->where('username', $candidate)->exists()) {
            $suffix = '_'.$i;
            $candidate = Str::limit($base, 50 - strlen($suffix), '').$suffix;
            $i++;
        }

        return $candidate;
    }

    private function normalizeGender(string $raw): ?string
    {
        $g = Str::lower(trim($raw));

        if (in_array($g, ['l', 'laki-laki', 'laki laki', 'male', 'm', '1'], true)) {
            return 'male';
        }

        if (in_array($g, ['p', 'perempuan', 'female', 'f', '2'], true)) {
            return 'female';
        }

        return null;
    }

    private function emptyToNull(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function resolveClassId(string $className, int $schoolYearId): ?int
    {
        if ($className === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', trim($className)) ?? '';

        $match = ClassRoom::query()
            ->active()
            ->where('school_year_id', $schoolYearId)
            ->get()
            ->first(function (ClassRoom $classRoom) use ($normalized): bool {
                return strcasecmp((string) $classRoom->name, $normalized) === 0;
            });

        return $match?->id;
    }
}
