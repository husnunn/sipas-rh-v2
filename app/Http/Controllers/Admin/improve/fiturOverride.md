# Spesifikasi Fitur Override Absensi Harian untuk Kejadian Insidental

## Tujuan
Menambahkan fitur **override absensi harian** agar admin dapat menangani kondisi insidental seperti:

- rapat guru mendadak
- siswa dipulangkan lebih awal
- pembelajaran ditiadakan
- jam pulang dimajukan
- check-out dibebaskan
- aturan absensi harian berubah hanya untuk tanggal tertentu

Fitur ini **tidak mengganti policy absensi utama**, tetapi **menimpa aturan absensi pada hari tertentu**.

---

# 1. Masalah yang Ingin Diselesaikan

Sekolah memiliki aturan absensi normal, misalnya:
- check-in buka 06:00
- hadir tepat waktu sampai 07:00
- check-in ditutup 12:00
- check-out buka 14:00
- check-out ditutup 18:00

Namun, kadang ada kondisi insidental seperti:
- rapat guru mendadak
- siswa diperbolehkan pulang lebih awal
- kegiatan sekolah khusus
- pembelajaran dibatalkan

Dalam kondisi seperti ini, **mengubah policy utama tidak aman**, karena:
- bisa lupa dikembalikan
- memengaruhi hari lain
- rawan salah aturan

Karena itu diperlukan **event override harian**.

---

# 2. Prinsip Solusi

## 2.1 Policy utama tetap ada
Tetap ada satu aturan absensi normal sekolah, misalnya:
- check_in_open_at
- check_in_on_time_until
- check_in_close_at
- check_out_open_at
- check_out_close_at

## 2.2 Event override menimpa policy hanya untuk tanggal tertentu
Jika ada event override aktif pada suatu tanggal, backend akan:
- memakai rule override
- atau menonaktifkan bagian tertentu dari rule utama
- atau membebaskan check-out
- atau menutup absensi sama sekali

## 2.3 Backend tetap menjadi penentu final
Android hanya membaca hasil final dari backend:
- apakah check-in boleh
- apakah check-out boleh
- apakah status libur / pulang cepat aktif
- apakah check-out diwajibkan atau dibebaskan

---

# 3. Contoh Kasus

## Kasus A — Hari normal
- check-in: 06:00–07:00 = hadir
- >07:00–12:00 = terlambat
- check-out: 14:00–18:00

## Kasus B — Rapat guru mendadak
- siswa dipulangkan lebih awal
- check-out dimajukan ke 10:00
- jadwal setelah jam 10:00 tidak berlaku
- siswa tetap boleh check-out dan tercatat sah

## Kasus C — Siswa dipulangkan dan check-out tidak wajib
- siswa yang sudah check-in tidak perlu absen pulang manual
- sistem menganggap hari itu aman tanpa check-out

## Kasus D — Event sekolah / libur mendadak
- semua absensi ditutup
- hari dianggap tidak membutuhkan kehadiran biasa

---

# 4. Terminologi

## Policy utama
Aturan normal yang berlaku setiap hari.

## Override event
Aturan insidental yang berlaku pada tanggal tertentu dan dapat:
- menimpa jam check-in
- menimpa jam check-out
- menutup absensi
- membebaskan check-out
- menutup jadwal pelajaran

---

# 5. Desain Database yang Disarankan

## Tabel: `attendance_day_overrides`

### Kolom utama
- `id`
- `name`
- `date`
- `event_type`
- `is_active`
- `attendance_site_id` nullable
- `override_attendance_policy` boolean
- `override_schedule` boolean
- `allow_check_in` boolean
- `allow_check_out` boolean
- `waive_check_out` boolean
- `dismiss_students_early` boolean
- `check_in_open_at` nullable
- `check_in_on_time_until` nullable
- `check_in_close_at` nullable
- `check_out_open_at` nullable
- `check_out_close_at` nullable
- `notes` nullable
- `created_by`
- `updated_by` nullable
- `created_at`
- `updated_at`

