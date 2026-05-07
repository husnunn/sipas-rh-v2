# Task: Buat Cron Job / Scheduler Laravel 13 untuk Notifikasi Android Berdasarkan Jadwal Pelajaran

Kamu bekerja pada backend Laravel 13 untuk aplikasi sekolah/Rohmah App.

## Tujuan

Saya ingin membuat fitur notifikasi otomatis ke Android berdasarkan jadwal pelajaran.

Notifikasi dikirim untuk:

1. Guru yang akan mulai mengajar.
2. Guru yang akan selesai mengajar.
3. Guru ketika waktu mengajarnya sudah habis.
4. Siswa sebelum jam pelajaran pertama dimulai.

Notifikasi harus dikirim ke Android melalui sistem push notification existing, misalnya Firebase Cloud Messaging / FCM, atau service notification yang sudah ada di project.

Jangan membuat ulang sistem auth.
Jangan membuat ulang sistem jadwal dari nol.
Jangan membuat ulang sistem FCM jika sudah ada.
Gunakan struktur project existing: model, service, repository, job, command, scheduler, dan notification sender yang sudah tersedia.

---

## Scope Utama

Buat cron job Laravel Scheduler yang berjalan setiap menit.

Scheduler harus mengecek jadwal pelajaran hari ini dan mengirim notifikasi sesuai kondisi waktu.

Timezone utama:

```text
Asia/Jakarta
```

Jika project sudah punya timezone config sendiri, gunakan config existing. Jangan hardcode berlebihan.

---

## Trigger Notifikasi yang Dibutuhkan

### 1. Notifikasi H-15 Menit Guru Akan Mengajar

Kirim notifikasi ke guru 15 menit sebelum jadwal mengajar dimulai.

Contoh:

```text
Jadwal guru:
Senin, PJOK, 07:00 - 09:15

Notifikasi dikirim:
06:45
```

Target:

```text
Hanya role teacher / guru
```

Contoh title:

```text
Jadwal Mengajar Akan Dimulai
```

Contoh body:

```text
Anda akan mengajar PJOK pukul 07:00 - 09:15.
```

Payload minimal:

```json
{
  "type": "teacher_schedule_start_reminder",
  "schedule_id": "123",
  "subject_name": "PJOK",
  "start_time": "07:00",
  "end_time": "09:15"
}
```

---

### 2. Notifikasi H-15 Menit Guru Akan Selesai Mengajar

Kirim notifikasi ke guru 15 menit sebelum jadwal mengajar selesai.

Contoh:

```text
Jadwal guru:
Senin, PJOK, 07:00 - 09:15

Notifikasi dikirim:
09:00
```

Target:

```text
Hanya role teacher / guru
```

Contoh title:

```text
Waktu Mengajar Hampir Selesai
```

Contoh body:

```text
Mata pelajaran PJOK akan selesai pukul 09:15.
```

Payload minimal:

```json
{
  "type": "teacher_schedule_end_reminder",
  "schedule_id": "123",
  "subject_name": "PJOK",
  "start_time": "07:00",
  "end_time": "09:15"
}
```

---

### 3. Notifikasi Guru Ketika Waktu Mengajar Sudah Habis

Kirim notifikasi ke guru tepat ketika waktu mengajar selesai.

Contoh:

```text
Jadwal guru:
Senin, PJOK, 07:00 - 09:15

Notifikasi dikirim:
09:15
```

Target:

```text
Hanya role teacher / guru
```

Contoh title:

```text
Waktu Mengajar Selesai
```

Contoh body:

```text
Waktu mengajar PJOK telah selesai.
```

Payload minimal:

```json
{
  "type": "teacher_schedule_ended",
  "schedule_id": "123",
  "subject_name": "PJOK",
  "start_time": "07:00",
  "end_time": "09:15"
}
```

---

### 4. Notifikasi H-15 Menit Siswa Sebelum Jam Pertama

Kirim notifikasi ke siswa 15 menit sebelum kegiatan belajar mengajar jam pertama dimulai.

Contoh:

```text
Jadwal kelas:
Jam pertama dimulai 07:00

Notifikasi dikirim:
06:45
```

Target:

```text
Hanya role student / siswa
```

Notifikasi ini hanya dikirim sekali per siswa per hari, berdasarkan jadwal pertama kelas siswa tersebut.

Contoh title:

```text
Kegiatan Belajar Akan Dimulai
```

Contoh body:

```text
Kegiatan belajar hari ini dimulai pukul 07:00. Persiapkan diri Anda.
```

Payload minimal:

