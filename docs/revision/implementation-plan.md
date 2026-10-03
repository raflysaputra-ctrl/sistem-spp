# Revision Implementation Plan
## Sistem Informasi Keuangan SMK Informatika CBI

**Status:** Fase Revisi Aktif  
**Catatan:** Dokumen ini bukan kelanjutan penomoran Milestone 0-17 pada implementation plan Sistem Pembayaran SPP lama.

Gunakan prefix **R** untuk seluruh milestone revisi agar jelas bahwa pekerjaan sekarang adalah refactor/extension terhadap baseline existing.

---

## Aturan Eksekusi Revisi

- Jangan mengerjakan seluruh revision milestone sekaligus.
- Sebelum setiap milestone, baca `docs/revision/context.md`, `prd.md`, dan `database-design.md`.
- Analisis implementation existing sebelum menambah file baru.
- Reuse fitur existing jika masih relevan.
- Jangan membuat keputusan untuk requirement `TBD`.
- Jangan menjalankan `migrate:fresh` pada database yang ingin dipertahankan.
- Semua perubahan schema melalui migration baru.
- Setiap milestone harus diakhiri dengan test/regression yang relevan dan ringkasan file berubah.

---

# R0 - Baseline dan Safeguard

**Status:** READY

## Tujuan

Membuat agent memahami bahwa project sedang masuk fase revisi sistem keuangan dan memastikan baseline existing aman sebelum refactor.

## Scope

- baca repository current;
- identifikasi route, controller, service, middleware, model, migration, Blade, dan test existing;
- catat fitur existing yang harus dipertahankan;
- validasi status migration/database sebelum perubahan;
- tidak melakukan perubahan business logic.

## Selesai Jika

- baseline fitur existing terdokumentasi;
- tidak ada asumsi bahwa project dibangun ulang;
- agent dapat menunjukkan bagian existing yang akan direuse pada R1/R2.

---

# R1 - Refactor Role dan Authorization

**Status:** READY FOR IMPLEMENTATION

## Tujuan

Memisahkan user internal existing dari satu role `petugas` menjadi:

- `admin`
- `tu`
- `kepala_sekolah`

Role `siswa` tetap dipertahankan.

## Scope

1. Buat migration baru untuk perubahan role.
2. Migrasikan user legacy `petugas` menjadi `tu`.
3. Update model/validation/seeder/factory/test yang bergantung pada nilai role.
4. Refactor middleware role existing agar mendukung role target dan guard siswa existing.
5. Pastikan authorization route bekerja secara server-side.
6. Pertahankan satu login internal.
7. Redirect internal user ke dashboard sesuai role atau siapkan dispatcher yang compatible dengan route existing.
8. Jangan mengubah business logic pembayaran SPP.

## Hak Akses Minimum Setelah R1

### Admin

- akses master data;
- akses riwayat transaksi yang diperlukan untuk pembatalan;
- boleh membatalkan transaksi;
- akses rekap.

### TU

- akses flow pembayaran SPP;
- riwayat pembayaran;
- arsip kwitansi siswa;
- status pembayaran/laporan yang relevan;
- rekap;
- **tidak boleh membatalkan transaksi**.

### Kepala Sekolah

- read-only ke data/dashboard/rekap yang diizinkan;
- tidak boleh master CRUD;
- tidak boleh transaksi;
- tidak boleh pembatalan.

### Siswa

- hanya Portal Siswa sesuai guard/role existing.

## Jangan Dikerjakan

- UJIKOM;
- pembayaran lain;
- Pengeluaran;
- backup;
- generalisasi tagihan;
- refactor UI Portal Siswa.

## Test Minimum

- Admin dapat mengakses area Admin.
- TU tidak dapat mengakses CRUD Admin melalui URL langsung.
- Kepala Sekolah tidak dapat mengakses CRUD Admin.
- TU dapat mengakses flow SPP.
- TU tidak dapat membatalkan transaksi.
- Kepala Sekolah tidak dapat membatalkan/transaksi.
- Admin dapat membatalkan transaksi.
- siswa tidak dapat mengakses route internal.
- Portal Siswa tetap login dan bekerja.
- Portal Wali tetap bekerja.

---

# R2 - Pemisahan Dashboard dan Navigasi

**Status:** READY AFTER R1

## Tujuan

Menyediakan page/dashboard dan sidebar sesuai role tanpa menduplikasi business logic.

## Scope

### Admin

