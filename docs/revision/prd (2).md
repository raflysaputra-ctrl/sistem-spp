# Product Requirements Document (PRD)
## Sistem Informasi Keuangan SMK Informatika CBI

**Versi:** Revision 1.0 Draft  
**Status:** Revisi Requirement Aktif  
**Platform:** Web  
**Framework:** Laravel  
**Database:** MySQL  
**Environment Pengembangan:** XAMPP

---

## 1. Latar Belakang Revisi

Sistem existing awalnya dikembangkan sebagai **Sistem Pembayaran SPP Sekolah** dan telah memiliki fungsi pembayaran SPP, tagihan bulanan, kwitansi, rekap, laporan tunggakan, Portal Wali, Portal Siswa, serta arsip foto kwitansi siswa.

Setelah evaluasi/bimbingan, scope sistem dikembangkan menjadi **Sistem Informasi Keuangan** agar tidak hanya menangani pembayaran SPP, tetapi juga dapat mendukung penerimaan sekolah lainnya, pengeluaran, rekap keuangan, serta dashboard monitoring untuk Kepala Sekolah.

Revisi ini harus dilakukan dengan pendekatan **refactor dan extension** terhadap implementasi existing. Fitur yang sudah bekerja tidak boleh dibuat ulang tanpa alasan teknis yang jelas.

---

## 2. Tujuan Sistem Revisi

Sistem bertujuan untuk:

1. Memisahkan hak akses internal menjadi Admin, Tata Usaha, dan Kepala Sekolah.
2. Mempertahankan seluruh proses SPP existing yang masih sesuai kebutuhan sekolah.
3. Mengembangkan pencatatan **Penerimaan** agar nantinya tidak terbatas pada SPP.
4. Menyediakan pencatatan **Pengeluaran** sekolah setelah aturan bisnis dikonfirmasi.
5. Menyediakan rekap keuangan yang dapat digunakan sesuai hak akses tiap role.
6. Menyediakan dashboard keuangan untuk Kepala Sekolah secara read-only.
7. Mempertahankan Portal Siswa beserta upload/ganti foto kwitansi.
8. Mempertahankan Portal Wali.
9. Menyederhanakan UI status SPP siswa pada tahap revisi berikutnya tanpa mengurangi fungsi penting.
10. Menjaga histori transaksi dan integritas data existing selama proses revisi.

---

## 3. Terminologi

- Gunakan **Penerimaan** untuk uang masuk yang diterima sekolah dari pembayaran siswa.
- Gunakan **Pengeluaran** untuk uang keluar yang dicatat sekolah.
- Gunakan **Keuangan** sebagai scope umum sistem.
- Istilah **SPP** tetap dipakai pada fitur yang memang khusus SPP.

---

## 4. Aktor Sistem

### 4.1 Admin

Admin bertanggung jawab terhadap pengelolaan dan kontrol sistem.

Scope yang telah disetujui:

- login/logout internal;
- Dashboard Admin;
- CRUD/kelola master data;
- data siswa;
- jurusan;
- kelas;
- tahun ajaran;
- tarif SPP;
- kenaikan kelas;
- pengelolaan akun yang diperlukan sistem;
- pembatalan transaksi;
- melihat rekap;
- backup data.

Catatan:

- mekanisme backup final: **TBD**;
- Admin tidak otomatis diasumsikan sebagai petugas transaksi harian TU.

### 4.2 Tata Usaha (TU)

TU bertanggung jawab terhadap operasional transaksi keuangan.

Scope yang telah disetujui:

- login/logout internal;
- Dashboard TU;
- Penerimaan;
- pembayaran SPP;
- pembayaran UJIKOM setelah requirement final;
- pembayaran lainnya setelah requirement final;
- Pengeluaran setelah requirement final;
- riwayat pembayaran;
- arsip foto kwitansi siswa;
- status pembayaran SPP existing jika masih relevan;
- rekap pembayaran;
- laporan tunggakan.

TU tidak boleh membatalkan transaksi setelah pemisahan role; pembatalan menjadi kewenangan Admin.

### 4.3 Kepala Sekolah

Kepala Sekolah menggunakan sistem untuk monitoring.

Scope:

- login/logout internal;
- Dashboard Keuangan;
- melihat rekap penerimaan;
- melihat rekap pengeluaran setelah modul tersedia;
- melihat rekap keuangan;
- export laporan existing jika tetap relevan dan bersifat read-only.

Kepala Sekolah tidak boleh:

- menambah transaksi;
- mengubah transaksi;
- menghapus data;
- membatalkan transaksi;
- mengubah master data.

### 4.4 Siswa

Portal Siswa existing tetap dipertahankan.

Siswa dapat:

