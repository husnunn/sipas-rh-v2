# Blueprint Awal Proyek  
**Website Admin + Backend API Laravel 13**  
**Sekolah: Robithotul Hikmah Jombang**

Menurut saya, desain yang paling aman untuk kebutuhan ini adalah:

- **Website hanya untuk admin**
- **Guru dan siswa mengakses lewat API**
- **Route API guru dan siswa dipisah dari awal**
- **Validasi bentrok jadwal wajib dicek di backend, bukan hanya di form admin**

Laravel 13 sudah mendukung pola ini dengan baik melalui **routing + middleware**, **authorization**, **validation**, **migrations**, dan **Sanctum** untuk autentikasi token API/mobile. Untuk auth UI/admin, Anda juga bisa memakai starter kit/Fortify atau auth manual sesuai kebutuhan. ([laravel.com](https://laravel.com/docs/13.x/sanctum?utm_source=chatgpt.com))

---

## 1. Arah Arsitektur

### 1.1. Area sistem
#### A. Website Admin
Digunakan hanya oleh admin untuk:
- kelola data siswa
- kelola data guru
- kelola kelas
- kelola mata pelajaran
- kelola jadwal
- kelola akun login guru/siswa
- reset password akun

#### B. API Guru
Dipakai aplikasi guru untuk:
- login
- lihat profil
- lihat jadwal mengajar
- ke depan: CRUD tertentu dari aplikasi guru

#### C. API Siswa
Dipakai aplikasi siswa untuk:
- login
- lihat profil
- lihat jadwal kelas
- ke depan: fitur siswa lain

Untuk API mobile seperti ini, **Sanctum** cocok karena memang ditujukan untuk API token sederhana, termasuk request dengan `Authorization` header. ([laravel.com](https://laravel.com/docs/13.x/sanctum?utm_source=chatgpt.com))

---

## 2. Prinsip Utama Sistem

### 2.1. Satu guru tidak boleh bentrok jadwal
Wajib dicek saat:
- admin tambah jadwal
- admin edit jadwal

Aturan bentrok guru:
- `teacher_id` sama
- `day_of_week` sama
- waktu overlap

Rumus overlap:
```text
jadwal_baru_start < jadwal_lama_end
AND
jadwal_baru_end > jadwal_lama_start
```

Contoh:
- lama: `08:00 - 09:00`
- baru: `08:30 - 09:30` → **bentrok**
- baru: `09:00 - 10:00` → **tidak bentrok**

---

### 2.2. Satu kelas tidak boleh bentrok jadwal
Aturan yang sama, tetapi dicek berdasarkan:
- `class_id`
- `day_of_week`
- rentang waktu

---

### 2.3. API guru dan siswa dipisah
Ini penting supaya nanti:
- endpoint guru bisa berkembang tanpa mengganggu siswa
- permission lebih jelas
- dokumentasi API lebih rapi
- testing lebih gampang

Laravel memang dirancang untuk pemisahan akses seperti ini lewat **route groups**, **middleware**, dan **authorization**. ([laravel.com](https://laravel.com/docs/13.x/middleware?utm_source=chatgpt.com))

---

## 3. Struktur Data yang Saya Sarankan

### 3.1. Tabel inti

### `school_years`
Menyimpan tahun ajaran.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| name | string | contoh: `2026/2027` |
| is_active | boolean | tahun ajaran aktif |
| created_at | timestamp |  |
| updated_at | timestamp |  |

---

### `users`
Semua akun login masuk ke sini.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| name | string | nama akun |
| email | string nullable | bisa nullable jika login pakai username |
| username | string unique | username login |
| password | string | hash password |
| role | enum/string | `admin`, `teacher`, `student` |
| is_active | boolean | status akun |
| must_change_password | boolean | paksa ganti password setelah reset admin |
| last_login_at | timestamp nullable | opsional |
| created_at | timestamp |  |
| updated_at | timestamp |  |

Saya sarankan mulai dari satu tabel `users` dengan kolom `role` sederhana. Kalau nanti butuh permission lebih kompleks, baru dipisah lagi. Laravel sendiri mendukung auth, password reset, middleware, dan authorization untuk pola seperti ini. ([laravel.com](https://laravel.com/docs/13.x/passwords?utm_source=chatgpt.com))

---

### `teacher_profiles`
Profil guru.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| user_id | bigint | FK ke `users.id` |
| nip | string nullable | nomor induk guru |
| full_name | string | nama lengkap |
| gender | string nullable |  |
| phone | string nullable |  |
| address | text nullable |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

---

### `student_profiles`
Profil siswa.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| user_id | bigint | FK ke `users.id` |
| nis | string unique | nomor induk siswa |
| full_name | string | nama lengkap |
| gender | string nullable |  |
| phone | string nullable |  |
| address | text nullable |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

---

### `classes`
Daftar kelas.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| school_year_id | bigint | FK |
| name | string | contoh: `7A` |
| level | integer | contoh: `7`, `8`, `9` |
| homeroom_teacher_id | bigint nullable | FK ke guru |
| is_active | boolean |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

---

### `class_student`
Relasi siswa ke kelas aktif.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| class_id | bigint | FK |
| student_id | bigint | FK ke `student_profiles.id` |
| school_year_id | bigint | FK |
| is_active | boolean |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

> Kalau Anda ingin histori perpindahan kelas lebih rapi, nama tabel bisa diganti menjadi `student_class_histories`.

---

### `subjects`
Daftar mata pelajaran.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| code | string unique | contoh: `MTK` |
| name | string | contoh: `Matematika` |
| is_active | boolean |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

---

### `teacher_subjects`
Relasi guru dengan mapel yang dia ampu.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| teacher_id | bigint | FK |
| subject_id | bigint | FK |
| created_at | timestamp |  |
| updated_at | timestamp |  |

> Ini membantu agar saat admin memilih guru untuk jadwal, pilihan guru bisa difilter sesuai mapel.

---

### `schedules`
Tabel utama jadwal pelajaran.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| school_year_id | bigint | FK |
| class_id | bigint | FK |
| subject_id | bigint | FK |
| teacher_id | bigint | FK |
| semester | tinyInteger | `1` / `2` |
| day_of_week | tinyInteger | misal: `1=Senin` sampai `6=Sabtu` |
| start_time | time | jam mulai |
| end_time | time | jam selesai |
| room | string nullable | ruang kelas |
| notes | text nullable | catatan |
| is_active | boolean |  |
| created_at | timestamp |  |
| updated_at | timestamp |  |

Contoh data:
- kelas: `7A`
- mapel: `Matematika`
- guru: `Ust. Ahmad`
- hari: `Senin`
- jam: `08:00 - 09:00`

---

### `password_reset_audits`
Log reset password oleh admin.

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint | PK |
| user_id | bigint | user yang direset |
| reset_by_admin_id | bigint | admin pelaku |
| notes | text nullable | alasan/reset info |
| created_at | timestamp |  |
| updated_at | timestamp |  |

> **Catatan penting:**  
> Laravel punya fasilitas password reset bawaan, tetapi untuk kebutuhan **admin me-reset password user lain**, saya lebih menyarankan dibuatkan **fitur admin reset password khusus** + `must_change_password = true` saat user login pertama setelah reset. Laravel juga menyediakan sistem reset password bawaan jika nanti Anda ingin menambah alur “lupa password” mandiri. ([laravel.com](https://laravel.com/docs/13.x/passwords?utm_source=chatgpt.com))

---

## 4. Relasi Antar Entitas

```text
users
 ├── teacher_profiles
 └── student_profiles

school_years
 ├── classes
 ├── class_student
 └── schedules

subjects
 ├── teacher_subjects
 └── schedules

teacher_profiles
 ├── teacher_subjects
 ├── classes.homeroom_teacher_id
 └── schedules

student_profiles
 └── class_student

classes
 ├── class_student
 └── schedules
```

---

## 5. Urutan Migration Laravel

Laravel migrations memang didesain untuk dibuat dan dijalankan berurutan dari folder `database/migrations`. ([laravel.com](https://laravel.com/docs/13.x/migrations?utm_source=chatgpt.com))

Urutan yang saya sarankan:

```text
1. create_school_years_table
2. create_users_table
3. create_teacher_profiles_table
4. create_student_profiles_table
5. create_classes_table
6. create_class_student_table
7. create_subjects_table
8. create_teacher_subjects_table
9. create_schedules_table
10. create_password_reset_audits_table
```

---

## 6. Struktur Route

### 6.1. Web Admin
Semua route admin hanya bisa diakses:
- user login
- role = `admin`

Contoh struktur:

```php
// routes/web.php

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('students', StudentController::class);
        Route::resource('teachers', TeacherController::class);
        Route::resource('classes', ClassController::class);
        Route::resource('subjects', SubjectController::class);
        Route::resource('schedules', ScheduleController::class);

        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('/accounts/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');
    });
});
```

Laravel routing dan middleware memang mendukung pengelompokan route seperti ini. ([laravel.com](https://laravel.com/docs/13.x/middleware?utm_source=chatgpt.com))

---

### 6.2. API Student
Contoh prefix khusus siswa:

```php
// routes/api.php

Route::prefix('v1/student')->name('api.student.')->group(function () {
    Route::post('/login', [StudentAuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
        Route::get('/me', [StudentProfileController::class, 'me'])->name('me');
        Route::get('/schedule', [StudentScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/change-password', [StudentPasswordController::class, 'change'])->name('password.change');
    });
});
```

---

### 6.3. API Teacher
Contoh prefix khusus guru:

```php
// routes/api.php

Route::prefix('v1/teacher')->name('api.teacher.')->group(function () {
    Route::post('/login', [TeacherAuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum', 'role:teacher'])->group(function () {
        Route::get('/me', [TeacherProfileController::class, 'me'])->name('me');
        Route::get('/schedule', [TeacherScheduleController::class, 'index'])->name('schedule.index');

        // cadangan untuk fitur berikutnya
        Route::get('/classes', [TeacherClassController::class, 'index'])->name('classes.index');
        Route::get('/students', [TeacherStudentController::class, 'index'])->name('students.index');

        // nanti bisa dikembangkan
        Route::post('/attendance', [TeacherAttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/grades', [TeacherGradeController::class, 'store'])->name('grades.store');
    });
});
```

**Keuntungan pemisahan ini:**
- siswa tidak akan “melihat” endpoint guru
- guru bisa dikembangkan ke CRUD/fitur baru tanpa mencampur logic siswa
- middleware dan policy lebih mudah diatur

Laravel menyediakan **authorization** lewat gates/policies untuk model-level action, dan **middleware** untuk memfilter akses request sebelum masuk ke controller. ([laravel.com](https://laravel.com/docs/13.x/middleware?utm_source=chatgpt.com))

---

## 7. Struktur Controller yang Saya Sarankan

```text
app/Http/Controllers/
├── Admin/
│   ├── Auth/
│   │   └── AdminAuthController.php
│   ├── DashboardController.php
│   ├── StudentController.php
│   ├── TeacherController.php
│   ├── ClassController.php
│   ├── SubjectController.php
│   ├── ScheduleController.php
│   └── AccountController.php
└── Api/
    ├── Student/
    │   ├── StudentAuthController.php
    │   ├── StudentProfileController.php
    │   ├── StudentScheduleController.php
    │   └── StudentPasswordController.php
    └── Teacher/
        ├── TeacherAuthController.php
        ├── TeacherProfileController.php
        ├── TeacherScheduleController.php
        ├── TeacherClassController.php
        ├── TeacherStudentController.php
        ├── TeacherAttendanceController.php
        └── TeacherGradeController.php
```

---

## 8. Form Request yang Perlu Dibuat

Laravel validation bisa dipusatkan dalam request validation dan custom rules. Laravel juga menyediakan `Rules` directory lewat `make:rule` jika Anda ingin memisahkan logika validasi kompleks. ([laravel.com](https://laravel.com/docs/13.x/validation?utm_source=chatgpt.com))

Saya sarankan minimal:

```text
app/Http/Requests/Admin/
├── StoreStudentRequest.php
├── UpdateStudentRequest.php
├── StoreTeacherRequest.php
├── UpdateTeacherRequest.php
├── StoreClassRequest.php
├── UpdateClassRequest.php
├── StoreSubjectRequest.php
├── UpdateSubjectRequest.php
├── StoreScheduleRequest.php
└── UpdateScheduleRequest.php

app/Rules/
├── NoTeacherScheduleConflict.php
└── NoClassScheduleConflict.php
```

---

## 9. Aturan Validasi Jadwal

### 9.1. Validasi dasar
```text
- class_id wajib ada
- subject_id wajib ada
- teacher_id wajib ada
- school_year_id wajib ada
- semester wajib ada
- day_of_week wajib ada
- start_time wajib ada
- end_time wajib ada
- end_time harus lebih besar dari start_time
```

---

### 9.2. Validasi bentrok guru
Pseudo query:

```php
Schedule::query()
    ->where('teacher_id', $teacherId)
    ->where('day_of_week', $dayOfWeek)
    ->where('school_year_id', $schoolYearId)
    ->where('semester', $semester)
    ->where('id', '!=', $ignoreId) // saat update
    ->where(function ($query) use ($startTime, $endTime) {
        $query->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
    })
    ->exists();
```

Kalau `exists() == true`, berarti **bentrok**.

---

### 9.3. Validasi bentrok kelas
Pseudo query:

```php
Schedule::query()
    ->where('class_id', $classId)
    ->where('day_of_week', $dayOfWeek)
    ->where('school_year_id', $schoolYearId)
    ->where('semester', $semester)
    ->where('id', '!=', $ignoreId) // saat update
    ->where(function ($query) use ($startTime, $endTime) {
        $query->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
    })
    ->exists();
```

---

### 9.4. Tempat validasi dilakukan
Saya sarankan validasi ada di **dua tempat**:

#### A. Frontend admin
Untuk warning cepat.

#### B. Backend Laravel
Untuk final protection.

**Backend adalah sumber kebenaran utama.**

---

## 10. Middleware Role

Untuk tahap awal, cukup buat middleware role sederhana:

```text
role:admin
role:teacher
role:student
```

Logika dasarnya:
- ambil user login
- cek `role`
- kalau tidak sesuai, tolak request

Laravel middleware memang dibuat untuk memfilter request seperti ini sebelum controller dijalankan. ([laravel.com](https://laravel.com/docs/13.x/middleware?utm_source=chatgpt.com))

---

## 11. Resource / JSON Response API

Untuk API mobile, saya sarankan jangan return model mentah.  
Pakai **API Resource** agar format response rapi dan konsisten.

Laravel menyediakan **Eloquent API Resources** untuk mentransform model dan collection menjadi JSON yang lebih terkontrol. Laravel 13 juga sudah punya dukungan JSON:API resource first-party bila nanti Anda ingin response yang lebih formal. ([laravel.com](https://laravel.com/docs/13.x/eloquent-resources?utm_source=chatgpt.com))

Contoh struktur:

```text
app/Http/Resources/
├── StudentProfileResource.php
├── TeacherProfileResource.php
├── StudentScheduleResource.php
└── TeacherScheduleResource.php
```

Contoh response jadwal:

```json
{
  "data": [
    {
      "id": 1,
      "day": "Senin",
      "start_time": "08:00",
      "end_time": "09:00",
      "subject": "Matematika",
      "teacher": "Ust. Ahmad",
      "class": "7A"
    }
  ]
}
```

---

## 12. Alur Reset Password Oleh Admin

Saya sarankan alurnya seperti ini:

1. Admin buka halaman akun
2. Admin klik **Reset Password**
3. Sistem generate password sementara atau admin input password baru
4. Password di-hash
5. Set `must_change_password = true`
6. Simpan log ke `password_reset_audits`

Saat guru/siswa login:
- kalau `must_change_password = true`
- paksa user ganti password sebelum lanjut

> Ini lebih cocok untuk skenario sekolah daripada alur “forgot password” publik.

---

## 13. Fitur Tahap 1 yang Sebaiknya Dibangun Dulu

### Wajib
- login admin
- CRUD siswa
- CRUD guru
- CRUD kelas
- CRUD mata pelajaran
- CRUD jadwal
- akun guru/siswa
- reset password admin
- validasi bentrok guru
- validasi bentrok kelas

### Menyusul
- import Excel siswa/guru
- filter guru per mapel saat buat jadwal
- notifikasi akun baru
- audit log admin
- absensi guru
- nilai
- tugas dari guru

---

## 14. Kesimpulan Final

Menurut saya, bentuk sistem yang paling sehat untuk proyek ini adalah:

### Website Admin
- hanya admin
- kelola seluruh master data
- reset password akun guru/siswa
- susun jadwal

### API Teacher
- route dipisah
- middleware role dipisah
- siap dikembangkan untuk CRUD guru di aplikasi

### API Student
- route dipisah
- read-only untuk kebutuhan siswa di tahap awal

### Jadwal
- tabel `schedules` sebagai pusat relasi:
  - `class_id`
  - `subject_id`
  - `teacher_id`
  - `day_of_week`
  - `start_time`
  - `end_time`

### Aturan wajib
- guru tidak boleh bentrok
- kelas tidak boleh bentrok
- validasi dilakukan di backend

---

## 15. Saran Teknis Singkat

Kalau ingin cepat dan stabil:

```text
- Laravel 13
- Blade untuk admin panel tahap awal
- Sanctum untuk API guru/siswa
- Form Request untuk validasi
- API Resource untuk response JSON
- Middleware role sederhana
```

Semua bagian ini sesuai dengan kemampuan resmi Laravel 13 untuk auth, Sanctum, routing, middleware, validation, migrations, authorization, password reset, dan API resources. ([laravel.com](https://laravel.com/docs/13.x/sanctum?utm_source=chatgpt.com))
