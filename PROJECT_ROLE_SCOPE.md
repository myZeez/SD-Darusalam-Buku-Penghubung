# Project Role Scope

## Sistem Buku Penghubung Digital SD Darusalam

Status dokumen: Rancangan alur kerja dan hak akses v1  
Tanggal pembaruan: 20 Juli 2026  
Pemilik keputusan bisnis: SD Darusalam

Dokumen ini menjadi acuan utama untuk pembagian tugas, alur kerja, kepemilikan data, menu, dan hak akses setiap pengguna. Jika ada perbedaan dengan bagian hak akses pada `PROJECT_SCOPE.md`, keputusan pada dokumen ini yang digunakan sampai ada revisi tertulis berikutnya.

---

## 1. Tujuan Dokumen

Dokumen ini dibuat agar setiap fitur memiliki jawaban yang pasti untuk pertanyaan berikut:

- Siapa yang membuat data?
- Siapa yang boleh melihat data?
- Siapa yang boleh mengubah atau menghapus data?
- Siapa yang bertanggung jawab menyelesaikan prosesnya?
- Data apa yang muncul pada masing-masing dashboard?
- Apa yang terjadi setelah sebuah data dibuat?
- Bagaimana sistem membatasi data antar kelas dan keluarga?

Rancangan ini harus disetujui sebelum perubahan besar berikutnya dilakukan pada aplikasi.

---

## 2. Keputusan Role Final

Sistem menggunakan lima role bisnis.

| Kode role | Nama di antarmuka | Fungsi utama |
|---|---|---|
| `admin` | Admin / Kepala Sekolah | Mengelola keseluruhan sistem dan data sekolah |
| `guru` | Guru | Menjalankan pembelajaran dan operasional kelas sesuai penugasan |
| `orang_tua` | Orang Tua / Wali | Memantau dan memberikan informasi mengenai anak |
| `siswa` | Siswa | Melihat informasi pribadi dan sekolah yang relevan |
| `loket` | Petugas Loket | Mencatat kedatangan siswa di gerbang |

### 2.1 Admin dan Kepala Sekolah

Admin dan Kepala Sekolah adalah role yang sama. Perbedaan seperti Kepala Sekolah, Wakil Kesiswaan, atau Administrator Sekolah disimpan sebagai jabatan pengguna, bukan sebagai role atau permission yang berbeda.

Contoh:

| Nama | Role | Jabatan |
|---|---|---|
| Kepala SD Darusalam | Admin | Kepala Sekolah |
| Wakil Kesiswaan | Admin | Wakil Kesiswaan |
| Operator Sekolah | Admin | Administrator Sekolah |

Semua akun tersebut memiliki hak akses bisnis yang sama sebagai Admin.

### 2.2 Catatan Teknis Role Lama

- Role bisnis `kepala_sekolah` tidak digunakan lagi sebagai role terpisah.
- Akun dengan role `kepala_sekolah` harus dipindahkan ke role Admin.
- Role `super_admin` juga dipindahkan ke role Admin agar tidak ada jalur hak akses tersembunyi.
- Nama role teknis final adalah `admin`.

---

## 3. Prinsip Hak Akses

### 3.1 Hak Akses Mengikuti Tugas

Hak akses tidak diberikan hanya karena pengguna dapat melihat sebuah menu. Setiap aksi membuat, melihat, mengubah, menghapus, menyetujui, mencetak, dan mengekspor harus diperiksa secara terpisah.

### 3.2 Pembatasan Berdasarkan Relasi

- Guru dibatasi berdasarkan penugasan kelas dan mata pelajaran.
- Orang tua dibatasi berdasarkan anak yang terhubung ke akun keluarga.
- Siswa dibatasi berdasarkan profil siswa miliknya sendiri.
- Loket hanya mendapat data minimum yang dibutuhkan untuk mencatat kedatangan.

### 3.3 Struktur dan Operasional Dipisahkan

- Admin mengelola struktur: akun, kelas, siswa, mata pelajaran, guru, penugasan, dan jadwal.
- Guru menjalankan operasional: presensi, aktivitas sekolah, komunikasi, dan laporan internal.
- Orang tua menjalankan operasional keluarga: izin/sakit, aktivitas rumah, dan komunikasi.
- Loket menjalankan operasional kedatangan.

### 3.4 Koreksi Data

- Pengguna operasional hanya boleh memperbaiki data dalam batas tugas dan waktu yang ditentukan.
- Admin boleh melakukan koreksi administratif dengan alasan perubahan yang tercatat.
- Data penting tidak dihapus diam-diam. Perubahan harus dapat ditelusuri melalui audit log.

---

## 4. Konsep Data Utama

### 4.1 Struktur Akademik

Relasi akademik yang menjadi dasar sistem:

1. Sekolah memiliki tahun ajaran dan semester aktif.
2. Tahun ajaran memiliki kelas.
3. Kelas memiliki satu wali kelas dan dapat memiliki satu guru pendamping.
4. Sekolah memiliki daftar mata pelajaran.
5. Guru dapat mengajar lebih dari satu mata pelajaran.
6. Guru dapat mengajar mata pelajaran yang sama atau berbeda di beberapa kelas.
7. Penugasan mengajar dibuat oleh Admin per tahun ajaran.
8. Jadwal pelajaran dibuat berdasarkan kelas, mata pelajaran, guru, hari, dan jam pelajaran.

### 4.2 Jenis Penugasan Guru

Satu akun Guru dapat memiliki beberapa konteks penugasan sekaligus.

| Jenis penugasan | Ruang lingkup |
|---|---|
| Wali Kelas | Bertanggung jawab atas operasional utama satu kelas |
| Guru Pendamping | Membantu wali kelas dengan hak operasional kelas yang setara pada proses harian |
| Guru Mata Pelajaran | Mengajar mata pelajaran tertentu pada satu atau beberapa kelas |

Hak akses Guru ditentukan oleh konteks tersebut. Guru Mata Pelajaran tidak otomatis menjadi pengelola penuh kelas yang diajar.

### 4.3 Relasi Keluarga

- Satu akun Orang Tua mewakili satu keluarga atau wali utama.
- Satu akun Orang Tua dapat terhubung dengan beberapa anak.
- Setiap anak dapat berada di kelas yang berbeda.
- Saat Orang Tua memilih anak, kelas, wali kelas, guru pendamping, jadwal, presensi, dan laporan harus mengikuti data anak tersebut secara otomatis.
- Data keluarga lain tidak boleh muncul pada pilihan, pencarian, URL langsung, export, atau notifikasi.

---

## 5. Role Admin / Kepala Sekolah

### 5.1 Tujuan Role