```json
{
  "type": "student_first_schedule_reminder",
  "class_id": "10",
  "first_schedule_id": "123",
  "start_time": "07:00"
}
```

---

## Prinsip Penting

### Jangan Kirim Notifikasi Berulang

Cron berjalan setiap menit, jadi harus ada mekanisme idempotency agar notifikasi tidak terkirim berkali-kali.

Wajib buat mekanisme tracking/log.

Contoh table:

```text
schedule_notification_logs
```

Minimal kolom:

```text
id
notification_key
event_type
target_role
recipient_id
schedule_id nullable
class_id nullable
scheduled_at
sent_at
status
error_message nullable
created_at
updated_at
```

`notification_key` harus unique.

Contoh notification key:

```text
teacher_schedule_start_reminder:{schedule_id}:{teacher_id}:{date}
teacher_schedule_end_reminder:{schedule_id}:{teacher_id}:{date}
teacher_schedule_ended:{schedule_id}:{teacher_id}:{date}
student_first_schedule_reminder:{class_id}:{student_id}:{date}
```

Tambahkan unique index pada:

```text
notification_key
```

Dengan ini, meskipun cron berjalan berkali-kali, notifikasi yang sama tidak terkirim ulang.

---

## Toleransi Waktu

Karena scheduler berjalan tiap menit, gunakan window waktu agar tidak miss.

Contoh:

```text
now = 06:45
target_time = 06:45
```

Boleh cek berdasarkan format tanggal dan menit:

```text
target_datetime between now startOfMinute and now endOfMinute
```

Atau gunakan window aman:

```text
target_datetime >= now - 30 seconds
target_datetime <= now + 30 seconds
```

Jangan menggunakan perbandingan detik yang terlalu kaku sehingga cron bisa melewatkan notifikasi.

---

## Data Jadwal

Gunakan table jadwal existing di project.

Cari model/table yang mewakili:

```text
schedule
teacher schedule
class schedule
subject
teacher
student
class
school
```

Jangan membuat table jadwal baru jika sudah ada.

Asumsi data yang dibutuhkan dari jadwal:

```text
id
day_of_week
date jika jadwal berbasis tanggal
teacher_id
class_id
subject_id
start_time
end_time
status aktif/tidak
```

Jika jadwal berbasis hari, cocokkan dengan hari ini.

Jika jadwal berbasis tanggal, cocokkan dengan tanggal hari ini.

Pastikan hanya mengambil jadwal aktif.

---

## Target Recipient

### Untuk Guru

Ambil teacher dari jadwal masing-masing.

Pastikan user target memiliki role:

```text
teacher
```

Kirim hanya ke device token milik user guru tersebut.

Jangan kirim ke siswa/admin.

---

### Untuk Siswa

Ambil siswa berdasarkan class_id dari jadwal jam pertama.

Kirim hanya ke user dengan role:

```text
student
```

Kirim hanya ke siswa yang berada di kelas tersebut.

Jangan kirim ke guru/admin.

---

## Device Token

Gunakan device token Android existing jika project sudah punya table seperti:

```text
device_tokens
user_devices
firebase_tokens
personal_access_tokens dengan device info
```

Jangan membuat sistem device token baru jika sudah ada.

Jika belum ada, buat struktur minimal:

```text
user_device_tokens
- id
- user_id
- token
- platform
- is_active
- last_used_at
- created_at
- updated_at
```

Tapi prioritas utama adalah memakai struktur existing.

Hanya kirim ke:

```text
platform = android
is_active = true
token tidak null
```

---

## Struktur Implementasi yang Diharapkan

Ikuti struktur Laravel project existing.

Buat komponen seperti:

```text
app/Console/Commands/SendScheduleReminderNotificationsCommand.php
app/Services/ScheduleNotificationService.php
app/Jobs/SendPushNotificationJob.php jika project memakai queue
app/Models/ScheduleNotificationLog.php
database/migrations/xxxx_xx_xx_create_schedule_notification_logs_table.php
```

Jika project sudah punya service/job notification existing, gunakan service/job tersebut.

Jangan duplikasi FCM sender jika sudah ada.

---

## Laravel Scheduler

Daftarkan command agar berjalan setiap menit.

Gunakan struktur Laravel 13 existing.

Jika project memakai `routes/console.php`, tambahkan schedule di sana.

