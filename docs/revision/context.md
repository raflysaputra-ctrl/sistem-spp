# Revision Context
## Sistem Informasi Keuangan SMK Informatika CBI

**Status:** Current Revision Context  
**Tujuan:** Menjelaskan konteks perubahan requirement terhadap sistem existing agar pekerjaan revisi tidak dianggap sebagai kelanjutan biasa dari milestone Sistem Pembayaran SPP sebelumnya.

---

## 1. Baseline Sistem Existing

Repository ini sebelumnya dibangun sebagai **Sistem Pembayaran SPP Sekolah**.

Implementasi existing yang sudah tersedia menjadi **baseline** dan harus direuse/refactor jika masih relevan. Fitur yang sudah ada antara lain:

- authentication user internal dengan role `petugas`;
- authentication siswa dengan guard/session terpisah;
- master data siswa;
- jurusan;
- kelas;
- tahun ajaran;
- tarif SPP;
- riwayat kelas dan kenaikan kelas;
- tagihan SPP bulanan;
- pembayaran SPP satu bulan dan multi-bulan;
- pembayaran tunggakan dan bulan berikutnya;
- kwitansi;
- riwayat pembayaran;
- pembatalan transaksi tanpa menghapus histori;
- rekap pembayaran dan export;
- laporan tunggakan;
- dashboard penerimaan SPP;
- Portal Wali;
- Portal Siswa;
- upload dan ganti foto kwitansi siswa yang terhubung ke transaksi pembayaran aktif.

Dokumen baseline existing berada pada:

- `docs/prd.md`
- `docs/database-design.md`
- `docs/implementation-plan.md`

Ketiga dokumen tersebut menjelaskan requirement dan proses implementasi sistem SPP sebelum revisi. Dokumen tersebut **bukan source of truth utama untuk pekerjaan revisi sistem keuangan**.

---

## 2. Alasan Revisi

Setelah bimbingan/evaluasi, scope sistem dikembangkan dari sistem yang berfokus pada pembayaran SPP menjadi **Sistem Informasi Keuangan**.

Perubahan utama yang telah disetujui:

- user internal dipisahkan menjadi **Admin**, **Tata Usaha (TU)**, dan **Kepala Sekolah**;
- TU menangani **Penerimaan** dan **Pengeluaran**;
- penerimaan tidak hanya SPP, tetapi nantinya juga mencakup UJIKOM dan pembayaran lain sesuai aturan sekolah;
- Kepala Sekolah memiliki dashboard keuangan dan rekap read-only;
- Admin menangani master data, pembatalan, rekap, dan backup;
- Portal Siswa dan Portal Wali tetap dipertahankan;
- UI `siswa/status-spp` nantinya disederhanakan agar tidak terlalu ramai;
- fitur upload/ganti foto kwitansi Portal Siswa tetap dipertahankan.

Gunakan istilah **Penerimaan**, bukan **Pendapatan**, untuk uang masuk yang dicatat oleh sistem.

---

## 3. Prinsip Revisi

Pekerjaan revisi adalah **refactor dan extension terhadap sistem existing**, bukan pembangunan ulang dari nol.

Wajib:

1. Analisis implementasi existing sebelum membuat fitur baru.
2. Reuse controller, service, model, view, query, route name, dan business logic yang masih relevan.
3. Jangan menggandakan business logic hanya karena role berbeda.
4. Jangan menghapus histori transaksi, histori kelas, atau arsip kwitansi.
5. Jangan mengubah migration lama yang sudah pernah dijalankan; perubahan schema harus melalui migration baru.
6. Jangan menjalankan `migrate:fresh` pada database yang ingin dipertahankan.
7. Jangan mengimplementasikan requirement yang masih `TBD` berdasarkan asumsi.
8. Fitur SPP existing harus tetap bekerja selama proses revisi, kecuali requirement baru secara eksplisit mengubahnya.

---

## 4. Aktor Target

### 4.1 Admin

Fokus pada pengelolaan dan kontrol sistem:

- dashboard Admin;
- master data;
- manajemen data pengguna internal/siswa sesuai kebutuhan;
- pembatalan transaksi;
- rekap;
- backup data.

### 4.2 Tata Usaha (TU)

Fokus pada operasional keuangan:

- dashboard TU;
- Penerimaan;
- pembayaran SPP;
- UJIKOM dan pembayaran lain setelah requirement final;
- Pengeluaran setelah requirement final;
- riwayat pembayaran;
- arsip foto kwitansi siswa;
- status pembayaran;
- rekap pembayaran;
- laporan tunggakan.

### 4.3 Kepala Sekolah

Fokus pada monitoring dan laporan:

- dashboard keuangan;
- rekap penerimaan;
- rekap pengeluaran setelah modul tersedia;
- rekap keuangan;
- akses read-only.

### 4.4 Siswa

Tetap menggunakan Portal Siswa:

- login siswa;
- melihat status SPP miliknya;
- mengunggah foto kwitansi untuk transaksi aktif yang diizinkan;
- mengganti foto kwitansi yang sudah diunggah.

### 4.5 Wali

Tetap menggunakan Portal Wali public dengan mekanisme existing untuk melihat status SPP siswa.

---

## 5. Requirement yang Belum Final

Poin berikut belum boleh diimplementasikan sebagai aturan bisnis final sampai ada konfirmasi dari pihak TU/sekolah:

- daftar lengkap jenis penerimaan selain SPP;
- aturan UJIKOM;
- nominal UJIKOM;
- dasar penentuan tarif pembayaran selain SPP;
- apakah pembayaran selain SPP dapat dicicil;
- apakah satu kwitansi boleh mencampur SPP dan jenis pembayaran lain;
- siapa siswa yang dikenakan setiap jenis pembayaran;
- kategori pengeluaran;
- aturan input dan bukti pengeluaran;
- apakah upload foto kwitansi siswa berlaku untuk semua jenis pembayaran atau hanya SPP;
- apakah Portal Wali menampilkan pembayaran selain SPP.

Semua poin tersebut harus ditandai `TBD` di dokumen revisi sampai ada keputusan resmi.

---

## 6. Source of Truth untuk Pekerjaan Revisi

Untuk pekerjaan revisi Sistem Informasi Keuangan, gunakan urutan prioritas berikut:

1. `AGENTS.md`
2. `docs/revision/context.md`
3. `docs/revision/prd.md`
4. `docs/revision/database-design.md`
5. `docs/revision/implementation-plan.md`

Dokumen baseline lama tetap dapat dibaca untuk memahami implementasi existing, tetapi tidak boleh mengalahkan keputusan yang tercatat pada dokumen revisi.