Menjaga agar data, struktur akademik, akun, jadwal, dan proses sekolah berjalan benar. Admin bertanggung jawab atas konfigurasi sistem dan pemantauan keseluruhan, bukan mengerjakan tugas harian Guru, Orang Tua, atau Loket.

### 5.2 Tanggung Jawab Akun

Admin dapat:

- Membuat, melihat, mengubah, menonaktifkan, dan mengaktifkan akun Guru.
- Membuat akun Guru sekaligus profil Guru dalam satu formulir.
- Membuat, melihat, mengubah, menonaktifkan, dan mengaktifkan akun Siswa.
- Membuat akun Siswa sekaligus data Orang Tua dalam satu alur.
- Membuat dan mengelola akun Orang Tua.
- Membuat dan mengelola akun Petugas Loket.
- Mengatur ulang kata sandi pengguna.
- Memproses permintaan lupa kata sandi.
- Melihat riwayat perubahan akun.
- Menetapkan jabatan untuk akun Admin tanpa membuat role baru.

Admin tidak boleh mengubah kata sandi menjadi teks yang dapat dibaca kembali. Sistem hanya menyimpan hash dan menggunakan kata sandi sementara bila reset diperlukan.

### 5.3 Tanggung Jawab Data Siswa dan Keluarga

Admin dapat memasukkan data melalui tiga jalur:

1. Input manual oleh Admin.
2. Import Excel untuk banyak siswa dan keluarga.
3. Pendaftaran mandiri Siswa yang didampingi Guru.

Admin bertanggung jawab untuk:

- Memeriksa data pendaftaran mandiri.
- Menghindari duplikasi NIS, email Siswa, dan email Orang Tua.
- Menghubungkan saudara kandung ke akun Orang Tua yang sama.
- Menempatkan Siswa ke kelas.
- Memindahkan kelas dengan riwayat perpindahan.
- Mengaktifkan atau menonaktifkan status Siswa.
- Menangani kelulusan atau keluar sekolah tanpa menghapus riwayat lama.

### 5.4 Tanggung Jawab Struktur Sekolah

Admin mengelola:

- Identitas dan pengaturan sekolah.
- Tahun ajaran dan semester aktif.
- Tingkat kelas.
- Data kelas dan kapasitas maksimal.
- Ruangan kelas.
- Wali kelas dan guru pendamping.
- Mata pelajaran.
- Penugasan Guru per mata pelajaran dan kelas.
- Jam masuk, batas keterlambatan, istirahat, dan pulang.
- Periode atau jam pelajaran.
- Jadwal pelajaran mingguan.
- Hari libur dan kegiatan khusus sekolah.

### 5.5 Tanggung Jawab Operasional dan Pemantauan

Admin dapat:

- Melihat kedatangan seluruh Siswa.
- Melihat presensi seluruh kelas.
- Melihat pengajuan sakit atau izin untuk kebutuhan monitoring tanpa memproses keputusan.
- Melakukan koreksi presensi dengan alasan yang wajib diisi.
- Melihat aktivitas sekolah dan aktivitas rumah seluruh Siswa.
- Melihat diskusi Guru dan Orang Tua untuk kebutuhan pengawasan.
- Melihat agenda seluruh sekolah dan seluruh kelas.
- Mengelola seluruh data ekstrakurikuler.
- Melihat audit aktivitas Guru, Orang Tua, Siswa, dan Loket.
- Mengirim informasi umum sekolah.

Admin tidak menjadi pihak yang menyetujui izin atau sakit. Persetujuan tersebut tetap menjadi tugas Wali Kelas atau Guru Pendamping.

### 5.6 Laporan yang Dikelola Admin

Admin memiliki dua area laporan yang berbeda:

1. Laporan Siswa: rekap otomatis presensi, keterlambatan, aktivitas sekolah, dan aktivitas rumah.
2. Laporan Internal Guru: laporan perihal siswa, kelas, pembelajaran, fasilitas, kedisiplinan, atau kejadian tertentu yang ditujukan kepada Admin.

Admin dapat memfilter, melihat detail, memberi tindak lanjut, mengubah status, dan mengekspor laporan sesuai kebutuhan.

### 5.7 Batasan Admin

Admin tidak difokuskan untuk:

- Mengisi aktivitas sekolah harian menggantikan Guru.
- Mengisi aktivitas rumah menggantikan Orang Tua.
- Mencatat kedatangan rutin menggantikan Loket.
- Menyetujui izin/sakit menggantikan Wali Kelas.
- Membalas diskusi atas nama Guru atau Orang Tua.

Admin hanya melakukan koreksi jika terjadi kesalahan administratif dan alasan koreksi wajib dicatat.

### 5.8 Dashboard Admin

Dashboard minimal menampilkan:

- Total Siswa aktif.
- Total Guru aktif.
- Total kelas aktif.
- Hadir hari ini.
- Terlambat hari ini.
- Sakit, izin, alpa, dan belum dicatat hari ini.
- Jadwal dan agenda hari ini.
- Pendaftaran Siswa yang menunggu verifikasi.
- Laporan Internal Guru yang belum ditangani.
- Permintaan reset kata sandi.
- Ringkasan aktivitas pengguna terbaru.

---

## 6. Role Guru

### 6.1 Tujuan Role

Menjalankan pembelajaran, operasional kelas, pencatatan perkembangan Siswa, komunikasi dengan Orang Tua, dan pelaporan kepada Admin sesuai penugasan.

### 6.2 Profil Guru

Guru dapat:

- Melihat profil sendiri.
- Mengubah foto, nomor telepon, alamat, dan data pribadi yang diizinkan.
- Melihat NIP dan status kepegawaian.

Guru tidak dapat:

- Membuat akun Guru lain.
- Mengubah NIP sendiri tanpa persetujuan Admin.
- Melihat daftar seluruh Guru sebagai data manajemen.

### 6.3 Guru sebagai Wali Kelas atau Guru Pendamping

Wali Kelas dan Guru Pendamping dapat:

- Melihat detail kelas yang menjadi tanggung jawabnya.
- Melihat daftar Siswa aktif di kelas tersebut.
- Melihat informasi kontak Orang Tua yang diperlukan untuk komunikasi sekolah.
- Mengisi dan menyelesaikan presensi harian kelas.
- Meninjau pengajuan sakit atau izin dari Orang Tua.
- Membuat aktivitas sekolah untuk Siswa di kelasnya.
- Melihat aktivitas rumah Siswa di kelasnya.
- Membuka dan membalas diskusi dengan Orang Tua.
- Membuat agenda tambahan untuk kelasnya.
- Mengirim notifikasi kepada Siswa atau Orang Tua di kelasnya.
- Mencetak presensi dan laporan kelas yang menjadi tanggung jawabnya.

