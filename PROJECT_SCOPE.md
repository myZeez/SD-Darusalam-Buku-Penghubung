# Pegangan Project SD Darusalam

Dokumen ini menjadi acuan utama agar pengerjaan Sistem Buku Penghubung Digital SD Darusalam tidak melebar ke fitur yang belum diperlukan. Jika ada ide fitur baru, cek dokumen ini dulu sebelum dikerjakan.

Rincian pembagian tugas, alur kerja, dan hak akses lima role berada di `PROJECT_ROLE_SCOPE.md`. Jika terdapat perbedaan aturan role, `PROJECT_ROLE_SCOPE.md` menjadi acuan yang lebih baru.

## 1. Nama Project

Sistem Buku Penghubung Digital SD Darusalam

## 2. Tujuan Utama

Membangun aplikasi web untuk membantu SD Darusalam mengelola komunikasi harian antara sekolah, guru, orang tua, dan siswa melalui sistem digital yang rapi, aman, dan mudah digunakan.

Fokus utama project:

- Mengganti buku penghubung fisik menjadi digital.
- Memudahkan guru mencatat aktivitas siswa di sekolah.
- Memudahkan orang tua mengirim laporan aktivitas anak di rumah.
- Memudahkan sekolah memantau data siswa, kelas, absensi, kedatangan, jadwal, dan laporan.
- Membatasi akses data sesuai role pengguna.

## 3. Role Utama Project

Project operasional menggunakan 5 role utama:

1. Admin / Kepala Sekolah
2. Guru
3. Orang Tua
4. Siswa
5. Admin Loket

Catatan teknis:

- Admin, Kepala Sekolah, Wakil Kesiswaan, dan operator menggunakan role `admin` yang sama.
- Perbedaan tanggung jawab internal sekolah dicatat pada kolom jabatan pengguna.
- Role lama `super_admin` dan `kepala_sekolah` tidak digunakan lagi.

## 4. Prinsip Pengerjaan

- Kerjakan fitur yang langsung mendukung komunikasi sekolah-rumah.
- Setiap fitur harus jelas dipakai oleh role siapa.
- Jangan menambah modul baru jika belum masuk scope MVP.
- Hak akses harus dicek sebelum UI dianggap selesai.
- Tampilan harus sederhana, bahasa Indonesia, dan mudah dipahami pengguna sekolah.
- Orang tua dan siswa harus nyaman menggunakan web dari HP.
- Data siswa tidak boleh terlihat oleh pengguna yang tidak berhak.

## 5. Scope MVP

MVP adalah versi awal yang harus selesai dan bisa dipakai sekolah.

### 5.1 Autentikasi dan Akun

Fitur wajib:

- Login pengguna.
- Register awal siswa/orang tua jika memang dipakai.
- Reset password atau pengajuan reset password.
- Manajemen user oleh admin.
- Status akun aktif/nonaktif.
- Role dan permission.

Role yang terlibat:

- Admin / Kepala Sekolah
- Guru
- Orang Tua
- Siswa
- Admin Loket

Kriteria selesai:

- Setiap role masuk ke dashboard masing-masing.
- Pengguna tidak bisa membuka halaman yang bukan haknya.
- Akun nonaktif tidak boleh digunakan untuk akses operasional.

### 5.2 Data Master Sekolah

Fitur wajib:

- Data guru.
- Data orang tua.
- Data siswa.
- Data kelas.
- Relasi siswa dengan orang tua.
- Relasi siswa dengan kelas.
- Relasi kelas dengan guru.
- Pengaturan sekolah, seperti jam masuk dan toleransi keterlambatan.

Role yang terlibat:

- Admin / Kepala Sekolah: kelola dan pantau data.
- Guru: melihat data kelas dan siswa yang diajar.
- Orang Tua: melihat data anak sendiri.
- Siswa: melihat profil sendiri.
- Admin Loket: tidak perlu akses data master penuh.

Kriteria selesai:

- Admin bisa membuat dan mengedit data utama.
- Guru hanya melihat kelas/siswa yang diajar.
- Orang tua hanya melihat anaknya.
- Siswa hanya melihat dirinya sendiri.
- Admin Loket tidak melihat data sensitif selain kebutuhan check-in.

### 5.3 Buku Penghubung Digital

Fitur wajib:

- Aktivitas sekolah.
- Aktivitas rumah.
- Komentar/diskusi aktivitas.
- Riwayat aktivitas siswa.

Alur utama:

1. Guru mencatat aktivitas siswa di sekolah.
2. Orang tua membaca aktivitas sekolah.
3. Orang tua mengisi aktivitas anak di rumah.
4. Guru membaca aktivitas rumah.
5. Guru dan orang tua bisa memberi komentar sesuai hak akses.
6. Siswa bisa melihat riwayat miliknya secara read-only.

