# Task: Tambahkan Fitur Ubah Foto Profil dan Password untuk Role Student dan Teacher

Kamu bekerja pada backend existing untuk aplikasi Rohmah App.

## Tujuan

Tambahkan fitur edit profil terbatas untuk user dengan role:

- student
- teacher

User hanya boleh mengubah:

1. Foto profil
2. Password

Field lain seperti nama, email, NIS/NIP, kelas, sekolah, role, nomor HP, alamat, dan data identitas lainnya tidak boleh diubah dari aplikasi Android.

Jangan membuat ulang sistem auth.
Jangan membuat ulang struktur user dari nol.
Gunakan auth dan middleware existing.
Ikuti struktur controller, service, repository, route, request validation, dan response format yang sudah ada di project.

---

## Scope Backend

Buat atau sesuaikan endpoint mobile API untuk:

1. Mengambil data profil user login
2. Mengubah foto profil
3. Mengubah password

Endpoint yang disarankan:

```http
GET /api/mobile/profile
POST /api/mobile/profile/photo
POST /api/mobile/profile/password
Jika project sudah punya endpoint profil existing, gunakan endpoint existing dan sesuaikan tanpa duplikasi.

Authorization

Endpoint hanya boleh diakses oleh user yang sudah login.

Role yang boleh menggunakan fitur ini:
student
teacher

Jika role selain itu mencoba mengakses, return error authorization.

Contoh response:
{
  "success": false,
  "message": "Anda tidak memiliki akses untuk mengubah profil."
}
Gunakan middleware / policy / guard existing jika sudah ada.

1. Endpoint Get Profile

Endpoint:
GET /api/mobile/profile
Fungsi:

Mengambil data profil user login untuk ditampilkan di Android.

Response minimal:
{
  "success": true,
  "message": "Profile loaded successfully",
  "data": {
    "id": 1,
    "name": "Nama User",
    "email": "user@example.com",
    "role": "student",
    "profile_photo_url": "https://example.com/storage/profile/photo.jpg",
    "school": {
      "id": 1,
      "name": "Nama Sekolah"
    },
    "class": {
      "id": 10,
      "name": "X RPL 1"
    }
  }
}

Catatan:

Untuk teacher, field class boleh null.
Jangan expose password.
Jangan expose field sensitif yang tidak dibutuhkan Android.
Sesuaikan field dengan struktur database existing.

2. Endpoint Update Photo Profile

Endpoint:
POST /api/mobile/profile/photo
Request:
photo: file
Validasi:

photo wajib
tipe file hanya image
ekstensi yang diizinkan: jpg, jpeg, png, webp
maksimal ukuran file: 2MB atau sesuai standar project
user harus login
role harus student atau teacher

Contoh validasi Laravel:
'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']

Behavior:

Ambil user login.
Validasi role student / teacher.
Upload file ke storage.
Jika user sudah punya foto lama, hapus foto lama jika aman dilakukan.
Simpan path foto baru ke field profile photo existing.
Return response profile terbaru.

Response sukses:
{
  "success": true,
  "message": "Foto profil berhasil diperbarui.",
  "data": {
    "profile_photo_url": "https://example.com/storage/profile/photo.jpg"
  }
}

Response gagal validasi:
{
  "success": false,
  "message": "Foto profil tidak valid.",
  "errors": {
    "photo": [
      "The photo field is required."
    ]
  }
}
Catatan penting:

Jangan mengizinkan update nama, email, role, school_id, class_id, atau field lain dari endpoint ini.
Endpoint ini hanya update foto profil.
Gunakan disk storage existing.
Jika backend Laravel, pastikan file bisa diakses melalui storage:link atau mekanisme public URL existing.

3. Endpoint Update Password

Endpoint:
POST /api/mobile/profile/password
Request JSON:
{
  "current_password": "password_lama",
  "password": "password_baru",
  "password_confirmation": "password_baru"
}
Validasi:

current_password wajib
password wajib
password_confirmation wajib
password minimal 8 karakter
password confirmation harus sama
password lama harus benar

Contoh validasi:
'current_password' => ['required', 'string'],
'password' => ['required', 'string', 'min:8', 'confirmed']
Behavior:

Ambil user login.
Validasi role student / teacher.
Cek apakah current_password sesuai dengan password user saat ini.
Jika salah, return error.
Jika benar, hash password baru.
Simpan password baru.
Return response sukses.

Response sukses:
{
  "success": true,
  "message": "Password berhasil diperbarui."
}
Response jika password lama salah:
{
  "success": false,
  "message": "Password lama tidak sesuai.",
  "errors": {
    "current_password": [
      "Password lama tidak sesuai."
    ]
  }
}
Catatan penting:

Jangan return password.
Jangan menyimpan password plain text.
Gunakan hashing existing, misalnya Hash::make() jika Laravel.
Jangan logout user otomatis kecuali project memang memiliki policy tersebut.
Jangan mengubah token auth kecuali project sudah memiliki aturan refresh token setelah password berubah.

Struktur Implementasi yang Diharapkan

Ikuti pola project existing.

Jika project menggunakan Laravel, struktur yang disarankan:
routes/api.php atau routes/mobile.php
↓
ProfileController
↓
Form Request Validation
↓
Service / Repository jika project memakai layer tersebut
↓
Model User / Student / Teacher
↓
Storage

Contoh file yang mungkin dibuat/disesuaikan:
app/Http/Controllers/Api/Mobile/ProfileController.php
app/Http/Requests/Mobile/UpdateProfilePhotoRequest.php
app/Http/Requests/Mobile/UpdateProfilePasswordRequest.php
routes/api.php atau routes/mobile.php
Jika project sudah punya folder atau naming convention berbeda, ikuti yang sudah ada.

Security Requirement

Pastikan:

Hanya user login yang bisa akses.
Hanya role student dan teacher yang bisa update.
User hanya bisa mengubah data miliknya sendiri.
User tidak bisa mengirim field tambahan untuk mengubah nama/email/role.
File upload divalidasi dengan benar.
Password lama wajib diverifikasi.
Password baru wajib di-hash.
Response tidak membocorkan field sensitif.

Testing Manual Backend

Test sebagai student:

Login sebagai student.
Call GET /api/mobile/profile.
Upload foto ke POST /api/mobile/profile/photo.
Pastikan foto berubah.
Call POST /api/mobile/profile/password dengan password lama yang benar.
Pastikan password berubah.
Login ulang dengan password baru.

Test sebagai teacher:

Login sebagai teacher.
Lakukan test yang sama.

Test negative case:

Upload file PDF harus gagal.
Upload image lebih dari limit harus gagal.
Password lama salah harus gagal.
Password confirmation tidak sama harus gagal.
Role selain student/teacher harus gagal.
Request mencoba mengubah nama/email/role harus diabaikan atau ditolak.

Expected Result

Setelah implementasi backend selesai:

Student bisa mengubah foto profil.
Student bisa mengubah password.
Teacher bisa mengubah foto profil.
Teacher bisa mengubah password.
Field lain tidak bisa diedit dari Android.
Endpoint aman dan mengikuti auth existing.
Response konsisten dengan format API project.