Wali Kelas dan Guru Pendamping tidak dapat:

- Menambah atau menghapus kelas.
- Memindahkan Siswa ke kelas lain.
- Mengubah kapasitas kelas.
- Mengganti Wali Kelas atau Guru Pendamping.
- Mengubah identitas utama Siswa dan Orang Tua.

### 6.4 Guru sebagai Guru Mata Pelajaran

Guru Mata Pelajaran dapat:

- Melihat jadwal mengajarnya.
- Melihat kelas dan daftar Siswa pada kelas yang diajar.
- Melihat mata pelajaran yang ditugaskan kepadanya.
- Membuat catatan pembelajaran atau aktivitas yang berkaitan dengan sesi mengajarnya.
- Membuat Laporan Internal Guru bila ada hal yang perlu ditindaklanjuti Admin.

Guru Mata Pelajaran tidak dapat:

- Mengubah jadwal pelajaran.
- Mengelola presensi harian kelas jika bukan Wali Kelas atau Guru Pendamping.
- Menyetujui izin/sakit jika bukan Wali Kelas atau Guru Pendamping.
- Membuat agenda umum kelas jika bukan Wali Kelas atau Guru Pendamping.
- Mengakses komunikasi keluarga di luar konteks pembelajaran yang ditugaskan.

### 6.5 Jadwal Guru

Guru hanya melihat jadwal pelajaran yang memuat dirinya sebagai pengajar. Jadwal menampilkan:

- Hari dan tanggal jika diperlukan.
- Jam mulai dan selesai.
- Mata pelajaran.
- Kelas.
- Ruangan.
- Keterangan atau perubahan jadwal.

Guru tidak membuat atau mengubah jadwal pelajaran. Jadwal pelajaran sepenuhnya dikelola Admin.

### 6.6 Laporan Internal Guru

Guru dapat mengirim laporan kepada Admin dengan kategori:

- Perkembangan Siswa.
- Kedisiplinan Siswa.
- Kondisi kelas.
- Pembelajaran.
- Fasilitas.
- Kejadian khusus.
- Lainnya.

Data minimal laporan:

- Guru pelapor otomatis.
- Tanggal laporan otomatis.
- Kelas terkait jika ada.
- Siswa terkait jika ada.
- Kategori.
- Judul.
- Deskripsi.
- Tingkat prioritas.
- Lampiran opsional.

Guru hanya dapat melihat laporan yang dibuatnya dan status tindak lanjut dari Admin.

### 6.7 Dashboard Guru

Dashboard Guru menyesuaikan penugasan dan minimal menampilkan:

- Kelas yang menjadi tanggung jawabnya.
- Jadwal mengajar hari ini.
- Jumlah Siswa pada kelas binaan.
- Status presensi kelas hari ini.
- Siswa terlambat hari ini.
- Pengajuan sakit/izin yang menunggu keputusan.
- Aktivitas sekolah yang belum diisi.
- Aktivitas rumah terbaru dari Orang Tua.
- Agenda kelas hari ini.
- Laporan Internal yang masih diproses.

---

## 7. Role Orang Tua / Wali

### 7.1 Tujuan Role

Memantau kondisi dan kegiatan anak, menyampaikan informasi dari rumah, serta berkomunikasi dengan Guru yang bertanggung jawab.

### 7.2 Ruang Lingkup Keluarga

- Orang Tua hanya dapat melihat anak yang terhubung dengan akun keluarganya.
- Jika memiliki dua atau lebih anak, Orang Tua memilih anak sebelum membuka data kontekstual.
- Kelas dan Guru diambil otomatis dari anak yang dipilih.
- Pergantian kelas anak tidak memutus riwayat laporan lama.

### 7.3 Hak Orang Tua

Orang Tua dapat:

- Melihat dan memperbarui profil keluarga serta kontak sendiri.
- Melihat profil ringkas setiap anak.
- Melihat kelas, Wali Kelas, dan Guru Pendamping anak.
- Melihat jadwal pelajaran dan agenda yang berlaku untuk kelas anak.
- Melihat presensi anak secara ringkas.
- Melihat waktu kedatangan dan status terlambat.
- Mengajukan sakit atau izin.
- Membatalkan pengajuan yang masih menunggu dan belum diproses Guru.
- Mengisi aktivitas rumah menggunakan kategori, checklist, dan teks.
- Melihat aktivitas sekolah anak secara read-only.
- Memulai atau membalas diskusi yang berkaitan dengan anak.
- Melihat ekstrakurikuler yang diikuti anak.
- Melihat ringkasan kehadiran ekstrakurikuler anak.
- Melihat dan mengunduh laporan anak.
- Memberikan konfirmasi pada agenda yang membutuhkan kehadiran Orang Tua.

### 7.4 Batasan Orang Tua

Orang Tua tidak dapat:

- Melihat anak keluarga lain.
- Mengubah data kelas dan Guru.
- Mengubah presensi secara langsung.
- Mengubah aktivitas sekolah yang dibuat Guru.
- Mengubah aktivitas rumah milik keluarga lain.
- Mengubah jadwal pelajaran atau agenda.
- Mengakses Laporan Internal Guru.
- Melihat audit aktivitas pengguna lain.

### 7.5 Dashboard Orang Tua

Dashboard minimal menampilkan:

- Pilihan anak aktif bila memiliki lebih dari satu anak.
- Status presensi anak hari ini.
- Jam kedatangan dan status terlambat.
- Jadwal pelajaran atau agenda hari ini.
- Aktivitas sekolah terbaru.
- Status aktivitas rumah hari ini.
- Pengajuan sakit/izin terbaru.
- Pesan atau balasan Guru yang belum dibaca.
- Ringkasan ekstrakurikuler anak.

Dashboard dan alur utama Orang Tua harus nyaman digunakan melalui ponsel.

---

## 8. Role Siswa

### 8.1 Tujuan Role

Memberikan akses baca yang sederhana terhadap informasi milik Siswa tanpa membebani Siswa dengan tugas administrasi.

### 8.2 Pendaftaran Mandiri

Pendaftaran mandiri dilakukan dengan pendampingan Guru dan mencakup:

- Data identitas Siswa.
- Email dan kata sandi akun Siswa.
- Data Orang Tua atau Wali.
- Email akun Orang Tua.
- Relasi keluarga.

Setelah dikirim:

1. Data berstatus menunggu verifikasi.
2. Admin memeriksa duplikasi dan kelengkapan.
3. Admin menentukan kelas.
4. Akun diaktifkan setelah disetujui.
5. Kredensial awal Siswa dan Orang Tua ditampilkan satu kali dan dapat disimpan sebagai gambar.