Role yang terlibat:

- Guru
- Orang Tua
- Siswa
- Admin / Kepala Sekolah

Kriteria selesai:

- Guru tidak bisa mengedit aktivitas rumah milik orang tua.
- Orang tua tidak bisa mengedit aktivitas sekolah milik guru.
- Siswa tidak bisa membuat atau mengedit aktivitas.
- Admin / Kepala Sekolah bisa memantau seluruh aktivitas.

### 5.4 Kedatangan dan Absensi

Fitur wajib:

- Catat kedatangan siswa.
- Status tepat waktu atau terlambat.
- Absensi kelas.
- Rekap kehadiran.
- Notifikasi keterlambatan ke guru.

Alur utama:

1. Admin Loket mencatat siswa datang.
2. Sistem membaca jam masuk sekolah.
3. Sistem menentukan status tepat waktu/terlambat.
4. Sistem membuat data absensi hadir.
5. Jika terlambat, sistem memberi notifikasi ke guru.

Role yang terlibat:

- Admin Loket
- Guru
- Admin / Kepala Sekolah
- Orang Tua
- Siswa

Kriteria selesai:

- Admin Loket hanya fokus pada halaman catat kedatangan.
- Guru bisa melihat kehadiran siswa di kelasnya.
- Kepala sekolah/admin bisa melihat rekap.
- Orang tua bisa melihat riwayat absensi anak.
- Siswa hanya melihat status presensi hari ini pada dashboard.

### 5.5 Jadwal dan Informasi

Fitur wajib:

- Jadwal umum sekolah.
- Jadwal khusus kelas.
- Informasi kegiatan.

Role yang terlibat:

- Admin / Kepala Sekolah: membuat jadwal umum.
- Guru: membuat jadwal kelas yang diajar.
- Orang Tua: melihat jadwal umum dan jadwal kelas anak.
- Orang Tua: mengonfirmasi akan datang, belum bisa datang, atau mengusulkan jadwal ulang.

Kriteria selesai:

- Jadwal kelas lain tidak muncul ke orang tua yang tidak terkait.
- Guru tidak bisa mengedit jadwal milik kelas lain.

### 5.6 Notifikasi

Fitur wajib:

- Notifikasi manual.
- Notifikasi otomatis keterlambatan.
- Status sudah/belum dibaca.

Role yang terlibat:

- Admin / Kepala Sekolah
- Guru
- Orang Tua

Kriteria selesai:

- Guru hanya bisa mengirim notifikasi ke siswa/orang tua di kelasnya.
- Orang tua hanya melihat notifikasi miliknya.
- Pintu notifikasi berada di ikon lonceng topbar, bukan menu sidebar.

### 5.7 Pengajuan Orang Tua

Fitur wajib:

- Pengajuan izin.
- Pengajuan sakit.
- Status pending/approved/rejected.
- Persetujuan atau penolakan oleh guru kelas.
- Jika disetujui, sistem membuat catatan absensi sesuai tanggal.

Role yang terlibat:

- Orang Tua
- Guru

Kriteria selesai:

- Orang tua hanya mengajukan untuk anaknya.
- Guru hanya bisa menyetujui atau menolak laporan siswa di kelasnya.
- Guru tidak dapat mengubah siswa, jenis, tanggal, judul, atau isi laporan orang tua.
- Admin dan kepala sekolah tidak menampilkan halaman pengajuan orang tua.

### 5.8 Ekstrakurikuler

Fitur wajib:

- Daftar ekstrakurikuler dan pelatih penanggung jawab.
- Penempatan siswa sebagai peserta ekstrakurikuler.
- Jadwal/pertemuan dan presensi siswa serta pelatih.
- Materi, catatan kegiatan, dan foto kegiatan.
- Penilaian siswa per ekstrakurikuler.

Role yang terlibat:

- Admin / Kepala Sekolah
- Orang Tua
- Siswa

Kriteria selesai:

- Admin mengelola data, peserta, sesi, dokumentasi, materi, dan nilai ekstrakurikuler.
- Guru pembina tercatat sebagai penanggung jawab, tetapi tidak menggunakan halaman ekstrakurikuler.
- Orang tua dan siswa hanya melihat ekstrakurikuler yang diikuti siswa terkait.
- Foto kegiatan tersimpan sebagai dokumentasi dan tidak dapat diakses oleh role yang tidak berhak.

### 5.9 Laporan dan Export

Fitur wajib:

- Laporan aktivitas PDF.
- Export data siswa.
- Import data siswa.
- Export kedatangan.
- Export absensi.

Role yang terlibat:

- Admin / Kepala Sekolah
- Guru untuk laporan kelasnya jika diperlukan.

Kriteria selesai:

- Laporan mengikuti hak akses pengguna.
- Data yang diexport tidak bocor ke role yang tidak berhak.