Contoh:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('notifications:send-schedule-reminders')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
```

Jika project memakai struktur lain, ikuti best practice Laravel 13 di project tersebut.

Command signature:

```php
protected $signature = 'notifications:send-schedule-reminders';
```

Deskripsi:

```php
protected $description = 'Send schedule reminder notifications for teachers and students.';
```

---

## Queue

Jika project sudah memakai queue, dispatch pengiriman push notification ke job.

Contoh flow:

```text
Command scheduler
↓
ScheduleNotificationService mencari kandidat notifikasi
↓
Buat log / reserve notification_key
↓
Dispatch SendPushNotificationJob
↓
Job kirim FCM
↓
Update log status sent / failed
```

Jika belum memakai queue, boleh kirim langsung dari command, tapi struktur service tetap harus rapi.

Untuk production, lebih disarankan queue.

---

## Status Log

Gunakan status:

```text
pending
sent
failed
skipped
```

Flow aman:

```text
1. Cek notification_key apakah sudah ada.
2. Jika sudah ada, skip.
3. Jika belum ada, create log status pending.
4. Kirim notifikasi.
5. Jika sukses, update status sent dan sent_at.
6. Jika gagal, update status failed dan error_message.
```

Untuk menghindari race condition, gunakan unique index `notification_key`.

Jika insert gagal karena duplicate key, skip.

---

## Logic Detail Guru

### Reminder Mulai Mengajar

Cari jadwal guru hari ini yang:

```text
start_time - 15 menit == now minute
```

Kirim ke guru pemilik jadwal.

Event type:

```text
teacher_schedule_start_reminder
```

---

### Reminder Akan Selesai Mengajar

Cari jadwal guru hari ini yang:

```text
end_time - 15 menit == now minute
```

Kirim ke guru pemilik jadwal.

Event type:

```text
teacher_schedule_end_reminder
```

---

### Waktu Mengajar Selesai

Cari jadwal guru hari ini yang:

```text
end_time == now minute
```

Kirim ke guru pemilik jadwal.

Event type:

```text
teacher_schedule_ended
```

---

## Logic Detail Siswa

Cari jadwal pertama setiap kelas hari ini.

Definisi jadwal pertama:

```text
jadwal aktif paling awal berdasarkan start_time untuk class_id pada hari ini
```

Kirim notifikasi ke semua siswa di kelas tersebut saat:

```text
first_schedule.start_time - 15 menit == now minute
```

Event type:

```text
student_first_schedule_reminder
```

Notifikasi ini hanya sekali per siswa per hari.

Jangan mengirim notifikasi ke siswa untuk setiap mata pelajaran. Hanya untuk jam pertama saja.

---

## Contoh Skenario

### Jadwal Guru

```text
Senin
PJOK 07:00 - 09:15
PAI 09:15 - 10:00
PAI 10:30 - 12:00
Pend. Pancasila 12:00 - 13:30
```

Untuk guru PJOK:

```text
06:45 → notifikasi akan mengajar PJOK
09:00 → notifikasi PJOK hampir selesai
09:15 → notifikasi PJOK selesai
```

Untuk guru PAI jadwal 09:15 - 10:00:

```text
09:00 → notifikasi akan mengajar PAI
09:45 → notifikasi PAI hampir selesai
10:00 → notifikasi PAI selesai
```

Untuk guru PAI jadwal 10:30 - 12:00:

```text
10:15 → notifikasi akan mengajar PAI
11:45 → notifikasi PAI hampir selesai
12:00 → notifikasi PAI selesai
```

---

### Jadwal Siswa

Jika jadwal pertama kelas dimulai 07:00:

```text
06:45 → siswa kelas tersebut menerima notifikasi kegiatan belajar akan dimulai
```

Siswa tidak menerima notifikasi 15 menit sebelum setiap pelajaran, hanya sebelum jam pertama.

---

## Response / Payload Android

Kirim payload data agar Android bisa membedakan tipe notifikasi.

Minimal payload:

```json
{
  "type": "teacher_schedule_start_reminder",
  "title": "Jadwal Mengajar Akan Dimulai",
  "body": "Anda akan mengajar PJOK pukul 07:00 - 09:15.",
  "schedule_id": "123",
  "subject_name": "PJOK",
  "start_time": "07:00",
  "end_time": "09:15"
}
```

Untuk siswa:

```json
{
  "type": "student_first_schedule_reminder",
  "title": "Kegiatan Belajar Akan Dimulai",
  "body": "Kegiatan belajar hari ini dimulai pukul 07:00. Persiapkan diri Anda.",
  "class_id": "10",
  "first_schedule_id": "123",
  "start_time": "07:00"
}
```

Pastikan payload bisa diterima Android foreground/background.

---

## Error Handling

Handle kondisi berikut:

1. Jadwal tidak ada.
2. Guru tidak punya user account.
3. Siswa tidak punya user account.
4. User tidak punya device token.
5. Token FCM invalid.
6. FCM gagal.
7. Data schedule tidak lengkap.
8. Duplicate notification key.

Jika token invalid, tandai token tidak aktif jika project sudah mendukung.

---

## Performance Requirement

Karena cron berjalan setiap menit:

1. Query harus efisien.
2. Hindari N+1 query.
3. Gunakan eager loading untuk teacher, subject, class, students, user, device tokens.
4. Gunakan index pada kolom jadwal yang sering dicari:
   - day_of_week atau date
   - start_time
   - end_time
   - teacher_id
   - class_id
   - status
5. Gunakan chunking jika jumlah siswa besar.

---

## Migration Schedule Notification Logs

Buat migration untuk log notifikasi.

Contoh struktur:

```php
Schema::create('schedule_notification_logs', function (Blueprint $table) {
    $table->id();
    $table->string('notification_key')->unique();
    $table->string('event_type');
    $table->string('target_role');
    $table->unsignedBigInteger('recipient_id');
    $table->unsignedBigInteger('schedule_id')->nullable();
    $table->unsignedBigInteger('class_id')->nullable();
    $table->dateTime('scheduled_at')->nullable();
    $table->dateTime('sent_at')->nullable();
    $table->string('status')->default('pending');
    $table->text('error_message')->nullable();
    $table->timestamps();

    $table->index(['event_type', 'scheduled_at']);
    $table->index(['target_role', 'recipient_id']);
    $table->index(['schedule_id']);
    $table->index(['class_id']);
});
```

Sesuaikan foreign key dengan table existing jika aman dilakukan.

---

## Testing Manual

Tambahkan cara test manual.

### Test Guru Akan Mengajar

1. Buat jadwal guru mulai 15 menit dari waktu sekarang.
2. Jalankan command:

```bash
php artisan notifications:send-schedule-reminders
```

3. Pastikan notifikasi terkirim ke guru.
4. Jalankan command lagi di menit yang sama.
5. Pastikan notifikasi tidak terkirim dua kali.

---

### Test Guru Hampir Selesai

1. Buat jadwal guru dengan end_time 15 menit dari waktu sekarang.
2. Jalankan command.
3. Pastikan notifikasi hampir selesai terkirim ke guru.

---

### Test Guru Selesai

1. Buat jadwal guru dengan end_time sama dengan waktu sekarang.
2. Jalankan command.
3. Pastikan notifikasi selesai terkirim ke guru.

---

### Test Siswa Jam Pertama

1. Buat jadwal pertama kelas mulai 15 menit dari waktu sekarang.
2. Pastikan siswa di kelas tersebut punya device token.
3. Jalankan command.
4. Pastikan notifikasi terkirim ke semua siswa di kelas tersebut.
5. Pastikan siswa tidak menerima notifikasi untuk jam kedua dan seterusnya.

---

## Testing Negative Case

Test kondisi:

1. User tidak punya token.
2. Token invalid.
3. Jadwal nonaktif.
4. Role selain teacher/student.
5. Command dijalankan dua kali dalam menit yang sama.
6. Ada dua schedule di hari yang sama.
7. Siswa pindah kelas.
8. Server timezone berbeda.

---

## Expected Result

Setelah implementasi selesai:

1. Scheduler berjalan setiap menit.
2. Guru mendapat notifikasi H-15 sebelum mulai mengajar.
3. Guru mendapat notifikasi H-15 sebelum selesai mengajar.
4. Guru mendapat notifikasi saat waktu mengajar selesai.
5. Siswa mendapat notifikasi H-15 sebelum jam pertama dimulai.
6. Notifikasi hanya dikirim ke role yang sesuai.
7. Tidak ada notifikasi duplicate.
8. Semua pengiriman tercatat di `schedule_notification_logs`.
9. Query efisien dan tidak N+1.
10. Build/test backend berhasil.

---

## Output yang Harus Dijelaskan Setelah Implementasi

Setelah selesai, jelaskan:

1. File apa saja yang dibuat/diubah.
2. Command artisan yang dibuat.
3. Scheduler didaftarkan di mana.
4. Migration/table log yang dibuat.
5. Bagaimana menentukan jadwal guru.
6. Bagaimana menentukan jadwal pertama siswa.
7. Bagaimana mencegah duplicate notification.
8. Bagaimana cara test manual.
9. TODO lanjutan jika ada.