---

# 6. Arti Setiap Field

## `date`
Tanggal override berlaku.

## `event_type`
Nilai yang disarankan:
- `early_dismissal`
- `teacher_meeting`
- `special_event`
- `holiday_override`
- `attendance_closed`
- `custom`

## `override_attendance_policy`
Jika `true`, maka jam check-in/check-out pada hari itu mengikuti field override.

## `override_schedule`
Jika `true`, maka jadwal pelajaran pada hari itu dianggap ditimpa / dinonaktifkan / disesuaikan.

## `allow_check_in`
Menentukan apakah check-in masih diizinkan pada hari itu.

## `allow_check_out`
Menentukan apakah check-out masih diizinkan pada hari itu.

## `waive_check_out`
Jika `true`, siswa yang sudah check-in **tidak wajib** check-out manual.

## `dismiss_students_early`
Penanda bahwa siswa dipulangkan lebih awal.

## `check_in_open_at`, `check_in_on_time_until`, `check_in_close_at`
Dipakai hanya jika override policy aktif dan check-in tetap diizinkan.

## `check_out_open_at`, `check_out_close_at`
Dipakai untuk memajukan atau memundurkan jam pulang pada hari itu.

---

# 7. Contoh Migration Laravel

```php
Schema::create('attendance_day_overrides', function (Blueprint $table) {
    $table->id();
    $table->date('date');
    $table->string('name');
    $table->string('event_type')->default('custom');
    $table->boolean('is_active')->default(true);

    $table->foreignId('attendance_site_id')->nullable()->constrained()->nullOnDelete();

    $table->boolean('override_attendance_policy')->default(false);
    $table->boolean('override_schedule')->default(false);

    $table->boolean('allow_check_in')->default(true);
    $table->boolean('allow_check_out')->default(true);
    $table->boolean('waive_check_out')->default(false);
    $table->boolean('dismiss_students_early')->default(false);

    $table->time('check_in_open_at')->nullable();
    $table->time('check_in_on_time_until')->nullable();
    $table->time('check_in_close_at')->nullable();
    $table->time('check_out_open_at')->nullable();
    $table->time('check_out_close_at')->nullable();

    $table->text('notes')->nullable();

    $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

    $table->timestamps();

    $table->index(['date', 'is_active']);
});

8. Hubungan dengan Academic Calendar

Ada dua opsi implementasi:

Opsi A — tabel terpisah
Gunakan attendance_day_overrides terpisah dari kalender akademik.
Kelebihan
lebih jelas fokus ke absensi
lebih fleksibel
tidak mencampur event akademik umum dengan policy absensi

Opsi B — perluas academic_calendar_events
Tambahkan field override absensi ke tabel event akademik yang sudah ada.
Kelebihan
satu sumber event
Kekurangan
tabel jadi lebih kompleks
Rekomendasi

Untuk fleksibilitas dan kejelasan, gunakan tabel terpisah:
academic_calendar_events → event akademik umum
attendance_day_overrides → event yang mengubah absensi harian


9. Rule Backend
9.1 Urutan evaluasi harian

Saat backend mengevaluasi status absensi untuk satu siswa pada satu tanggal:
cek apakah ada academic_calendar_event yang menutup absensi
cek apakah ada attendance_day_override aktif di tanggal itu
cek apakah ada status manual (excused, sick, dispensation)
cek absensi fisik check-in/check-out
tentukan status akhir

9.2 Urutan validasi saat check-in
cari policy normal
cek apakah ada override aktif
kalau override aktif dan override_attendance_policy = true, pakai jam override
kalau allow_check_in = false, tolak check-in
validasi Wi-Fi
validasi lokasi
hitung status:
- sebelum buka → CHECK_IN_NOT_OPEN_YET
- antara buka s/d tepat waktu → present
- setelah tepat waktu s/d tutup → late
- setelah tutup → CHECK_IN_CLOSED

9.3 Urutan validasi saat check-out
cek override aktif
kalau waive_check_out = true, maka check-out manual tidak wajib
kalau allow_check_out = false, tombol/check-out ditutup
jika tetap diizinkan:
- sebelum check_out_open_at → CHECK_OUT_NOT_OPEN_YET
- antara check_out_open_at s/d check_out_close_at → boleh check-out
- setelah tutup → CHECK_OUT_CLOSED atau tetap diterima sesuai kebijakan


10. Aturan untuk Kasus “Rapat Guru Mendadak”
Solusi terbaik

Gunakan override dengan:
event_type = teacher_meeting
dismiss_students_early = true
override_schedule = true
override_attendance_policy = true

Opsi 1 — tetap wajib check-out
Contoh:
allow_check_in = true
allow_check_out = true
check_out_open_at = 10:00
check_out_close_at = 13:00
waive_check_out = false
Hasil:
siswa bisa pulang lebih awal
tetap melakukan check-out
audit lebih rapi

Opsi 2 — check-out dibebaskan

Contoh:
allow_check_in = true
allow_check_out = false
waive_check_out = true
Hasil:
siswa yang sudah check-in tidak wajib check-out manual
cocok untuk kondisi massal / darurat

Rekomendasi :
Gunakan Opsi 1 sebagai default, dan Opsi 2 untuk kondisi khusus.


11. Status Harian Final

Untuk satu siswa pada satu hari:
Jika ada override + waive check-out
jika siswa sudah check-in → tetap dianggap valid hadir / terlambat tanpa butuh check-out
Jika ada override + early dismissal
check-out mengikuti jam override
Jika tidak ada override
pakai policy normal


12. Endpoint Admin
Create override
POST /admin/attendance-day-overrides

Payload:
{
  "name": "Rapat Guru Mendadak",
  "date": "2026-05-02",
  "event_type": "teacher_meeting",
  "is_active": true,
  "attendance_site_id": 1,
  "override_attendance_policy": true,
  "override_schedule": true,
  "allow_check_in": true,
  "allow_check_out": true,
  "waive_check_out": false,
  "dismiss_students_early": true,
  "check_in_open_at": "06:00",
  "check_in_on_time_until": "07:00",
  "check_in_close_at": "12:00",
  "check_out_open_at": "10:00",
  "check_out_close_at": "13:00",
  "notes": "Siswa dipulangkan lebih awal karena rapat guru"
}

Update override
PUT /admin/attendance-day-overrides/{override}
Payload sama seperti create.

Toggle active
PATCH /admin/attendance-day-overrides/{override}/toggle-active
Cancel / deactivate
PATCH /admin/attendance-day-overrides/{override}/cancel


13. Validasi Backend
Request validation
name: required string
date: required date
event_type: required string
is_active: boolean
attendance_site_id: nullable exists
override_attendance_policy: boolean
override_schedule: boolean
allow_check_in: boolean
allow_check_out: boolean
waive_check_out: boolean
dismiss_students_early: boolean
check_in_open_at: nullable time
check_in_on_time_until: nullable time
check_in_close_at: nullable time
check_out_open_at: nullable time
check_out_close_at: nullable time

Rule tambahan
Jika override_attendance_policy = true, maka:
minimal field jam yang relevan harus valid
check_in_open_at <= check_in_on_time_until <= check_in_close_at
check_out_open_at <= check_out_close_at


14. Android Behavior

Android tidak perlu menghitung aturan override sendiri. Android cukup membaca status dari backend.
Endpoint yang disarankan
GET /api/v1/student/daily-attendance/today

Response harus bisa memuat:
status final harian
apakah check-in boleh
apakah check-out boleh
apakah hari ini ada override
apakah check-out dibebaskan
pesan informasi

Contoh:
{
  "data": {
    "date": "2026-05-02",
    "status": "present",
    "label": "Hadir",
    "check_in_at": "2026-05-02T06:45:00+07:00",
    "check_out_at": null,
    "late_minutes": 0,
    "can_check_in": false,
    "can_check_out": true,
    "override": {
      "active": true,
      "event_type": "teacher_meeting",
      "dismiss_students_early": true,
      "waive_check_out": false
    },
    "message": "Hari ini siswa dipulangkan lebih awal. Check-out dibuka pukul 10:00."
  }
}

Jika check-out dibebaskan:
{
  "data": {
    "date": "2026-05-02",
    "status": "present",
    "label": "Hadir",
    "check_in_at": "2026-05-02T06:45:00+07:00",
    "check_out_at": null,
    "late_minutes": 0,
    "can_check_in": false,
    "can_check_out": false,
    "override": {
      "active": true,
      "event_type": "teacher_meeting",
      "dismiss_students_early": true,
      "waive_check_out": true
    },
    "message": "Hari ini siswa dipulangkan lebih awal dan tidak wajib check-out manual."
  }
}


15. UI Android yang Diinginkan
Halaman attendance
Tambahkan informasi:
badge status harian
info override harian
info apakah check-out wajib atau tidak

Contoh pesan
"Hari ini siswa dipulangkan lebih awal."
"Check-out dibuka pukul 10:00."
"Hari ini check-out manual tidak diwajibkan."

Tombol
jika can_check_out = true → tombol pulang aktif
jika waive_check_out = true → tombol pulang nonaktif dan tampil info


16. Web/Admin yang Diinginkan
Halaman daftar override
Tampilkan:
nama event
tanggal
tipe event
aktif / nonaktif
apakah menimpa policy
apakah menimpa jadwal
apakah check-out dibebaskan

Form input

Field:
nama event
tanggal
tipe event
lokasi/site opsional
aktif / nonaktif
override attendance policy
override schedule
allow check-in
allow check-out
waive check-out
dismiss students early
jam-jam override
catatan


17. Integrasi dengan Rekap Kehadiran

Status final harian harus mempertimbangkan:
libur akademik
manual status (izin/sakit/dispen)
override harian
absensi fisik
Contoh
Kasus 1
siswa check-in 06:40
ada override early dismissal
check-out open jam 10:00
siswa check-out jam 10:15
hasil: valid

Kasus 2
siswa check-in 06:50
ada override + waive check-out
siswa tidak check-out
hasil: tetap valid hadir

Kasus 3
ada event attendance_closed
semua check-in/check-out ditolak
hasil akhir mengikuti event, bukan policy normal


18. Acceptance Criteria

Agent harus memastikan:
Admin bisa membuat override harian per tanggal.
Override dapat menimpa aturan absensi utama.
Override dapat menimpa jadwal pelajaran.
Admin bisa memajukan jam check-out.
Admin bisa membebaskan check-out manual.
Backend membaca override sebelum mengevaluasi policy normal.
Android cukup membaca hasil final dari backend.
UI siswa dapat menampilkan pesan event override.
Rekap harian tetap konsisten.
Policy utama tidak berubah permanen karena event insidental.


19. Catatan Penting untuk Agent
Jangan ubah policy utama untuk kasus insidental

Semua kasus mendadak harus ditangani melalui override harian, bukan mengedit aturan normal.
Fokus implementasi
attendance policy utama tetap ada
attendance day override untuk kasus insidental
manual status tetap terpisah
Android tetap ringan, backend yang memutuskan


20. Output yang Saya Inginkan dari Agent

Tolong hasilkan:
analisis desain fitur override absensi harian,
migration Laravel untuk attendance_day_overrides,
model dan relasi,
request validation,
service logic untuk apply override,
controller admin,
penyesuaian endpoint daily-attendance/today,
penyesuaian rule check-in/check-out,
penyesuaian rekap status harian,
daftar file yang perlu dibuat/diubah.