### 5.10 Dashboard

Fitur wajib:

- Dashboard berbeda sesuai role.
- Statistik ringkas yang relevan.

Isi dashboard minimal:

- Admin / Kepala Sekolah: total siswa aktif, total kelas, hadir hari ini, terlambat hari ini, belum check-in, dan jadwal hari ini.
- Guru: total siswa kelas, aktivitas sekolah hari ini, aktivitas rumah baru, jadwal hari ini, presensi kelas.
- Orang Tua: jumlah anak, aktivitas sekolah hari ini, aktivitas rumah hari ini, notifikasi, jadwal.
- Siswa: status presensi hari ini dan jumlah ekstrakurikuler yang diikuti.
- Admin Loket: check-in hari ini, tepat waktu, terlambat, belum check-in.

Kriteria selesai:

- Dashboard tidak menampilkan menu atau statistik yang tidak relevan dengan role.

## 6. Fitur yang Tidak Dikerjakan Dulu

Fitur berikut ditunda agar project tidak melebar:

- Pembayaran SPP.
- Modul keuangan sekolah.
- WhatsApp Gateway.
- SMS Gateway.
- Aplikasi Android/iOS native.
- QR code check-in.
- RFID/kartu siswa.
- Rapor digital lengkap.
- E-learning.
- Ujian online.
- Chat real-time.
- Website profil sekolah.
- Multi sekolah atau multi cabang.
- Integrasi Dapodik.
- Integrasi payment gateway.
- Tanda tangan digital.

Jika fitur di atas diminta, masukkan ke daftar fase berikutnya, bukan MVP.

## 7. Prioritas Pengerjaan

### Prioritas 1 - Harus Beres

- Login dan role access.
- Dashboard role.
- Data siswa, guru, orang tua, kelas.
- Relasi siswa-orang tua-kelas-guru.
- Aktivitas sekolah.
- Aktivitas rumah.
- Komentar aktivitas.
- Kedatangan siswa.
- Absensi.
- Jadwal.
- Notifikasi.
- Pengajuan izin/sakit.
- Ekstrakurikuler.

### Prioritas 2 - Penyempurnaan

- Import/export Excel.
- Laporan PDF.
- Tampilan mobile orang tua/siswa.
- Validasi form.
- Empty state dan pesan error yang jelas.
- Pengujian akses role.

### Prioritas 3 - Setelah MVP Stabil

- Tampilan lebih rapi.
- Optimasi performa.
- Audit log.
- Backup otomatis.
- Paket maintenance.
- Integrasi tambahan jika benar-benar dibutuhkan.

## 8. Struktur Modul Saat Ini

Modul yang sudah ada/menjadi arah project:

- Users
- Teachers
- ParentProfiles
- Students
- SchoolClasses
- SchoolSettings
- SchoolActivities
- HomeActivities
- ActivityComments
- Schedules
- ScheduleResponses
- UserNotifications
- StudentArrivals
- AttendanceRecords
- ParentSubmissions
- PasswordResetRequests
- Extracurriculars
- ExtracurricularEnrollments
- ExtracurricularSessions
- ExtracurricularAttendances
- ExtracurricularScores

Halaman tambahan:

- Dashboard admin.
- Keamanan akun.
- Presensi kelas.
- Login.
- Register.
- Reset password.
- Laporan aktivitas dengan filter siswa dan tanggal.

## 9. Aturan Hak Akses Per Role

### Admin

Boleh:

- Melihat dashboard sekolah.
- Melihat data siswa, guru, orang tua, kelas.
- Mengelola data utama sesuai kebutuhan.
- Melihat absensi, kedatangan, jadwal, notifikasi, dan laporan.
- Mengelola seluruh data ekstrakurikuler.

Tidak difokuskan:

- Mengisi aktivitas harian satu per satu seperti guru/orang tua, kecuali untuk koreksi administratif.
- Meninjau pengajuan orang tua; alur tersebut menjadi tanggung jawab guru kelas.

### Kepala Sekolah

Boleh:

- Melihat dashboard dan data sekolah secara menyeluruh.
- Melihat guru, orang tua, siswa, kelas, kehadiran, jadwal, notifikasi, laporan, dan ekstrakurikuler.

Tidak boleh:

- Menambah, mengubah, atau menghapus data operasional.
- Meninjau pengajuan orang tua.

### Guru

Boleh:

- Melihat profil sendiri.
- Melihat kelas yang diajar.
- Melihat siswa di kelasnya.
- Membuat aktivitas sekolah.
- Melihat aktivitas rumah.
- Mengelola komentar.
- Membuat jadwal kelas.
- Melakukan presensi kelas.
- Mengirim notifikasi ke siswa/orang tua di kelasnya.
- Menyetujui atau menolak pengajuan orang tua untuk siswa di kelasnya.