### 8.3 Hak Siswa Setelah Aktif

Siswa dapat:

- Melihat profil sendiri.
- Melihat nama kelas, Wali Kelas, dan Guru Pendamping.
- Melihat status presensi hari ini pada dashboard.
- Melihat ringkasan jadwal hari ini secara read-only.
- Melihat agenda sekolah yang relevan.
- Melihat ekstrakurikuler yang diikuti.

### 8.4 Batasan Siswa

Siswa tidak dapat:

- Mengubah data identitas utama tanpa proses Admin.
- Melihat profil Siswa lain.
- Mengisi atau mengubah presensi.
- Mencatat kedatangan sendiri.
- Mengisi aktivitas sekolah atau aktivitas rumah.
- Membuka halaman pengajuan Orang Tua.
- Membuka diskusi Guru dan Orang Tua.
- Mengakses notifikasi administratif.
- Mengubah jadwal.
- Mengakses laporan penuh, audit log, atau data keluarga sensitif.

### 8.5 Dashboard Siswa

Dashboard dibuat sederhana dan minimal menampilkan:

- Sapaan dan nama Siswa.
- Kelas.
- Status hadir hari ini.
- Status tepat waktu atau terlambat.
- Jadwal ringkas hari ini.
- Agenda sekolah berikutnya.
- Ekstrakurikuler yang diikuti.

Tidak diperlukan sidebar yang panjang untuk Siswa.

---

## 9. Role Petugas Loket

### 9.1 Tujuan Role

Mencatat kedatangan Siswa dengan cepat dan akurat di gerbang sekolah.

### 9.2 Hak Loket

Loket dapat:

- Mencari Siswa berdasarkan nama atau NIS.
- Melihat nama, NIS, foto, dan kelas sebagai identitas minimum.
- Mencatat waktu kedatangan.
- Melihat status tepat waktu atau terlambat yang dihitung sistem.
- Melihat daftar check-in pada hari berjalan.
- Memperbaiki catatan kedatangan yang dibuatnya pada hari yang sama.
- Melihat ringkasan jumlah datang, tepat waktu, terlambat, dan belum check-in.

### 9.3 Batasan Loket

Loket tidak dapat:

- Melihat alamat, data keluarga, nilai, aktivitas, atau diskusi Siswa.
- Membuat atau mengubah akun.
- Mengubah kelas Siswa.
- Mengisi status sakit, izin, atau alpa.
- Mengubah pengaturan jam keterlambatan.
- Melihat laporan lengkap sekolah.
- Mengedit catatan kedatangan hari sebelumnya tanpa bantuan Admin.

### 9.4 Dashboard Loket

Dashboard Loket berfokus pada satu pekerjaan:

- Pencarian atau pemindaian identitas Siswa.
- Tombol catat kedatangan.
- Waktu sekolah saat ini.
- Batas keterlambatan.
- Total check-in hari ini.
- Total tepat waktu.
- Total terlambat.
- Total belum check-in.
- Daftar kedatangan terbaru.

---

## 10. Pemisahan Jenis Jadwal

Istilah jadwal dibagi menjadi tiga modul agar tanggung jawab tidak bercampur.

### 10.1 Jam Operasional Sekolah

Berisi:

- Jam masuk.
- Batas keterlambatan.
- Jam istirahat.
- Jam pulang.
- Hari aktif sekolah.

Pengelola: Admin.  
Pengguna lain: hanya menggunakan hasil pengaturan.

### 10.2 Jadwal Pelajaran

Berisi:

- Hari.
- Periode pelajaran.
- Jam mulai dan selesai.
- Kelas.
- Mata pelajaran.
- Guru pengajar.
- Ruangan.

Pengelola: hanya Admin.  
Guru: melihat jadwal yang ditugaskan kepadanya.  
Orang Tua dan Siswa: melihat jadwal kelas terkait secara read-only.  
Loket: tidak memiliki akses.

### 10.3 Agenda dan Kegiatan Tambahan

Contoh:

- Pertemuan Orang Tua.
- Kegiatan kelas.
- Lomba.
- Kunjungan sekolah.
- Kegiatan keagamaan.

Admin dapat membuat agenda untuk semua kelas atau kelas tertentu. Wali Kelas dan Guru Pendamping dapat membuat agenda tambahan hanya untuk kelas binaannya. Guru Mata Pelajaran tidak dapat membuat agenda kelas kecuali juga menjadi Wali Kelas atau Guru Pendamping.

---

## 11. Alur Akun dan Pendaftaran

### 11.1 Pembuatan Akun Guru

1. Admin memilih Tambah Guru.
2. Admin mengisi identitas, NIP, kontak, email, dan kata sandi awal.
3. Sistem membuat User dan profil Guru dalam satu transaksi.
4. Admin memberikan penugasan mengajar atau kelas secara terpisah.
5. Guru login dan melengkapi profil pribadi yang diizinkan.

Jika pembuatan profil gagal, akun User juga tidak boleh tersimpan setengah jadi.

### 11.2 Input Manual Siswa dan Orang Tua

1. Admin memasukkan data Siswa.
2. Admin memilih keluarga yang sudah ada atau membuat data Orang Tua baru.
3. Sistem membuat akun Siswa dan Orang Tua bila diperlukan.
4. Admin memilih kelas.
5. Sistem memeriksa kapasitas kelas dan duplikasi data.
6. Sistem mengaktifkan akun.

### 11.3 Import Excel

1. Admin mengunduh template Excel.
2. Admin mengisi data Siswa, Orang Tua, akun, dan kelas.
3. Sistem melakukan pratinjau dan validasi sebelum menyimpan.
4. Baris bermasalah ditampilkan dengan alasan yang jelas.
5. Admin mengonfirmasi baris valid.
6. Sistem membuat akun dan relasi dalam satu proses transaksi per baris.
7. Sistem menghasilkan ringkasan berhasil, gagal, dan dilewati.

Import tidak boleh membuat duplikasi email, NIS, atau akun Orang Tua.

### 11.4 Pendaftaran Mandiri Siswa

1. Siswa membuka Registrasi Siswa bersama Guru pendamping proses.
2. Siswa dan Orang Tua mengisi data keluarga serta kredensial.
3. Sistem memeriksa email dan identitas dasar.
4. Pendaftaran masuk ke antrean verifikasi Admin.
5. Admin menyetujui, meminta perbaikan, atau menolak.
6. Admin menentukan kelas saat persetujuan.
7. Sistem mengaktifkan akun Siswa dan Orang Tua.
8. Kredensial awal ditampilkan satu kali dan dapat disimpan sebagai gambar.