- login menggunakan mekanisme existing;
- melihat status SPP miliknya;
- mengunggah foto kwitansi fisik miliknya ke transaksi pembayaran aktif yang diizinkan;
- mengganti foto kwitansi pada transaksi aktif sesuai aturan existing;
- logout.

Aturan existing yang dipertahankan:

- satu transaksi pembayaran hanya memiliki satu arsip foto kwitansi;
- foto baru wajib tertaut ke transaksi aktif milik siswa;
- upload foto tidak mengubah status tagihan/pembayaran;
- file disimpan pada storage private sesuai implementasi existing.

UI `siswa/status-spp` akan disederhanakan pada milestone revisi tersendiri.

### 4.5 Wali

Portal Wali existing tetap dipertahankan.

Mekanisme existing:

- public tanpa login;
- pencarian berdasarkan NIPD;
- menampilkan informasi status SPP sesuai implementasi saat ini.

Apakah Portal Wali nantinya menampilkan jenis pembayaran selain SPP: **TBD**.

---

## 5. Struktur Navigasi Target

### 5.1 Admin

```text
Dashboard
Master Data
- Siswa
- Jurusan
- Kelas
- Tahun Ajaran
- Tarif SPP
- Kenaikan Kelas
Transaksi
- Pembatalan Transaksi
Rekap
- Rekap Pembayaran / Penerimaan
Backup Data
```

### 5.2 Tata Usaha

```text
Dashboard
Penerimaan
- SPP
- UJIKOM
- Pembayaran Lainnya
Pengeluaran
- Input Pengeluaran
- Riwayat Pengeluaran
Kelola Pembayaran
- Riwayat Pembayaran
- Arsip Kwitansi Siswa
- Status Pembayaran SPP (existing jika tetap diperlukan)
Laporan / Rekap
- Rekap Pembayaran
- Laporan Tunggakan
```

Menu yang requirement-nya belum final boleh ditampilkan sebagai pending/disabled atau ditunda sampai milestone terkait. Jangan membuat fungsi palsu.

### 5.3 Kepala Sekolah

```text
Dashboard Keuangan
Rekap
- Penerimaan
- Pengeluaran
- Keuangan
```

Pada tahap awal, dashboard hanya boleh menampilkan data yang benar-benar tersedia. Jangan membuat nilai pengeluaran fiktif sebelum modul Pengeluaran tersedia.

---

## 6. Authentication dan Authorization

### 6.1 Target Role

Role internal target:

- `admin`
- `tu`
- `kepala_sekolah`

Role portal siswa:

- `siswa`

Legacy role existing:

- `petugas`

Mapping migrasi:

```text
petugas -> tu
```

### 6.2 Login Internal

Gunakan satu halaman login internal.

Setelah autentikasi berhasil, sistem mengarahkan user ke dashboard sesuai role.

Tidak perlu membuat login terpisah untuk Admin, TU, dan Kepala Sekolah kecuali ada requirement baru.

### 6.3 Authorization

Authorization wajib diterapkan pada route/middleware, bukan hanya menyembunyikan menu.

Akses URL langsung oleh role yang tidak berhak harus ditolak.

---

## 7. Master Data

Master data existing yang masih relevan dipertahankan dan dipindahkan hak aksesnya ke Admin.

Meliputi:

- siswa;
- jurusan;
- kelas;
- tahun ajaran;
- tarif SPP;
- kenaikan kelas;
- akun siswa sesuai implementasi existing.

Master **Jenis Pembayaran** direncanakan untuk kebutuhan penerimaan non-SPP, tetapi struktur dan aturan finalnya menunggu konfirmasi pihak TU.

Status: **TBD / Belum Diimplementasikan**.

---

## 8. Penerimaan

### 8.1 SPP

Fitur SPP existing dipertahankan, termasuk:

- tagihan bulanan;
- pembayaran satu bulan;
- pembayaran multi-bulan;
- tunggakan;
- pembayaran bulan berikutnya;
- tarif berdasarkan tingkat dan tahun ajaran;
- pencegahan pembayaran ganda;
- transaksi atomic;
- kwitansi;
- riwayat pembayaran;
- rekap;
- pembatalan tanpa menghapus histori.

Business logic SPP tidak boleh digeneralisasi secara prematur ke pembayaran lain.

### 8.2 UJIKOM

UJIKOM telah disebut sebagai salah satu jenis penerimaan yang akan ditambahkan.

Namun aturan berikut masih **TBD**:

- nominal;
- siapa siswa yang dikenakan;
- berlaku berdasarkan kelas/jurusan/tahun ajaran atau tidak;
- sekali bayar atau dapat dicicil;
- batas waktu pembayaran;
- aturan kwitansi;
- hubungan dengan jenis pembayaran lain.

Jangan implementasikan flow final UJIKOM sebelum requirement tersebut dikonfirmasi.

### 8.3 Pembayaran Lainnya

