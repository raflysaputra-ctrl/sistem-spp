# Implementation Plan
## Sistem Pembayaran SPP Sekolah

Dokumen ini menjadi urutan implementasi project.

Jangan mengerjakan semua milestone sekaligus.

---

## Milestone 0 - Validasi Fondasi Project

### Tujuan

Memastikan project Laravel dan database siap digunakan.

### Checklist

- [ ] Laravel berjalan.
- [ ] `.env` menggunakan MySQL.
- [ ] Database `db_spp_sekolah` terhubung.
- [ ] Migration seluruh tabel utama tersedia.
- [ ] Seeder jurusan, tahun ajaran, kelas, dan tarif tersedia.
- [ ] `php artisan migrate:fresh --seed` berhasil tanpa error.
- [ ] Struktur tabel sesuai `database-design.md`.

### Selesai Jika

Project dapat dibangun ulang dari database kosong menggunakan migration + seeder.

---

## Milestone 1 - Model dan Relasi Eloquent

### Scope

Buat model:

- User
- Jurusan
- TahunAjaran
- Kelas
- Siswa
- SiswaKelas
- TarifSpp
- TagihanSpp
- Pembayaran
- DetailPembayaran

### Implementasi

Setiap model:

- tentukan `$table`;
- tentukan `$primaryKey`;
- tentukan `$fillable`;
- tentukan `$casts` jika dibutuhkan;
- buat relationship sesuai `database-design.md`.

### Jangan Dikerjakan

- controller pembayaran;
- view;
- dashboard;
- authentication UI.

### Test

Gunakan Tinker atau test sederhana untuk memastikan relasi dapat diakses.

---

## Milestone 2 - Authentication Petugas TU

### Scope

- login;
- logout;
- auth middleware;
- akun petugas.

### Requirement

Login menggunakan:

```text
username
password
```

Bukan email.

### Implementasi

- sesuaikan model `User`;
- gunakan hash password;
- buat form login;
- buat proses login;
- buat logout;
- proteksi route internal.

### Test

- login benar berhasil;
- password salah ditolak;
- username tidak ada ditolak;
- route internal tidak dapat diakses guest;
- logout berhasil.

---

## Milestone 3 - Layout dan Navigasi Dasar

### Scope

Buat layout aplikasi dan navigasi awal:

```text
Dashboard

Master Data
- Data Siswa
- Data Jurusan
- Data Kelas
- Tahun Ajaran
- Tarif SPP

Pembayaran
- Transaksi Pembayaran
- Riwayat Pembayaran

Laporan
- Rekap Pembayaran
- Status Pembayaran SPP

Akun
- Logout
```

Frontend framework: **TBD**

Jangan menambahkan design system kompleks sebelum diputuskan.

---

## Milestone 4 - Master Data Akademik

### Scope

#### Tahun Ajaran

- daftar;
- tambah/edit sesuai kebutuhan;
- status aktif.

#### Jurusan

Data awal:

- PPLG
- DKV

Hak CRUD penuh: **TBD**

#### Kelas

- daftar;
- filter;
- data tingkat;
- jurusan;
- rombel.

#### Tarif SPP

- daftar tarif;
- tarif berdasarkan tahun ajaran dan tingkat.

### Validasi

- tingkat hanya 1-3;
- rombel 1-255; rombel 1-4 dibuat sebagai kelas awal jurusan;
- kombinasi kelas unik;
- kombinasi tarif unik.

---

## Milestone 5 - Data Siswa dan Riwayat Kelas

### Scope

- daftar siswa;
- tambah siswa;
- edit siswa;
- detail siswa;
- cari berdasarkan NIPD;
- cari berdasarkan nama;
- status siswa;
- penempatan kelas;
- histori kelas.
- hapus siswa menggunakan soft delete dengan konfirmasi NIPD.

### Input Siswa

- NIPD;
- nama;
- jenis kelamin;
- angkatan;
- status;
- kelas pada tahun ajaran aktif.

### Validasi

- NIPD wajib dan unik;
- jenis kelamin L/P;
- status valid;
- siswa hanya satu kelas per tahun ajaran.

### Test

- tambah siswa;
- edit;
- duplicate NIPD ditolak;
- tambah riwayat kelas;
- duplicate siswa + tahun ajaran ditolak.
- hapus siswa tidak menghilangkan riwayat.

---

## Milestone 6 - Service Pembuatan Tagihan

### Tujuan

Membuat mekanisme tagihan SPP per bulan.

### Scope

- generate tagihan berdasarkan siswa;
- tentukan kelas siswa;
- tentukan tingkat;
- tentukan tarif;
- simpan nominal snapshot;
- bulan;
- tahun;
- status awal `belum_bayar`.