Target navigasi:

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
- Rekap Pembayaran
Backup Data (pending / belum fungsional)
```

### TU

Target navigasi:

```text
Dashboard
Penerimaan
- SPP
- UJIKOM (pending)
- Pembayaran Lainnya (pending)
Pengeluaran
- Input Pengeluaran (pending)
- Riwayat Pengeluaran (pending)
Kelola Pembayaran
- Riwayat Pembayaran
- Arsip Kwitansi Siswa
- Status Pembayaran SPP jika masih dibutuhkan
Laporan / Rekap
- Rekap Pembayaran
- Laporan Tunggakan
```

### Kepala Sekolah

Target navigasi:

```text
Dashboard Keuangan
Rekap
- Penerimaan
```

Pengeluaran dan Rekap Keuangan final menunggu modul terkait.

## Layout Strategy

- pertahankan satu app shell/layout utama;
- jangan copy seluruh layout untuk tiap role;
- pisahkan navigation partial per role jika diperlukan;
- jangan hardcode label semua user internal sebagai "Petugas TU";
- branding internal mulai menggunakan "Sistem Informasi Keuangan".

## Reuse

- Master Data existing;
- PembayaranController/PembayaranService/TagihanSppService untuk SPP;
- RiwayatPembayaranController;
- PembatalanPembayaranService;
- RekapPembayaranController;
- LaporanTunggakanController;
- ArsipKwitansiSiswaController;
- DashboardController dapat direfactor/reuse tanpa menduplikasi query yang sama.

## Jangan Dikerjakan

- logic UJIKOM;
- logic pembayaran lain;
- logic Pengeluaran;
- logic backup;
- perubahan UI Portal Siswa.

## Test

- setiap role melihat menu yang sesuai;
- menu hidden/pending tidak memberikan akses route yang belum diizinkan;
- route existing yang direuse tetap berfungsi;
- SPP existing tidak mengalami regression.

---

# R3 - Konfirmasi Requirement Penerimaan Non-SPP

**Status:** BLOCKED - MENUNGGU PIHAK TU

## Tujuan

Mengumpulkan requirement resmi sebelum desain teknis.

## Data yang Wajib Diperoleh

- daftar jenis pembayaran selain SPP;
- aturan UJIKOM;
- nominal/dasar tarif;
- siapa siswa yang dikenakan;
- sekali bayar/berkala;
- cicilan;
- jatuh tempo/denda jika ada;
- aturan kwitansi;
- apakah satu transaksi dapat mencampur beberapa jenis pembayaran;
- apakah pembayaran tersebut tampil pada Portal Siswa/Wali;
- apakah foto kwitansi berlaku untuk transaksi non-SPP.

## Output

Update `docs/revision/prd.md` dan `database-design.md` berdasarkan keputusan nyata.

Tidak ada coding business logic pada milestone ini.

---

# R4 - Desain Penerimaan Umum

**Status:** BLOCKED BY R3

## Tujuan

Menentukan desain jenis pembayaran/tagihan/pembayaran yang dapat mendukung SPP dan penerimaan lain tanpa merusak flow SPP existing.

## Catatan

- jangan membuat tabel terpisah per jenis pembayaran tanpa alasan kuat;
- jangan generalisasi `tagihan_spp` sebelum aturan R3 lengkap;
- siapkan migration strategy yang menjaga data existing.

---

# R5 - Implementasi Penerimaan Non-SPP

**Status:** BLOCKED BY R4

## Scope Target

- master jenis pembayaran;
- UJIKOM;
- pembayaran lain yang sudah disetujui;
- integrasi kwitansi/riwayat/rekap sesuai aturan final.

Detail mengikuti hasil R3/R4.

---

# R6 - Modul Pengeluaran

**Status:** BLOCKED - MENUNGGU PIHAK TU

## Requirement yang Dibutuhkan

- kategori pengeluaran;
- siapa yang boleh input;
- bukti transaksi;
- koreksi/pembatalan;
- format rekap.

## Scope Target

- master kategori jika dibutuhkan;
- input pengeluaran;
- riwayat pengeluaran;
- authorization;
- audit metadata yang diperlukan.

---

# R7 - Rekap Keuangan

**Status:** BLOCKED BY R5/R6

## Target

- Rekap Penerimaan;
- Rekap Pengeluaran;
- Rekap Keuangan;
- filter periode;
- export sesuai requirement.

Core query existing harus direuse bila masih relevan.

---

# R8 - Dashboard Kepala Sekolah

**Status:** PARTIAL / FINAL BLOCKED BY R6-R7

## Target Akhir

- total Penerimaan;
- total Pengeluaran;
- selisih;
- grafik periode;
- ringkasan jenis penerimaan;
- read-only.

Sebelum Pengeluaran tersedia, jangan membuat nilai pengeluaran fiktif.

---

# R9 - Backup Data Admin

**Status:** PENDING REQUIREMENT DETAIL

## Target

Fitur backup khusus Admin.

Detail restore, retensi, format, lokasi file: **TBD**.

---

# R10 - Penyederhanaan UI Portal Siswa

**Status:** READY LATER

## Tujuan

Mengurangi kepadatan `siswa/status-spp` tanpa menghapus fitur existing.

## Target

- status 12 bulan SPP dibuat compact;
- detail transaksi tidak diulang pada setiap bulan;
- arsip/upload foto dipresentasikan berdasarkan transaksi/kwitansi;
- upload dan ganti foto tetap memakai service/validation/storage existing;
- transaksi multi-bulan hanya memiliki satu representasi arsip kwitansi.

## Jangan Ubah Tanpa Requirement Baru

- private storage;
- batas/validasi file existing;
- relasi satu foto per transaksi;
- requirement transaksi aktif;
- rule kepemilikan siswa.

---

# R11 - Regression Testing dan Security Review

**Status:** FINAL PHASE

## Coverage Minimum

- authentication semua role;
- authorization direct URL;
- master data;
- pembayaran SPP;
- pembayaran multi-bulan;
- pembatalan Admin;
- kwitansi;
- rekap;
- laporan tunggakan;
- Portal Siswa;
- upload/ganti foto kwitansi;
- Portal Wali;
- Penerimaan non-SPP;
- Pengeluaran;
- dashboard Kepala Sekolah;
- backup jika sudah dibuat.

---

## Current Execution Point

Revision milestone yang boleh dikerjakan sekarang:

```text
R0 -> R1 -> R2
```

Revision milestone berikut masih menunggu requirement pihak TU:

```text
R3 -> R4 -> R5
R6
```

Jangan melompat ke milestone yang masih `BLOCKED`.
