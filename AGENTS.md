# AGENTS.md

# Project Instructions

Project ini adalah aplikasi Laravel + MySQL yang awalnya dikembangkan sebagai
**Sistem Pembayaran SPP Sekolah**.

Project saat ini sedang masuk ke fase **revisi requirement** menjadi:

**Sistem Informasi Keuangan SMK Informatika CBI**

Pekerjaan revisi adalah refactor dan extension terhadap sistem existing,
bukan pembangunan ulang dari nol.

Fitur SPP yang sudah berjalan adalah baseline yang harus dipertahankan
dan direuse selama masih sesuai requirement baru.

---

## Source of Truth

Untuk pekerjaan revisi Sistem Informasi Keuangan, baca dokumen berikut
dengan urutan prioritas:

1. `docs/revision/context.md`
2. `docs/revision/prd.md`
3. `docs/revision/database-design.md`
4. `docs/revision/implementation-plan.md`

Dokumen berikut adalah baseline lama Sistem Pembayaran SPP:

- `docs/prd.md`
- `docs/database-design.md`
- `docs/implementation-plan.md`

Dokumen baseline lama tetap boleh digunakan untuk memahami alasan,
business rule, dan implementasi fitur existing.

Namun untuk pekerjaan revisi baru, keputusan pada `docs/revision/`
memiliki prioritas lebih tinggi.

Jangan menganggap revision milestone sebagai kelanjutan langsung
Milestone 0-17 pada implementation plan lama.

Revision milestone menggunakan prefix:

`R0`, `R1`, `R2`, dan seterusnya.

---

## Kondisi Baseline Existing

Sistem existing sudah memiliki implementasi antara lain:

- authentication user internal dengan role `petugas`;
- authentication siswa dengan guard/session terpisah;
- master data siswa;
- jurusan;
- kelas;
- tahun ajaran;
- tarif SPP;
- riwayat kelas;
- kenaikan kelas;
- tagihan SPP bulanan;
- pembayaran SPP;
- pembayaran multi-bulan;
- pembayaran tunggakan;
- pembayaran bulan berikutnya;
- kwitansi;
- riwayat pembayaran;
- pembatalan transaksi;
- rekap pembayaran;
- export rekap;
- laporan tunggakan;
- dashboard penerimaan;
- Portal Wali;
- Portal Siswa;
- upload foto kwitansi;
- ganti foto kwitansi;
- arsip foto kwitansi yang terhubung ke transaksi pembayaran.

Jangan membuat ulang fitur tersebut dari nol jika implementation existing
masih dapat direuse atau direfactor.

---

## Revisi Requirement Aktif

User internal target:

- `admin`
- `tu`
- `kepala_sekolah`

Role portal siswa tetap:

- `siswa`

Legacy role:

- `petugas`

Legacy role `petugas` merepresentasikan Petugas Tata Usaha dan pada
refactor role akan dimigrasikan menjadi:

`petugas -> tu`

Gunakan istilah:

**Penerimaan**

untuk uang masuk dari pembayaran siswa.

Jangan gunakan istilah **Pendapatan** sebagai nama utama modul tersebut.

---

## Pembagian Role

### Admin

Scope utama:

- Dashboard Admin;
- master data;
- CRUD data yang menjadi kewenangan Admin;
- pengelolaan akun yang diperlukan;
- pembatalan transaksi;
- rekap;
- backup data.

### Tata Usaha (TU)

Scope utama:

- Dashboard TU;
- Penerimaan;
- pembayaran SPP;
- pembayaran UJIKOM setelah requirement final;
- pembayaran lainnya setelah requirement final;
- Pengeluaran setelah requirement final;
- riwayat pembayaran;
- arsip kwitansi siswa;
- status pembayaran;
- rekap pembayaran;
- laporan tunggakan.

TU tidak boleh melakukan pembatalan transaksi setelah role dipisahkan.

### Kepala Sekolah

Scope utama:

- Dashboard Keuangan;
- melihat rekap;
- monitoring keuangan.

Kepala Sekolah bersifat read-only.

Kepala Sekolah tidak boleh:

- melakukan transaksi;
- mengubah master data;
- menghapus data;
- membatalkan transaksi.

### Siswa

Portal Siswa existing tetap dipertahankan.

Siswa tetap dapat:

- login;
- melihat status SPP miliknya;
- upload foto kwitansi;
- mengganti foto kwitansi;
- logout.

### Wali

Portal Wali existing tetap dipertahankan dengan flow yang sekarang
sampai ada requirement baru.

---

## Requirement yang Masih TBD

Jangan membuat keputusan sendiri untuk:

- daftar lengkap jenis Penerimaan selain SPP;
- aturan pembayaran UJIKOM;
- nominal UJIKOM;
- dasar tarif pembayaran selain SPP;
- cicilan pembayaran selain SPP;
- pembayaran lainnya;
- apakah satu kwitansi dapat mencampur beberapa jenis pembayaran;
- kategori Pengeluaran;
- bukti Pengeluaran;
- aturan koreksi/pembatalan Pengeluaran;
- jenis pembayaran yang tampil pada Portal Siswa;
- jenis pembayaran yang tampil pada Portal Wali;
- apakah upload foto kwitansi berlaku untuk transaksi selain SPP;
- detail mekanisme backup/restore.

Requirement tersebut menunggu konfirmasi pihak TU/sekolah.

Jangan mengimplementasikannya hanya berdasarkan asumsi.

---

## Aturan Utama