### Validasi

Tidak boleh ada duplicate:

```text
id_siswa + bulan + tahun
```

### Catatan

Mekanisme kapan tagihan dibuat:

- otomatis;
- manual massal;
- saat siswa ditambahkan;

**TBD**

Jangan menentukan strategi final sebelum disetujui.

---

## Milestone 7 - Halaman Status SPP Siswa

### Scope

Tampilkan:

- identitas siswa;
- kelas;
- daftar bulan;
- nominal;
- status belum bayar/lunas;
- tanggal lunas jika ada.

### Tujuan

Petugas dapat melihat dengan cepat periode yang sudah atau belum dibayar.

---

## Milestone 8 - Transaksi Pembayaran

### Ini Milestone Kritis

Gunakan database transaction.

### Flow

1. Cari siswa.
2. Tampilkan tagihan.
3. Hanya tagihan belum lunas yang dapat dipilih.
4. Petugas memilih satu atau beberapa tagihan.
5. Hitung total server-side.
6. Generate nomor kwitansi.
7. Buat pembayaran.
8. Buat detail pembayaran.
9. Update tagihan menjadi lunas.
10. Isi tanggal lunas.
11. Commit.

### Requirement

- pembayaran bulan lalu diperbolehkan;
- bulan berjalan diperbolehkan;
- bulan berikutnya diperbolehkan;
- multi-bulan diperbolehkan;
- duplicate payment dilarang.

### Security

Jangan percaya total dari frontend.

Server harus menghitung ulang berdasarkan tagihan database.

### Concurrency

Saat transaksi, recheck status tagihan untuk mencegah pembayaran ganda.

Gunakan pendekatan locking jika diperlukan.

### Test

- bayar 1 bulan;
- bayar beberapa bulan;
- bayar tunggakan;
- bayar bulan depan;
- coba bayar tagihan yang sudah lunas;
- simulasi error dan pastikan rollback.

---

## Milestone 9 - Kwitansi

### Scope

Setelah pembayaran berhasil:

- detail transaksi;
- nomor kwitansi;
- tanggal;
- siswa;
- kelas;
- detail bulan;
- nominal;
- total;
- petugas.

### Fitur

- preview;
- print browser.

### TBD

- PDF;
- ukuran kertas;
- desain final;
- format nomor kwitansi.

Implementasikan format sementara hanya jika sudah disetujui.

---

## Milestone 10 - Riwayat Pembayaran

### Scope

Daftar pembayaran:

- no kwitansi;
- tanggal;
- siswa;
- total;
- petugas.

Detail transaksi:

- periode;
- nominal per periode.

Filter final mengikuti kebutuhan PRD.

### Pembatalan Transaksi

- transaksi tidak dihapus, tetapi berstatus `dibatalkan`;
- alasan, pengguna, dan waktu pembatalan dicatat;
- pembatalan memakai password pengguna dan database transaction;
- tagihan transaksi kembali `belum_bayar` agar dapat dibayar ulang;
- detail transaksi tetap menjadi histori dan transaksi hanya dapat dibatalkan satu kali.

---

## Milestone 11 - Rekap Pembayaran

### Filter Minimum

- bulan;
- tahun;
- jurusan;
- tingkat;
- rombel;
- siswa.

### Penting

Bedakan:

```text
periode SPP
```

dan:

```text
tanggal transaksi
```

Siswa yang membayar SPP Juli pada Agustus tetap dapat muncul di rekap periode Juli.

Tambahkan rentang `tanggal_mulai` dan `tanggal_selesai` berdasarkan `pembayaran.tanggal_bayar`, serta filter status transaksi `aktif`, `dibatalkan`, atau `semua`. Default laporan adalah transaksi aktif; export memakai filter yang sama.

---

## Milestone 12 - Dashboard

### Scope

Implementasikan setelah fitur transaksi stabil.

Widget final: **TBD**

Jangan membuat query dashboard kompleks sebelum kebutuhan final diputuskan.

---

## Milestone 13 - Validation dan Error Handling

### Pastikan

- validation message jelas;
- error transaksi tidak menghasilkan data parsial;
- foreign key error ditangani;
- duplicate constraint ditangani;
- unauthorized access ditolak.

---

## Milestone 14 - Testing

### Unit/Feature Test Penting

#### Authentication

- login valid;
- login invalid;
- guest redirect.

#### Siswa

- tambah siswa;
- duplicate NIPD;
- histori kelas.

#### Tarif

- tarif per tahun dan tingkat.

