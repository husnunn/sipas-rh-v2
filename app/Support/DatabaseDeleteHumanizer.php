<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Throwable;

final class DatabaseDeleteHumanizer
{
    /**
     * @return array{type: 'error', message: string}
     */
    public static function flash(Throwable $e, string $fallbackMessage): array
    {
        if ($e instanceof QueryException && self::isIntegrityConstraintViolation($e)) {
            return [
                'type' => 'error',
                'message' => self::integrityMessage($e) ?? self::genericRelationMessage(),
            ];
        }

        report($e);

        return [
            'type' => 'error',
            'message' => $fallbackMessage,
        ];
    }

    public static function genericRelationMessage(): string
    {
        return 'Data tidak dapat dihapus karena masih terhubung dengan data lain. Hapus atau ubah data terkait terlebih dahulu.';
    }

    private static function isIntegrityConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? '';
        $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : null;

        if ($sqlState === '23000') {
            return true;
        }

        if (in_array($code, [19, 1451, 1452], true)) {
            return true;
        }

        $msg = strtolower($e->getMessage());

        if (str_contains($msg, 'foreign key constraint failed')
            || str_contains($msg, 'foreign key constraint fails')
            || str_contains($msg, 'integrity constraint violation')) {
            return true;
        }

        return false;
    }

    private static function integrityMessage(QueryException $e): ?string
    {
        $message = $e->getMessage();

        $childTable = null;
        if (preg_match('/foreign key constraint fails\s*\(\s*(?:`[^`]+`\.)?`(\w+)`/i', $message, $matches)) {
            $childTable = $matches[1];
        }

        $parentTable = null;
        if (preg_match('/REFERENCES\s*`(\w+)`/i', $message, $matches)) {
            $parentTable = $matches[1];
        }

        if ($parentTable !== null) {
            $specific = self::messageForParentAndChild($parentTable, $childTable);
            if ($specific !== null) {
                return $specific;
            }
        }

        if ($childTable !== null) {
            $label = self::childTableLabel($childTable);

            return $label !== null
                ? "Data tidak dapat dihapus karena masih dipakai di {$label}. Hapus data terkait terlebih dahulu."
                : self::genericRelationMessage();
        }

        return null;
    }

    private static function messageForParentAndChild(string $parentTable, ?string $childTable): ?string
    {
        return match ($parentTable) {
            'users' => match ($childTable) {
                'password_reset_audits' => 'Akun tidak dapat dihapus karena masih ada riwayat reset password. Hapus riwayat terkait terlebih dahulu atau jangan menghapus akun yang pernah dilakukan reset dari admin.',
                'attendance_manual_statuses' => 'Akun tidak dapat dihapus karena masih tercatat sebagai pembuat data absensi manual. Ubah atau hapus entri terkait terlebih dahulu.',
                default => 'Akun tidak dapat dihapus karena masih dipakai oleh data lain di sistem.',
            },
            'classes' => 'Kelas tidak dapat dihapus karena masih digunakan pada jadwal pelajaran atau penempatan siswa. Hapus jadwal dan relasi terkait terlebih dahulu.',
            'subjects' => 'Mata pelajaran tidak dapat dihapus karena masih digunakan pada jadwal. Hapus jadwal terkait terlebih dahulu.',
            'teacher_profiles' => 'Data guru tidak dapat dihapus karena masih terdaftar pada jadwal mengajar. Ubah atau hapus jadwal terkait terlebih dahulu.',
            'school_years' => 'Tahun ajaran tidak dapat dihapus karena masih digunakan pada kelas atau jadwal.',
            'student_profiles' => 'Data siswa tidak dapat dihapus karena masih terkait dengan data lain (misalnya absensi atau penempatan kelas).',
            'attendance_sites' => 'Lokasi absensi tidak dapat dihapus karena masih terkait dengan data absensi.',
            default => null,
        };
    }

    private static function childTableLabel(string $childTable): ?string
    {
        return match ($childTable) {
            'schedules' => 'jadwal pelajaran',
            'password_reset_audits' => 'riwayat reset password',
            'class_student' => 'penempatan siswa di kelas',
            'teacher_subjects' => 'relasi guru dan mata pelajaran',
            'attendance_records' => 'catatan absensi',
            'daily_attendances' => 'absensi harian',
            'attendance_manual_statuses' => 'status absensi manual',
            default => null,
        };
    }
}