Daftar jenis pembayaran lain serta aturan masing-masing: **TBD**.

---

## 9. Pengeluaran

Modul Pengeluaran merupakan requirement baru.

Fungsi target:

- input pengeluaran oleh role yang diizinkan;
- tanggal pengeluaran;
- kategori;
- keterangan;
- nominal;
- pencatat transaksi;
- riwayat pengeluaran;
- masuk ke rekap keuangan dan dashboard Kepala Sekolah.

Hal berikut masih **TBD**:

- daftar kategori pengeluaran;
- siapa yang boleh input selain TU;
- kebutuhan bukti nota/foto/file;
- mekanisme koreksi/pembatalan pengeluaran;
- format rekap pengeluaran.

---

## 10. Pembatalan Transaksi

Business logic pembatalan existing dipertahankan.

Aturan revisi:

- hanya Admin yang boleh melakukan pembatalan;
- TU tidak boleh melakukan pembatalan;
- Kepala Sekolah tidak boleh melakukan pembatalan;
- transaksi tidak dihapus;
- alasan pembatalan tetap dicatat;
- pengguna yang membatalkan tetap dicatat;
- waktu pembatalan tetap dicatat;
- status tagihan dikembalikan sesuai business logic existing.

---

## 11. Rekap

Core data/query existing harus direuse jika masih relevan.

Target penyajian:

- TU: rekap operasional pembayaran/penerimaan;
- Admin: rekap dengan akses yang sesuai kebutuhan kontrol;
- Kepala Sekolah: rekap read-only.

Target jangka lanjut:

- Rekap Penerimaan;
- Rekap Pengeluaran;
- Rekap Keuangan.

Detail final setelah jenis penerimaan dan pengeluaran selesai: **TBD**.

---

## 12. Dashboard

### Admin

Dashboard berisi ringkasan yang relevan untuk pengelolaan sistem. Detail final: **TBD**.

### TU

Dashboard fokus pada operasional penerimaan/pembayaran. Data SPP existing dapat direuse.

### Kepala Sekolah

Target akhir:

- total Penerimaan;
- total Pengeluaran;
- selisih keuangan;
- grafik periode;
- ringkasan berdasarkan jenis penerimaan.

Sebelum modul Pengeluaran tersedia, jangan menampilkan nilai Pengeluaran atau selisih yang dibuat-buat.

---

## 13. Backup

Backup merupakan fitur khusus Admin.

Detail berikut masih **TBD**:

- format backup;
- lokasi penyimpanan;
- download backup;
- apakah restore dilakukan melalui aplikasi atau secara manual;
- retensi file backup.

---

## 14. Portal Siswa dan Foto Kwitansi

Fitur existing tetap dipertahankan.

Perubahan yang direncanakan hanya pada presentation/UI:

- kartu status SPP dibuat lebih ringkas;
- detail transaksi tidak perlu diulang di setiap kartu bulan;
- upload/ganti foto kwitansi sebaiknya dipresentasikan berdasarkan transaksi/kwitansi agar transaksi multi-bulan tidak tampil berulang.

Perubahan tersebut dikerjakan pada milestone revisi tersendiri dan tidak boleh mengubah aturan storage/relasi existing tanpa requirement baru.

Apakah foto kwitansi nantinya berlaku untuk UJIKOM dan pembayaran lain: **TBD**.

---

## 15. Kebutuhan Nonfungsional

- Laravel Migration menjadi sumber perubahan schema.
- Perubahan existing schema harus melalui migration baru.
- Password tetap menggunakan hashing Laravel.
- Authorization wajib diuji pada route, bukan hanya UI.
- Business transaction penting menggunakan database transaction.
- Histori pembayaran dan histori kelas tidak boleh hilang.
- Regression terhadap SPP, Portal Siswa, Portal Wali, kwitansi, rekap, dan arsip foto harus diuji setelah refactor role.
- Hindari duplikasi controller/service/query jika fungsi dapat direuse lintas role.

---

## 16. Requirement yang Belum Diputuskan

1. Daftar lengkap penerimaan non-SPP.
2. Aturan UJIKOM.
3. Tarif UJIKOM/pembayaran lain.
4. Cicilan pembayaran non-SPP.
5. Pencampuran beberapa jenis pembayaran dalam satu kwitansi.
6. Kategori pengeluaran.
7. Bukti pengeluaran.
8. Aturan koreksi/pembatalan pengeluaran.
9. Konten final dashboard tiap role.
10. Detail backup/restore.
11. Tampilan pembayaran selain SPP pada Portal Siswa.
12. Tampilan pembayaran selain SPP pada Portal Wali.
13. Cakupan upload foto kwitansi untuk transaksi non-SPP.

Semua poin di atas tidak boleh diputuskan sendiri oleh implementer/OpenCode.