#### Tagihan

- generate;
- duplicate period ditolak.

#### Pembayaran

- satu bulan;
- multi-bulan;
- tunggakan;
- bulan depan;
- duplicate payment;
- total benar;
- rollback.

#### Rekap

- filter periode;
- filter kelas;
- pembayaran terlambat tetap masuk periode yang benar.
- pembatalan membutuhkan alasan dan password yang benar.
- pembatalan rollback bila pemulihan tagihan gagal.
- tagihan transaksi yang dibatalkan dapat dibayar kembali.
- transaksi dibatalkan tidak dihitung sebagai penerimaan aktif.
- filter periode SPP, tanggal transaksi, status, Excel, dan PDF konsisten.

---

## Milestone 15 - Review Security

Periksa:

- auth middleware;
- CSRF;
- mass assignment;
- validation;
- query;
- authorization;
- password hashing;
- session;
- database transaction.

---

## Milestone 16 - Production Preparation

Belum dilakukan sampai environment production diputuskan.

### TBD

- server/hosting;
- domain;
- HTTPS;
- backup;
- restore;
- environment variables;
- queue;
- logging;
- monitoring.

---

## Milestone 17 - Portal Wali dan Portal Siswa

### Portal Wali

- dapat diakses tanpa login dengan NIPD;
- menampilkan nama siswa, kelas, dan tagihan yang sudah tersedia;
- status lunas ditentukan dari detail pembayaran dengan transaksi `aktif`;
- tidak menampilkan nominal, nomor kwitansi, foto, atau tautan login petugas.

### Portal Siswa

- akun dibuat dan password direset oleh Petugas TU, tanpa pendaftaran publik;
- role siswa tidak dapat mengakses seluruh route Petugas TU;
- siswa hanya melihat tagihan dan status SPP miliknya;
- unggah JPEG/PNG maksimum 2 MB, kompres sebelum disimpan pada storage privat;
- unggahan baru wajib tertaut ke satu transaksi aktif milik siswa dan satu transaksi hanya memiliki satu foto yang dapat diganti; arsip lama tanpa transaksi tetap dipertahankan;
- unggahan tidak mengubah pembayaran, detail pembayaran, tagihan, atau tanggal lunas;
- Petugas TU dapat membuka arsip melalui route yang diproteksi.

### Test

- pencarian NIPD dan empty state wali;
- transaksi aktif, dibatalkan, dan pembayaran ulang pada status portal;
- pemisahan akses role petugas/siswa;
- pembuatan akun dan reset password siswa;
- validasi unggahan, akses arsip privat, dan tidak berubahnya data pembayaran.

---

# Urutan Eksekusi Rekomendasi

```text
0. Fondasi
1. Model + Relasi
2. Authentication
3. Layout
4. Master Data
5. Siswa + Riwayat Kelas
6. Tagihan
7. Status SPP
8. Pembayaran
9. Kwitansi
10. Riwayat
11. Rekap
12. Dashboard
13. Error Handling
14. Testing
15. Security Review
16. Deployment
```

---

# Prompt Awal untuk OpenCode

Gunakan prompt berikut setelah file ini disimpan di project:

```text
Baca terlebih dahulu:

- AGENTS.md
- docs/prd.md
- docs/database-design.md
- docs/implementation-plan.md

Dokumen tersebut adalah sumber requirement project.

Analisis kondisi project Laravel saat ini.

Jangan implementasikan semua fitur sekaligus.

Tentukan milestone saat ini berdasarkan implementation-plan.md dan kerjakan hanya milestone tersebut.

Jangan membuat keputusan baru untuk item yang masih TBD.

Setelah selesai:
1. jelaskan file yang dibuat/diubah,
2. jelaskan keputusan teknis penting,
3. jalankan pengecekan/test yang relevan,
4. laporkan jika ada requirement yang belum jelas.
```

---

# Prompt Milestone 1

```text
Baca AGENTS.md dan seluruh file di docs/.

Kerjakan hanya Milestone 1: Model dan Relasi Eloquent.

Migration dan database sudah tersedia.

Buat model:
- User
- Jurusan
- TahunAjaran
- Kelas
- Siswa
- SiswaKelas
- TarifSpp
- TagihanSpp
- Pembayaran
- DetailPembayaran

Pastikan:
- nama tabel benar,
- primary key custom benar,
- fillable benar,
- casts yang diperlukan,
- seluruh relasi Eloquent sesuai database-design.md.

Jangan mengerjakan controller, view, authentication, atau fitur lain.

Setelah selesai, jalankan pengecekan yang relevan dan jelaskan semua file yang diubah.
```