Status pendaftaran:

`draft -> submitted -> needs_revision / approved / rejected`

---

## 12. Alur Penugasan Guru dan Jadwal Pelajaran

1. Admin membuat tahun ajaran dan semester.
2. Admin membuat atau mengaktifkan daftar mata pelajaran.
3. Admin membuat kelas.
4. Admin menentukan Wali Kelas dan Guru Pendamping.
5. Admin membuat Penugasan Mengajar: Guru + Mata Pelajaran + Kelas.
6. Admin membuat periode jam pelajaran.
7. Admin menyusun jadwal berdasarkan Penugasan Mengajar.
8. Sistem menolak benturan Guru, kelas, ruangan, atau periode.
9. Admin mempublikasikan jadwal.
10. Guru melihat jadwal yang memuat dirinya.
11. Orang Tua dan Siswa melihat jadwal kelasnya.

Perubahan jadwal harus menyimpan riwayat dan memberikan notifikasi kepada pihak terkait.

---

## 13. Alur Kedatangan dan Presensi Harian

### 13.1 Kedatangan di Loket

1. Loket mencari Siswa.
2. Sistem menampilkan identitas minimum dan kelas.
3. Loket menekan Catat Kedatangan.
4. Sistem menyimpan tanggal dan waktu server.
5. Sistem membandingkan waktu dengan jam masuk serta toleransi.
6. Sistem menentukan tepat waktu atau terlambat.
7. Sistem membuat atau memperbarui presensi hari itu menjadi Hadir.
8. Jika terlambat, sistem memberi notifikasi kepada Wali Kelas dan Guru Pendamping.

Satu Siswa hanya boleh memiliki satu kedatangan aktif per tanggal. Percobaan check-in kedua menampilkan data check-in yang sudah ada.

### 13.2 Pengajuan Sakit atau Izin

1. Orang Tua membuka Izin dan Sakit.
2. Orang Tua memilih anak.
3. Sistem mengisi kelas dan Guru secara otomatis.
4. Orang Tua memilih Sakit atau Izin.
5. Orang Tua mengisi tanggal dan deskripsi.
6. Status awal menjadi `pending`.
7. Wali Kelas dan Guru Pendamping menerima notifikasi.
8. Guru memilih Terima atau Tolak.
9. Jika diterima, sistem membuat presensi Sakit atau Izin dan menyalin deskripsi ke catatan.
10. Jika ditolak, presensi tidak berubah dan Orang Tua menerima alasan penolakan.

Status pengajuan:

`pending -> approved / rejected / cancelled`

### 13.3 Penyelesaian Presensi oleh Guru

1. Guru membuka Presensi Kelas pada tanggal hari ini.
2. Data Loket dan pengajuan Orang Tua dimuat otomatis.
3. Guru melengkapi Siswa yang belum memiliki status.
4. Guru memilih H, S, I, atau A.
5. Guru menambahkan catatan bila diperlukan.
6. Guru menyimpan dan memfinalisasi presensi.
7. Presensi dapat dicetak menjadi PDF.

Presensi harian tidak dihapus atau di-reset secara fisik. Setiap tanggal memiliki kumpulan presensi baru sehingga halaman hari berikutnya dimulai kosong tanpa menghilangkan riwayat.

Status presensi:

- `present`: Hadir.
- `sick`: Sakit.
- `permission`: Izin.
- `absent`: Alpa.

Flag tambahan:

- `is_late`: terlambat atau tidak.
- `source`: Loket, Guru, Orang Tua, atau koreksi Admin.
- `finalized_at`: waktu presensi dikunci Guru.

Setelah difinalisasi, perubahan oleh Guru membutuhkan pembukaan ulang atau koreksi Admin dengan alasan.

---

## 14. Alur Buku Penghubung Digital

### 14.1 Struktur Aktivitas Fleksibel

Aktivitas sekolah dan rumah menggunakan struktur:

1. Kategori.
2. Daftar item dalam kategori.
3. Jenis item checklist atau teks.

Contoh:

```text
Kategori: Kegiatan Ibadah
[x] Salat
[x] Mengaji

Kategori: Aspek Perkembangan
Teks: Sangat bersemangat dalam melakukan kegiatan luar ruang.
```

Admin dapat menyiapkan template kategori standar. Guru dan Orang Tua dapat menggunakan template lalu menyesuaikan item pada laporan sesuai kebutuhan tanpa mengubah data laporan lama.

### 14.2 Aktivitas Sekolah

1. Wali Kelas, Guru Pendamping, atau Guru Mata Pelajaran yang ditugaskan memilih Siswa sesuai cakupan.
2. Guru memilih tanggal.
3. Guru menambahkan kategori dan item checklist/teks.
4. Jika Guru Mata Pelajaran membuat catatan, mata pelajaran dan sesi mengajar dicatat sebagai konteks.
5. Guru menyimpan laporan.
6. Orang Tua dapat melihat laporan anak secara read-only.
7. Guru dan Orang Tua dapat membuka diskusi terkait laporan.

### 14.3 Aktivitas Rumah

1. Orang Tua memilih anak.
2. Sistem mengambil relasi keluarga otomatis.
3. Orang Tua memilih tanggal.
4. Orang Tua menambahkan kategori dan item checklist/teks.
5. Orang Tua menyimpan laporan.
6. Wali Kelas dan Guru Pendamping dapat melihat laporan.
7. Guru dan Orang Tua dapat berdiskusi terkait laporan.

### 14.4 Diskusi

- Satu percakapan dipisahkan berdasarkan anak dan konteks aktivitas.
- Tampilan daftar percakapan dikelompokkan per Orang Tua atau keluarga.
- Pesan tampil berurutan seperti aplikasi pesan atau email thread.
- Tombol Balas berada di area percakapan terkait.
- Pengguna hanya dapat mengubah pesan sendiri selama belum dibalas atau selama batas waktu yang ditentukan.
- Admin hanya memantau untuk pengawasan dan tidak membalas atas nama pihak lain.

---

## 15. Alur Laporan Internal Guru kepada Admin

Laporan Internal berbeda dari Laporan Siswa.

1. Guru membuat draft laporan.
2. Guru memilih kategori, prioritas, kelas, dan Siswa jika relevan.
3. Guru mengirim laporan.
4. Admin menerima notifikasi.
5. Admin membuka detail dan mengubah status menjadi Sedang Ditinjau.
6. Admin menambahkan catatan tindak lanjut.
7. Guru dapat melihat status dan tanggapan.
8. Admin menandai laporan Selesai setelah tindakan dilakukan.

Status laporan:

`draft -> submitted -> in_review -> follow_up -> resolved -> closed`

