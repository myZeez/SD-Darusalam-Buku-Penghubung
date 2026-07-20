# Sistem Buku Penghubung Digital SD Darusalam

MVP Laravel 12 untuk komunikasi harian sekolah dan orang tua siswa.

## Stack

- Laravel 12
- Filament v4 admin panel
- Spatie Laravel Permission
- DomPDF
- Laravel Excel
- MySQL

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open `http://127.0.0.1:8000/admin`.

## Data Demo Lengkap

Untuk mengisi 50 siswa beserta guru, kelas, orang tua, presensi, laporan aktivitas,
diskusi, agenda, notifikasi, jadwal akademik, dan ekstrakurikuler:

```bash
php artisan db:seed --class=DemoDataSeeder
```

Seeder ini aman dijalankan ulang karena memperbarui data demo yang sama tanpa
menggandakan relasi utama.

## Seed Accounts

All seeded users use password `password`.

- Administrator Sekolah: `admin@sddarusalam.test`
- Kepala Sekolah: `kepala.sekolah@sddarusalam.test`
- Guru: `guru@sddarusalam.test`
- Orang Tua: `ortu@sddarusalam.test`
- Siswa: `siswa@sddarusalam.test`
- Petugas Loket: `loket@sddarusalam.test`

Akun tambahan dari seeder demo memakai pola berikut dan seluruhnya menggunakan
kata sandi `password`:

- Guru: `guru2@sddarusalam.test` sampai `guru5@sddarusalam.test`
- Orang tua: `ortu02@sddarusalam.test` sampai `ortu50@sddarusalam.test`
- Siswa: `siswa02@sddarusalam.test` sampai `siswa50@sddarusalam.test`

## Hak Akses

- Admin / Kepala Sekolah: role `admin` dengan akses pengelolaan seluruh data sekolah. Pengajuan izin/sakit hanya dipantau; keputusan tetap dilakukan guru kelas.
- Guru: melihat siswa/kelas yang diajar, mengelola aktivitas sekolah, melihat aktivitas rumah, mengelola komentar, jadwal/notifikasi kelas, presensi kelas, serta meninjau pengajuan orang tua.
- Orang Tua: melihat data anak, ringkasan presensi, aktivitas rumah, utas diskusi, jadwal beserta konfirmasi kehadiran, notifikasi topbar, pengajuan izin/sakit, serta ringkasan ekstrakurikuler anak.
- Siswa: hanya melihat dashboard dengan status presensi hari ini, profil sendiri, serta ekstrakurikuler yang diikuti.
- Petugas Loket: hanya mencari siswa dan mencatat kedatangan di gerbang.

## MVP Modules

- User, teacher, parent, student, and class management
- Academic years and semesters
- Subjects and teacher assignments per class
- Lesson periods and weekly schedules with conflict detection
- Daily school activities entered by teachers
- Daily home activities entered by parents
- Threaded activity discussions with replies
- Separate weekly lesson schedules and additional activity agendas
- Parent attendance responses for activity agendas
- User notifications in the profile topbar
- Student arrivals and class attendance
- Parent leave/sick submissions
- Extracurricular enrollment, attendance, documentation, materials, and scores
- Student Excel import/export
- Filtered admin/principal activity report page at `/admin/laporan-aktivitas`
- PDF activity summary at `/reports/activity-summary`
- Public student registration with new or existing parent account linking

## Student Excel Import Columns

Use a heading row with these keys:

```text
class_id,parent_id,nis,name,gender,birth_date,status
```

`gender` accepts `male` or `female`; `status` defaults to `active`.
