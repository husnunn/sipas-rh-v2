# Task: Tambahkan Pengaturan Policy Absensi Harian oleh Admin di halaman Attendance Sites

Bantu saya menambahkan fitur **pengaturan policy absensi harian** yang bisa dikelola admin, dan tempatkan pengaturannya di halaman **Attendance Sites**.

## Latar belakang
Saat ini sistem sudah punya:
- Attendance Sites (lokasi, radius, Wi-Fi)
- Override Absensi Harian (untuk kejadian insidental seperti rapat guru, pulang lebih awal, dll.)

Tetapi saya masih **belum memiliki policy absensi harian utama** yang bisa diatur admin untuk aturan normal sehari-hari.

Saya ingin admin bisa mengatur aturan waktu absensi secara permanen, misalnya:
- batas awal absen masuk
- batas akhir absen masuk tepat waktu
- batas akhir absen masuk terlambat / batas penutupan check-in
- batas waktu mulai absen pulang
- batas waktu akhir absen pulang

## Tujuan
Tambahkan fitur policy absensi harian utama yang:
1. bisa dikelola admin dari web,
2. ditaruh di area/menu Attendance Sites,
3. dipakai backend sebagai rule normal harian,
4. tetap bisa dioverride oleh fitur Override Absensi Harian yang sudah ada.

---

# Konsep bisnis yang diinginkan

## Policy utama
Policy absensi harian normal harus bisa diatur admin.

Field minimal:
- `check_in_open_at`
- `check_in_on_time_until`
- `check_in_close_at`
- `check_out_open_at`
- `check_out_close_at`

Contoh:
- check-in buka: 06:00
- batas tepat waktu: 07:00
- check-in ditutup: 12:00
- check-out buka: 14:00
- check-out ditutup: 18:00

## Perilaku backend
### Check-in
- sebelum `check_in_open_at` → tolak, `CHECK_IN_NOT_OPEN_YET`
- antara `check_in_open_at` s/d `check_in_on_time_until` → status `present`
- setelah `check_in_on_time_until` s/d `check_in_close_at` → status `late`
- setelah `check_in_close_at` → tolak, `CHECK_IN_CLOSED`

### Check-out
- sebelum `check_out_open_at` → tolak, `CHECK_OUT_NOT_OPEN_YET`
- antara `check_out_open_at` s/d `check_out_close_at` → boleh check-out
- setelah `check_out_close_at` → tolak, `CHECK_OUT_CLOSED` atau sesuai kebijakan yang dipilih

## Catatan
- Policy ini adalah **aturan normal harian**
- Override harian tetap lebih tinggi prioritasnya
- Attendance Sites tetap untuk lokasi/radius/Wi-Fi
- Policy absensi bisa dihubungkan ke Attendance Site agar setiap site bisa punya rule sendiri

---

# Desain data yang diinginkan

## Opsi yang diutamakan
Tambahkan policy absensi langsung ke entitas **Attendance Site**, karena saya ingin menu pengaturannya berada di halaman Attendance Sites.

## Field yang perlu ditambahkan pada Attendance Site
Tambahkan field berikut:
- `check_in_open_at`
- `check_in_on_time_until`
- `check_in_close_at`
- `check_out_open_at`
- `check_out_close_at`

Opsional jika perlu:
- `timezone`
- `is_policy_active`
- `notes`

## Validasi
Pastikan urutan waktu valid:
- `check_in_open_at <= check_in_on_time_until <= check_in_close_at`
- `check_out_open_at <= check_out_close_at`

---

# Perubahan UI Admin yang diinginkan

## Halaman Attendance Sites
Tolong tambahkan pengaturan policy absensi harian pada:
- form create Attendance Site
- form edit Attendance Site
- halaman detail jika ada

## Field UI yang harus ada
Tambahkan section misalnya:
### Policy Absensi Harian
- Jam buka check-in
- Batas tepat waktu check-in
- Jam tutup check-in
- Jam buka check-out
- Jam tutup check-out

Tambahkan helper text yang jelas, misalnya:
- **Jam buka check-in**: siswa baru bisa absen masuk mulai jam ini
- **Batas tepat waktu**: lewat jam ini tetap boleh check-in tetapi dihitung terlambat
- **Jam tutup check-in**: setelah jam ini check-in ditolak
- **Jam buka check-out**: siswa baru bisa absen pulang mulai jam ini
- **Jam tutup check-out**: setelah jam ini check-out ditutup

## Penempatan
Saya ingin policy ini muncul langsung di halaman/menu Attendance Sites, bukan menu terpisah dahulu.

Kalau menurut struktur UI lebih baik:
- tampil sebagai section dalam form Attendance Site
- atau tab tambahan di halaman Attendance Site

pilih yang paling konsisten dengan project.

---

# Perubahan Backend yang diinginkan

## Saat validasi daily attendance
Backend harus:
1. ambil Attendance Site aktif / site yang dipilih
2. baca policy absensi dari site tersebut
3. pakai policy itu untuk check-in/check-out
4. jika ada override harian aktif, override boleh menimpa policy normal

## Prioritas evaluasi
1. cek academic calendar
2. cek attendance day override
3. cek manual status (izin/sakit/dispen)
4. cek Wi-Fi dan lokasi
5. cek jam berdasarkan policy Attendance Site
6. simpan daily attendance

---

# Perubahan API yang diinginkan

## Student daily attendance
Tidak perlu mengubah request utama terlalu banyak, tetapi backend harus memakai policy dari Attendance Site.

## Today endpoint
Akan lebih baik jika endpoint `daily-attendance/today` juga mengembalikan info policy yang sedang berlaku, misalnya:
```json
{
  "data": {
    "date": "2026-05-02",
    "status": "present",
    "label": "Hadir",
    "can_check_in": false,
    "can_check_out": true,
    "policy": {
      "check_in_open_at": "06:00",
      "check_in_on_time_until": "07:00",
      "check_in_close_at": "12:00",
      "check_out_open_at": "14:00",
      "check_out_close_at": "18:00"
    }
  }
}
Ini berguna agar Android bisa menampilkan informasi jam absensi dengan benar.


Yang harus dihasilkan agent

Tolong hasilkan:

analisis perubahan yang diperlukan,
migration untuk menambah field policy di Attendance Sites,
perubahan model dan relasi,
request validation,
perubahan form create/edit Attendance Site,
perubahan service absensi harian agar memakai policy dari Attendance Site,
prioritas rule antara policy normal dan override harian,
penyesuaian endpoint daily-attendance/today agar bisa mengirim info policy aktif,
daftar file yang perlu dibuat/diubah.


Catatan penting
jangan pindahkan rule utama ke Android
backend tetap penentu final
override harian tetap harus bisa menimpa policy normal
tampilkan policy di menu/halaman Attendance Sites