Aturan:

- Guru dapat mengubah atau menghapus hanya saat masih draft.
- Setelah dikirim, isi asli tidak dapat diubah.
- Admin tidak mengubah isi laporan Guru.
- Admin hanya mengelola status dan catatan tindak lanjut.
- Laporan dengan data sensitif tidak ditampilkan kepada Orang Tua, Siswa, atau Loket.

---

## 16. Alur Laporan Siswa dan Export PDF

Laporan Siswa disusun otomatis berdasarkan siswa dan periode.

Isi laporan:

- Identitas Siswa dan kelas.
- Wali Kelas.
- Ringkasan H, S, I, dan A.
- Waktu kedatangan.
- Status tepat waktu atau terlambat.
- Catatan presensi.
- Aktivitas sekolah per kategori.
- Aktivitas rumah per kategori.
- Lembar pengesahan.
- Kolom tanda tangan Guru.
- Kolom tanda tangan Orang Tua atau Wali.

Hak akses:

- Admin dapat melihat dan mengekspor seluruh Siswa.
- Wali Kelas dan Guru Pendamping hanya dapat mengekspor kelas binaannya.
- Orang Tua hanya dapat melihat dan mengunduh laporan anaknya.
- Siswa tidak mendapat halaman laporan penuh pada MVP.
- Loket tidak memiliki akses.

PDF harus selalu mengikuti filter dan hak akses pengguna. Mengubah parameter URL tidak boleh membuka laporan Siswa lain.

---

## 17. Alur Ekstrakurikuler

Admin mengelola:

- Daftar ekstrakurikuler.
- Pelatih atau penanggung jawab.
- Peserta Siswa.
- Jadwal pertemuan.
- Presensi Siswa dan pelatih.
- Materi.
- Foto kegiatan.
- Catatan dan nilai.

Orang Tua dapat:

- Melihat ekstrakurikuler yang diikuti anak.
- Melihat ringkasan kehadiran.
- Melihat materi, foto kegiatan, dan nilai yang dipublikasikan.

Siswa dapat:

- Melihat ekstrakurikuler yang diikuti.
- Melihat jadwal dan materi yang dipublikasikan.

Guru pembina dapat tercatat sebagai penanggung jawab, tetapi tidak otomatis mendapat menu pengelolaan ekstrakurikuler. Pengelolaan tetap dilakukan Admin pada scope saat ini.

Loket tidak memiliki akses ekstrakurikuler.

---

## 18. Alur Notifikasi

Notifikasi ditempatkan pada ikon lonceng di topbar, bukan sebagai menu sidebar utama.

| Peristiwa | Penerima |
|---|---|
| Siswa terlambat | Wali Kelas dan Guru Pendamping |
| Pengajuan sakit/izin baru | Wali Kelas dan Guru Pendamping |
| Pengajuan diterima/ditolak | Orang Tua |
| Aktivitas sekolah baru | Orang Tua terkait |
| Aktivitas rumah baru | Wali Kelas dan Guru Pendamping |
| Balasan diskusi baru | Lawan percakapan terkait |
| Jadwal pelajaran berubah | Guru terkait, Orang Tua, dan Siswa kelas terkait |
| Agenda kelas baru | Orang Tua kelas terkait |
| Laporan Internal baru | Admin |
| Tindak lanjut laporan | Guru pelapor |
| Reset kata sandi diproses | Guru kelas atau pihak penyampai yang ditentukan |

Notifikasi tidak boleh menjadi jalan untuk membuka data yang sebenarnya tidak boleh diakses penerima.

---

## 19. Alur Lupa Kata Sandi

1. Pengguna memilih Lupa Kata Sandi.
2. Pengguna memasukkan email atau identitas akun.
3. Sistem membuat permintaan reset berstatus menunggu.
4. Admin memeriksa permintaan.
5. Admin memproses reset dan sistem membuat kata sandi sementara.
6. Informasi disampaikan melalui Guru kelas bila akun milik Siswa atau Orang Tua.
7. Pengguna wajib mengganti kata sandi setelah login.
8. Kata sandi sementara tidak dapat digunakan kembali setelah diganti.

Status permintaan:

`pending -> processed / rejected / expired`

Halaman Keamanan Akun tidak menjadi menu rutin. Halaman hanya muncul saat pengguna wajib mengganti kata sandi sementara.

---

## 20. Matriks Hak Akses

Legenda:

- `Kelola`: membuat, melihat, mengubah, dan menonaktifkan/menghapus sesuai aturan.
- `Proses`: mengambil keputusan atau menyelesaikan alur.
- `Cakupan`: hanya data berdasarkan kelas, penugasan, anak, atau kepemilikan.
- `Lihat`: read-only.
- `-`: tidak memiliki akses.

| Modul | Admin | Guru | Orang Tua | Siswa | Loket |
|---|---|---|---|---|---|
| Akun pengguna | Kelola | Profil sendiri | Profil sendiri | Profil sendiri terbatas | Profil sendiri terbatas |
| Data Guru | Kelola | Profil sendiri | Lihat Guru anak | Lihat Guru kelas | - |
| Data Orang Tua | Kelola | Cakupan kelas seperlunya | Profil sendiri | - | - |
| Data Siswa | Kelola | Cakupan penugasan | Anak sendiri | Diri sendiri | Identitas minimum |
| Tahun ajaran/semester | Kelola | Lihat | Lihat ringkas | Lihat ringkas | - |
| Kelas dan kapasitas | Kelola | Cakupan binaan | Kelas anak | Kelas sendiri | Nama kelas saja |
| Penempatan Siswa | Kelola | Lihat | Lihat | Lihat | - |
| Mata pelajaran | Kelola | Lihat penugasan | Lihat jadwal anak | Lihat jadwal sendiri | - |
| Penugasan Guru | Kelola | Lihat sendiri | Lihat Guru anak | Lihat Guru kelas | - |
| Jam operasional | Kelola | Lihat | Lihat | Lihat | Lihat |
| Jadwal pelajaran | Kelola | Lihat sendiri | Lihat kelas anak | Lihat kelas sendiri | - |
| Agenda sekolah | Kelola | Cakupan kelas binaan | Lihat kelas anak | Lihat relevan | - |
| Kedatangan | Lihat/koreksi | Lihat kelas binaan | Lihat anak | Status hari ini | Kelola hari ini |
| Presensi harian | Lihat/koreksi | Kelola kelas binaan | Lihat anak | Status hari ini | Membuat Hadir dari check-in |
| Pengajuan sakit/izin | Lihat | Proses kelas binaan | Kelola milik anak | - | - |
| Aktivitas sekolah | Lihat/koreksi | Kelola cakupan | Lihat anak | - | - |
| Aktivitas rumah | Lihat/koreksi | Lihat kelas binaan | Kelola milik anak | - | - |
| Diskusi | Pantau | Cakupan kelas | Cakupan anak | - | - |
| Laporan Internal Guru | Kelola tindak lanjut | Kelola milik sendiri | - | - | - |
| Laporan Siswa | Kelola/export | Cakupan kelas/export | Anak sendiri/download | - | - |
| Ekstrakurikuler | Kelola | - | Lihat anak | Lihat milik sendiri | - |
| Notifikasi | Kelola informasi umum | Cakupan kelas | Milik sendiri | Informasi ringkas | Operasional sendiri |
| Audit log | Lihat seluruh | - | - | - | - |
| Reset kata sandi | Proses | Mengantar informasi sesuai tugas | Mengajukan | Mengajukan | Mengajukan |