Tidak boleh:

- Melihat semua data sekolah tanpa batas.
- Mengubah data siswa kelas lain.
- Mengubah aktivitas rumah milik orang tua.
- Mengirim notifikasi ke kelas lain.
- Mengakses kedatangan siswa atau ekstrakurikuler.
- Mengakses halaman Laporan Aktivitas atau mengunduh rekap aktivitas sekolah.
- Mengubah isi pengajuan orang tua selain status dan catatan peninjauan.

### Orang Tua

Boleh:

- Melihat profil keluarga sendiri.
- Melihat anak sendiri.
- Mengisi aktivitas rumah.
- Memulai dan membalas utas diskusi yang terkait dengan anak.
- Melihat jadwal anak dan mengirim konfirmasi kehadiran atau usulan jadwal ulang.
- Melihat notifikasi miliknya.
- Mengajukan izin/sakit.
- Melihat ringkasan presensi anak berupa nama, kelas, tanggal, kehadiran, dan status terlambat.
- Memantau ekstrakurikuler yang diikuti anak beserta ringkasan kehadirannya.

Tidak boleh:

- Melihat data anak keluarga lain.
- Mengubah data aktivitas sekolah.
- Membuka daftar laporan harian sekolah.
- Membuat jadwal.
- Mengelola data master sekolah.
- Membuka menu keamanan akun kecuali saat sistem mewajibkan pergantian password sementara.

### Siswa

Boleh:

- Melihat profil sendiri.
- Melihat status presensi hari ini di dashboard.
- Melihat ekstrakurikuler yang diikuti.

Tidak boleh:

- Membuat data aktivitas.
- Mengubah data profil utama.
- Melihat data siswa lain.
- Mengakses data guru/orang tua/kelas secara bebas.
- Membuka daftar presensi, laporan sekolah, laporan rumah, diskusi, jadwal, notifikasi, atau menu keamanan akun.

### Admin Loket

Boleh:

- Mencatat kedatangan siswa.
- Melihat ringkasan check-in hari ini.
- Mengedit catatan kedatangan hari ini yang dibuat sendiri jika diperlukan.

Tidak boleh:

- Mengelola data siswa lengkap.
- Mengakses data orang tua.
- Mengakses laporan penuh.
- Mengelola aktivitas, jadwal, notifikasi, atau pengajuan.

## 10. Definition of Done

Sebuah fitur dianggap selesai jika:

- Form bisa membuat data dengan validasi yang benar.
- List/table menampilkan data sesuai hak akses.
- Detail data bisa dibuka oleh role yang berhak.
- Edit/delete hanya tersedia untuk role yang berhak.
- Tampilan mobile tidak rusak untuk orang tua dan siswa.
- Ada pesan sukses/gagal yang jelas.
- Data tidak bocor ke role lain.
- Minimal ada pengujian manual untuk setiap role terkait.
- Jika fitur sensitif, ada automated test atau pengecekan akses yang kuat.

## 11. Checklist Sebelum Menambah Fitur

Sebelum mengerjakan fitur baru, jawab pertanyaan ini:

- Role mana yang memakai fitur ini?
- Masuk prioritas MVP atau fase berikutnya?
- Data apa yang dibuat/dibaca/diubah/dihapus?
- Siapa yang boleh melihat data ini?
- Apakah fitur ini mendukung buku penghubung digital?
- Apakah fitur ini bisa ditunda tanpa mengganggu operasional utama?
- Apakah fitur ini membuat scope melebar?

Jika jawabannya tidak jelas, fitur jangan langsung dikerjakan.

## 12. Fokus Sprint Terdekat

Urutan kerja yang disarankan:

1. Rapikan role 5 pengguna utama.
2. Pastikan menu tiap role sudah benar.
3. Uji alur data siswa-orang tua-kelas-guru.
4. Uji buku penghubung: aktivitas sekolah, aktivitas rumah, komentar.
5. Uji admin loket: catat kedatangan dan status terlambat.
6. Uji guru: presensi kelas.
7. Uji orang tua: pengajuan izin/sakit.
8. Uji laporan dan export.
9. Rapikan tampilan mobile.
10. Buat checklist UAT untuk sekolah.

## 13. Catatan Keputusan

Keputusan awal:

- Project berbasis web, bukan aplikasi mobile native.
- MVP berfokus pada komunikasi sekolah-rumah, absensi, kedatangan, jadwal, dan laporan.
- Role operasional dikunci menjadi 5.
- Fitur pembayaran, WhatsApp, QR, dan mobile app ditunda.
- Bahasa UI menggunakan bahasa Indonesia yang sederhana.

Jika ada perubahan keputusan, tambahkan di bagian ini agar semua pihak mengikuti acuan yang sama.