- Baca implementation existing sebelum membuat file baru.
- Reuse controller, service, model, query, route, dan view jika masih relevan.
- Jangan menduplikasi business logic hanya karena role berbeda.
- Jangan membuat controller terpisah untuk setiap role jika core logic sama.
- Jangan membuat tabel terpisah per jenis pembayaran tanpa requirement yang jelas.
- Jangan menghapus histori pembayaran.
- Jangan menghapus histori kelas siswa.
- Jangan menghapus arsip kwitansi existing.
- Jangan melakukan hard delete transaksi sebagai metode pembatalan.
- Gunakan Laravel Migration sebagai sumber perubahan schema database.
- Jangan mengedit migration lama yang sudah pernah dijalankan untuk menyesuaikan revisi.
- Buat migration baru untuk perubahan schema.
- Jangan mengubah struktur database langsung melalui phpMyAdmin sebagai implementasi fitur.
- Jangan menjalankan `migrate:fresh` terhadap database yang ingin dipertahankan.
- Password user harus disimpan menggunakan hashing Laravel.
- Gunakan validation request pada input penting.
- Gunakan database transaction pada proses transaksi yang membutuhkan atomicity.
- Hindari raw query jika Eloquent atau Query Builder sudah memadai.
- Authorization harus diterapkan pada server/route/middleware, bukan hanya menyembunyikan menu.
- Jangan mengerjakan seluruh revision milestone sekaligus.

---

## Business Rule SPP Existing

Selama belum secara eksplisit diubah oleh requirement revisi,
aturan SPP existing tetap berlaku.

Termasuk:

- satu siswa hanya boleh memiliki satu tagihan SPP untuk periode bulan dan tahun yang sama;
- satu tagihan aktif tidak boleh dilunasi dua kali;
- pembayaran dapat mencakup beberapa bulan sekaligus;
- pembayaran bulan sebelumnya diperbolehkan;
- pembayaran bulan berjalan diperbolehkan;
- pembayaran bulan berikutnya diperbolehkan;
- periode SPP dan tanggal transaksi adalah data berbeda;
- histori tarif lama tidak boleh berubah ketika tarif baru dibuat;
- histori kelas siswa tidak boleh hilang;
- pembayaran harus atomic;
- pembatalan tidak menghapus histori transaksi.

Aturan SPP-specific tidak boleh otomatis diterapkan kepada UJIKOM
atau pembayaran lainnya sebelum requirement-nya jelas.

---

## Portal Siswa dan Foto Kwitansi

Fitur upload foto kwitansi existing harus dipertahankan.

Jangan mengubah tanpa requirement baru:

- hubungan foto dengan transaksi pembayaran;
- validasi kepemilikan siswa;
- requirement transaksi aktif;
- satu foto per transaksi;
- kemampuan mengganti foto;
- private storage;
- validasi file;
- compression service.

UI `siswa/status-spp` akan disederhanakan pada revision milestone tersendiri.

Jangan melakukan refactor UI tersebut ketika sedang mengerjakan
revision milestone role/page separation.

---

## Teknologi

- Backend: Laravel
- Database: MySQL
- Environment development: XAMPP
- ORM: Eloquent
- Authentication: session-based Laravel authentication
- Portal internal dan siswa menggunakan mekanisme guard/session project existing
- Frontend: Blade + asset project existing

Gunakan teknologi project existing kecuali requirement secara eksplisit
meminta perubahan.

---

## Database

Primary key existing menggunakan nama eksplisit seperti:

- `id_user`
- `id_jurusan`
- `id_tahun_ajaran`
- `id_kelas`
- `id_siswa`
- `id_siswa_kelas`
- `id_tarif`
- `id_tagihan`
- `id_pembayaran`
- `id_detail_pembayaran`
- `id_arsip_kwitansi`

Jangan mengubah nama primary key existing tanpa kebutuhan yang telah
disetujui dan migration strategy yang aman.

Sebelum mengubah database:

1. Pastikan perubahan memang diperlukan requirement aktif.
2. Periksa data dan relasi existing.
3. Buat migration baru.
4. Pertahankan foreign key dan unique constraint penting.
5. Pastikan migration tidak menghilangkan histori.
6. Tambahkan/update test yang relevan.

---

## Workflow Revision

Kerjakan berdasarkan:

`docs/revision/implementation-plan.md`

Untuk setiap revision milestone:

1. Baca `docs/revision/context.md`.
2. Baca requirement terkait di `docs/revision/prd.md`.
3. Baca desain terkait di `docs/revision/database-design.md`.
4. Analisis file existing yang berhubungan.
5. Tentukan apa yang dapat direuse.
6. Implementasikan hanya scope revision milestone tersebut.
7. Jangan mengerjakan requirement yang masih `TBD`.
8. Jalankan test/check yang relevan.
9. Perbaiki regression sebelum lanjut.
10. Jelaskan file yang dibuat dan diubah.
11. Jelaskan migration yang dibuat.
12. Jelaskan test yang dijalankan.

---

## Current Revision Execution Point

Revision milestone yang sudah dapat dikerjakan:

- `R0 - Baseline dan Safeguard`
- `R1 - Refactor Role dan Authorization`
- `R2 - Pemisahan Dashboard dan Navigasi`

Revision milestone terkait UJIKOM, pembayaran lain, dan Pengeluaran
masih menunggu requirement pihak TU.

Jangan melompat ke milestone yang berstatus `BLOCKED`.

Jika diminta mengerjakan R1, berhenti setelah R1 selesai.

Jika diminta mengerjakan R2, pastikan R1 sudah selesai dan berhenti
setelah R2 selesai.