---

## 21. Menu Per Role

### 21.1 Menu Admin

- Dashboard
- Pengguna
- Data Guru
- Data Orang Tua
- Data Siswa
- Data Kelas
- Mata Pelajaran
- Penugasan Guru
- Jadwal Pelajaran
- Agenda Kegiatan
- Kedatangan Siswa
- Presensi
- Aktivitas Sekolah
- Aktivitas Rumah
- Diskusi
- Ekstrakurikuler
- Laporan Siswa
- Laporan Internal Guru
- Import dan Export
- Permintaan Reset Kata Sandi
- Audit Aktivitas
- Pengaturan Sekolah

### 21.2 Menu Guru

- Dashboard
- Profil Guru
- Kelas Binaan
- Kelas yang Diajar
- Jadwal Mengajar
- Presensi Kelas
- Pengajuan Orang Tua
- Aktivitas Sekolah
- Aktivitas Rumah
- Diskusi Orang Tua
- Agenda Kelas
- Laporan Internal

Menu ditampilkan berdasarkan penugasan. Guru yang hanya menjadi Guru Mata Pelajaran tidak melihat Presensi Kelas dan Pengajuan Orang Tua untuk kelas yang bukan binaannya.

### 21.3 Menu Orang Tua

- Dashboard
- Profil Keluarga
- Anak Saya
- Presensi Anak
- Jadwal Anak
- Izin dan Sakit
- Aktivitas Sekolah
- Aktivitas Rumah
- Diskusi Guru
- Ekstrakurikuler
- Laporan Anak

Pada ponsel, navigasi bawah hanya menampilkan tugas utama. Menu lain ditempatkan pada menu tambahan agar tidak terlalu padat.

### 21.4 Menu Siswa

- Dashboard
- Profil Saya
- Ekstrakurikuler

Jadwal hari ini dan agenda ditampilkan pada dashboard, bukan sebagai menu pengelolaan terpisah.

### 21.5 Menu Loket

- Dashboard Loket
- Catat Kedatangan
- Riwayat Hari Ini

---

## 22. Aturan Bisnis dan Edge Case

### 22.1 Kapasitas Kelas

- Sistem tidak boleh menempatkan Siswa melebihi kapasitas tanpa konfirmasi Admin.
- Penempatan otomatis hanya memilih kelas pada tingkat yang sama dan masih tersedia.
- Admin dapat memilih kelas lain secara manual dengan alasan.

### 22.2 Guru Berubah atau Mutasi

- Mengganti Wali Kelas tidak mengubah penulis laporan lama.
- Guru lama kehilangan akses operasional setelah tanggal akhir penugasan.
- Guru baru mendapat akses mulai tanggal penugasan.

### 22.3 Siswa Pindah Kelas

- Riwayat presensi dan aktivitas tetap menunjuk kelas pada saat data dibuat.
- Jadwal dan akses berikutnya mengikuti kelas baru.
- Orang Tua tetap terhubung dengan anak yang sama.

### 22.4 Lebih dari Satu Anak

- Pilihan anak wajib muncul sebelum Orang Tua membuat izin, aktivitas rumah, atau membuka laporan.
- Kelas dan Guru tidak dipilih manual oleh Orang Tua.
- Data pilihan harus selalu divalidasi ulang pada server.

### 22.5 Check-in Ganda

- Check-in kedua tidak membuat record baru.
- Sistem menampilkan waktu check-in pertama.
- Koreksi waktu harus memiliki alasan dan jejak perubahan.

### 22.6 Pengajuan dan Check-in Bertabrakan

- Jika Siswa sudah check-in lalu pengajuan sakit/izin diterima, sistem memberi peringatan konflik kepada Guru.
- Guru menentukan status akhir dengan alasan.
- Sistem tidak mengubah otomatis tanpa keputusan Guru.

### 22.7 Hari Libur

- Presensi tidak dibuat otomatis pada hari libur.
- Jadwal pelajaran tidak ditampilkan sebagai aktif.
- Agenda khusus tetap dapat ditampilkan jika memang dijadwalkan pada hari tersebut.

### 22.8 Akun Nonaktif

- Akun nonaktif tidak dapat login.
- Data historis pengguna tetap tersimpan.
- Menonaktifkan akun Orang Tua tidak boleh menghapus data anak.

---

## 23. Audit Log Wajib

Aktivitas berikut minimal dicatat:

- Login berhasil dan gagal yang relevan.
- Pembuatan dan perubahan akun.
- Aktivasi dan penonaktifan akun.
- Import data.
- Perubahan kelas Siswa.
- Perubahan Wali Kelas atau Guru Pendamping.
- Perubahan penugasan dan jadwal.
- Koreksi kedatangan atau presensi.
- Persetujuan atau penolakan izin/sakit.
- Publikasi dan perubahan aktivitas.
- Perubahan status Laporan Internal.
- Export data sensitif.
- Reset kata sandi.

Audit menyimpan pelaku, waktu, jenis aksi, record terkait, nilai sebelum dan sesudah jika aman, serta alasan perubahan bila diwajibkan.

---

## 24. Modul Baru yang Dibutuhkan

Berdasarkan rancangan ini, modul yang belum lengkap atau belum ada adalah:

- [x] Jabatan Admin.
- [x] Tahun ajaran dan semester.
- [x] Mata pelajaran.
- [x] Penugasan Guru per mata pelajaran dan kelas.
- [x] Periode jam pelajaran.
- [x] Jadwal pelajaran mingguan.
- Hari libur sekolah.
- Verifikasi pendaftaran mandiri.
- Import Excel terpadu Siswa dan Orang Tua dengan pratinjau.
- Template kategori aktivitas.
- Laporan Internal Guru kepada Admin.
- Finalisasi dan penguncian presensi harian.
- Audit log yang dapat dilihat Admin.

