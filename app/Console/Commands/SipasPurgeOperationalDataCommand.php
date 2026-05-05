<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus data operasional (riwayat & antrian), mempertahankan master akademik dan konfigurasi absensi.
 */
class SipasPurgeOperationalDataCommand extends Command
{
    protected $signature = 'sipas:purge-operational-data
                            {--force : Jalankan tanpa konfirmasi}
                            {--with-cache : Kosongkan tabel cache & cache_locks (jika ada)}';

    protected $description = 'Hapus riwayat absensi, kalender akademik, audit/token — tetap siswa, guru, kelas, jadwal, mapel, akun, lokasi & aturan absensi';

    /**
     * Log transaksi dan data turunan lain yang boleh dikosongkan.
     *
     * @var list<string>
     */
    private const OPERATIONAL_TABLES = [
        'attendance_validation_logs',
        'attendance_records',
        'daily_attendances',
        'attendance_manual_statuses',
        'academic_calendar_events',
        'password_reset_audits',
        'personal_access_tokens',
        'password_reset_tokens',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('Preserved data: siswa & guru (profil), users/akun, kelas/jadwal/mapel (+ tahun ajaran & pivot), ');
        $this->line('lokasi absensi (attendance_sites), aturan WiFi lokasi & override hari (wifi_rules / day_overrides).');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('Hapus data operasional (riwayat absensi, dll.) sesuai daftar di atas?', false)) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        $truncated = [];

        Schema::withoutForeignKeyConstraints(function () use (&$truncated): void {
            foreach (self::OPERATIONAL_TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();
                $truncated[] = $table;
            }

            if ($this->option('with-cache')) {
                foreach (['cache', 'cache_locks'] as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    DB::table($table)->truncate();
                    $truncated[] = $table;
                }
            }
        });

        if ($truncated === []) {
            $this->warn('Tidak ada tabel yang cocok dikosongkan.');

            return self::SUCCESS;
        }

        $this->info('Berhasil mengosongkan '.count($truncated).' tabel:');
        foreach ($truncated as $name) {
            $this->line(' - '.$name);
        }

        return self::SUCCESS;
    }
}
