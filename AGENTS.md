# AGENTS.md

# Project Instructions

Project ini adalah **Sistem Pembayaran SPP Sekolah** berbasis Laravel dan MySQL.

Pengguna utama aplikasi adalah **Petugas Tata Usaha (TU)**.

Sebelum melakukan perubahan signifikan pada project, baca:

- `docs/prd.md`
- `docs/database-design.md`
- `docs/implementation-plan.md`

## Aturan Utama

- Ikuti requirement pada `docs/prd.md`.
- Jangan membuat asumsi bisnis baru yang bertentangan dengan dokumen.
- Jika suatu kebutuhan masih ditandai `TBD`, jangan memutuskan sendiri kecuali diminta.
- Gunakan Laravel Migration sebagai sumber utama struktur database.
- Jangan mengubah struktur tabel langsung di database tanpa migration baru.
- Gunakan Eloquent Relationship sesuai `docs/database-design.md`.
- Gunakan database transaction untuk proses pembayaran.
- Jangan menghapus histori pembayaran.
- Jangan menghapus histori kelas siswa.
- Satu siswa hanya boleh memiliki satu tagihan untuk periode bulan dan tahun yang sama.
- Satu tagihan hanya boleh dilunasi satu kali.
- Satu transaksi pembayaran boleh mencakup beberapa tagihan sekaligus.
- Pembayaran bulan sebelumnya, bulan berjalan, dan bulan berikutnya harus didukung.
- Periode SPP dan tanggal transaksi adalah dua data yang berbeda.
- Tarif lama tidak boleh berubah ketika tarif tahun ajaran berikutnya berubah.
- Riwayat kelas lama harus tetap tersedia ketika siswa naik kelas.
- Password user harus disimpan menggunakan hashing Laravel.
- Gunakan validasi request pada input penting.
- Hindari query mentah jika Eloquent atau Query Builder sudah memadai.
- Jangan mengerjakan semua milestone sekaligus.

## Workflow Pengembangan

Kerjakan fitur berdasarkan urutan pada `docs/implementation-plan.md`.

Untuk setiap milestone:

1. Baca requirement terkait.
2. Analisis file yang sudah ada.
3. Implementasikan hanya scope milestone tersebut.
4. Jalankan test/check yang relevan.
5. Perbaiki error sebelum lanjut.
6. Jelaskan file apa saja yang dibuat atau diubah.

## Teknologi

- Backend: Laravel
- Database: MySQL
- Environment development: XAMPP
- ORM: Eloquent
- Authentication: session-based Laravel authentication
- Frontend: TBD

## Catatan Database

Primary key project menggunakan nama eksplisit, misalnya:

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

Jangan mengubah nama primary key tanpa alasan yang disetujui.

## Sebelum Mengubah Database

Pastikan:

- perubahan memang diperlukan oleh requirement;
- dibuat dalam migration baru;
- foreign key dan unique constraint tetap konsisten;
- perubahan tidak merusak histori transaksi.

## Sebelum Mengimplementasikan Pembayaran

Baca kembali:

- aturan tagihan;
- pembayaran multi-bulan;
- pencegahan pembayaran ganda;
- database transaction;
- nomor kwitansi;
- update status tagihan.

Proses pembayaran harus atomic: jika satu bagian gagal, seluruh transaksi harus rollback.