Modul Agenda Kegiatan yang sudah ada perlu dipisahkan secara konsep dari Jadwal Pelajaran.

---

## 25. Urutan Implementasi yang Direkomendasikan

### Fase 1 - Normalisasi Role

Status: **Selesai pada 20 Juli 2026**

1. [x] Gabungkan Kepala Sekolah ke Admin.
2. [x] Tetapkan lima role bisnis.
3. [x] Tambahkan jabatan pengguna Admin.
4. [x] Audit menu, tombol, query, URL langsung, export, dan API per role.

### Fase 2 - Struktur Akademik

Status: **Selesai pada 20 Juli 2026**

1. [x] Tahun ajaran dan semester.
2. [x] Mata pelajaran.
3. [x] Kelas, Wali Kelas, dan Guru Pendamping.
4. [x] Penugasan Guru.
5. [x] Periode pelajaran.
6. [x] Jadwal pelajaran dan deteksi benturan.

### Fase 3 - Akun dan Pendaftaran

1. Form akun terpadu Guru.
2. Form akun terpadu Siswa dan Orang Tua.
3. Import Excel dengan pratinjau.
4. Verifikasi pendaftaran mandiri.
5. Penempatan kelas.

### Fase 4 - Kehadiran

1. Pengaturan jam operasional.
2. Kedatangan Loket.
3. Pengajuan Orang Tua.
4. Presensi kelas.
5. Finalisasi, koreksi, dan PDF.

### Fase 5 - Buku Penghubung

1. Template aktivitas.
2. Aktivitas sekolah.
3. Aktivitas rumah.
4. Diskusi per anak.
5. Notifikasi.

### Fase 6 - Laporan

1. Laporan Internal Guru.
2. Laporan Siswa.
3. PDF dan Excel.
4. Audit log.

### Fase 7 - Penyempurnaan

1. Ekstrakurikuler.
2. Dashboard per role.
3. Tampilan mobile Orang Tua dan Siswa.
4. UAT semua role.
5. Optimasi performa dan backup.

---

## 26. Definition of Done Per Alur

Sebuah alur dianggap selesai jika:

- Role yang benar dapat menyelesaikan tugas dari awal sampai akhir.
- Role lain tidak melihat tombol yang tidak dapat digunakan.
- URL langsung tetap menolak pengguna yang tidak berhak.
- Query hanya mengambil data sesuai relasi pengguna.
- Export mengikuti scope data yang sama dengan halaman.
- Notifikasi tidak membuka data di luar hak akses.
- Status data dan aksi lanjutan jelas.
- Ada validasi untuk duplikasi dan konflik.
- Ada pesan sukses, gagal, kosong, dan menunggu yang mudah dipahami.
- Tampilan nyaman pada ukuran layar target.
- Ada automated test untuk jalur berhasil dan penolakan akses.
- Data penting memiliki audit trail.

---

## 27. Skenario UAT Minimum

### Admin

- Membuat akun Guru lengkap dalam satu alur.
- Mengimpor Siswa dan Orang Tua dari Excel.
- Menyetujui pendaftaran mandiri.
- Membuat kelas dan menetapkan Wali serta Guru Pendamping.
- Menugaskan Guru Mata Pelajaran ke beberapa kelas.
- Membuat jadwal tanpa benturan.
- Membuka Laporan Internal Guru.
- Mengekspor Laporan Siswa.

### Guru

- Melihat jadwal mengajar sendiri.
- Membuka hanya kelas sesuai penugasan.
- Menyelesaikan presensi kelas binaan.
- Menyetujui izin Orang Tua.
- Membuat aktivitas checklist dan teks.
- Membalas diskusi keluarga.
- Mengirim Laporan Internal kepada Admin.

### Orang Tua

- Memilih salah satu dari dua anak.
- Melihat jadwal dan presensi anak terpilih.
- Mengirim izin atau sakit.
- Mengisi aktivitas rumah.
- Membalas Guru.
- Mengunduh laporan anak sendiri.
- Gagal membuka data anak keluarga lain melalui URL.

### Siswa

- Login dan melihat dashboard sederhana.
- Melihat status presensi dan jadwal hari ini.
- Melihat ekstrakurikuler sendiri.
- Gagal membuka presensi lengkap, diskusi, dan halaman pengelolaan.

### Loket

- Mencari Siswa dan mencatat kedatangan.
- Sistem menentukan keterlambatan.
- Check-in ganda ditolak dengan informasi yang benar.
- Loket gagal membuka data keluarga dan laporan.

---

## 28. Keputusan yang Dikunci Dokumen Ini

- Admin dan Kepala Sekolah adalah satu role bisnis.
- Sistem memiliki lima role bisnis.
- Jabatan tidak menentukan permission.
- Jadwal pelajaran hanya dikelola Admin.
- Agenda kelas dapat dibuat Wali Kelas atau Guru Pendamping untuk kelas binaan.
- Guru dapat mengajar banyak mata pelajaran dan banyak kelas melalui Penugasan Mengajar.
- Guru Mata Pelajaran tidak otomatis mendapat hak Wali Kelas.
- Loket hanya menangani kedatangan.
- Izin dan sakit diproses Wali Kelas atau Guru Pendamping.
- Orang Tua dapat memiliki lebih dari satu anak.
- Aktivitas mendukung kategori, checklist, dan teks.
- Laporan Internal Guru berbeda dari Laporan Siswa.
- Presensi harian disimpan per tanggal, bukan dihapus saat berganti hari.
- PDF Laporan Siswa memiliki kolom tanda tangan Guru dan Orang Tua pada bagian akhir.

---

## 29. Catatan Revisi

Setiap perubahan alur atau hak akses harus ditambahkan ke dokumen ini dengan:

- Tanggal perubahan.
- Keputusan yang diubah.
- Alasan perubahan.
- Dampak ke role dan modul.
- Persetujuan pemilik keputusan bisnis.

Perubahan percakapan yang belum masuk ke dokumen ini belum dianggap sebagai keputusan final implementasi.

### 20 Juli 2026 - Implementasi Struktur Akademik

- Menambahkan periode akademik, mata pelajaran, penugasan Guru, periode pelajaran, dan jadwal pelajaran mingguan.
- Memisahkan Jadwal Pelajaran dari Agenda Kegiatan.
- Memisahkan akses kelas binaan dari kelas yang diajar oleh Guru Mata Pelajaran.
- Menambahkan pencegahan benturan Guru, kelas, ruangan, dan rentang periode jam.
- Dampak: Admin mengelola struktur; Guru dan Orang Tua hanya melihat jadwal sesuai relasi masing-masing.
