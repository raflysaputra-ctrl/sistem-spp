# Database Design - Revision
## Sistem Informasi Keuangan SMK Informatika CBI

**Status:** Draft Desain Revisi  
**Pendekatan:** AS-IS + TO-BE  
**Catatan:** Struktur penerimaan non-SPP dan pengeluaran belum final sampai requirement pihak TU selesai.

---

## 1. Prinsip Desain

1. Database existing menjadi baseline dan harus dipertahankan selama refactor.
2. Jangan mengedit migration lama yang sudah pernah dijalankan.
3. Semua perubahan schema dilakukan melalui migration baru.
4. Histori pembayaran, detail pembayaran, kelas, dan arsip kwitansi tidak boleh hilang.
5. Jangan mengubah tabel SPP menjadi struktur generik sebelum aturan pembayaran non-SPP final.
6. Perubahan role dapat dilakukan lebih dahulu karena requirement-nya sudah disetujui.
7. Struktur yang masih `PROPOSED/TBD` tidak boleh langsung diimplementasikan.

---

## 2. AS-IS: Struktur Existing yang Relevan

Tabel domain utama existing:

1. `users`
2. `jurusan`
3. `tahun_ajaran`
4. `kelas`
5. `siswa`
6. `siswa_kelas`
7. `tarif_spp`
8. `tagihan_spp`
9. `pembayaran`
10. `detail_pembayaran`
11. `arsip_kwitansi_siswa`

Tabel framework/infrastruktur seperti `sessions`, cache, dan jobs tetap mengikuti implementasi Laravel project.

---

## 3. AS-IS: users

Kolom domain yang relevan pada implementasi existing:

| Kolom | Keterangan |
|---|---|
| `id_user` | Primary key |
| `nama` | Nama pengguna |
| `username` | Username unik |
| `password` | Password ter-hash |
| `role` | Existing: `petugas` atau `siswa` |
| `id_siswa` | Nullable, unique, FK untuk akun siswa |

Existing behavior:

- user internal menggunakan role `petugas`;
- akun siswa menggunakan role `siswa` dan terhubung ke `siswa`;
- siswa menggunakan guard/session terpisah pada aplikasi.

---

## 4. TO-BE READY: users.role

Target role:

```text
admin
tu
kepala_sekolah
siswa
```

Legacy mapping:

```text
petugas -> tu
```

Perubahan harus dilakukan melalui migration baru dan mempertahankan user existing.

Target aturan:

- `admin`, `tu`, `kepala_sekolah` adalah role internal;
- `siswa` tetap mempertahankan hubungan `id_siswa` existing;
- tidak perlu membuat tabel user terpisah untuk masing-masing role internal;
- password dan autentikasi existing tetap digunakan.

Status: **READY FOR IMPLEMENTATION**.

---

## 5. Tabel Existing yang Harus Dipertahankan pada Fase Role/Page Separation

Pada revisi awal, tabel berikut tidak boleh diubah hanya untuk pemisahan role/page:

- `jurusan`
- `tahun_ajaran`
- `kelas`
- `siswa`
- `siswa_kelas`
- `tarif_spp`
- `tagihan_spp`
- `pembayaran`
- `detail_pembayaran`
- `arsip_kwitansi_siswa`

Alasannya: business logic SPP dan portal existing sudah bergantung pada struktur tersebut dan requirement penerimaan non-SPP belum final.

---

## 6. AS-IS: tarif_spp

Existing role:

- menyimpan tarif SPP berdasarkan tahun ajaran dan tingkat;
- digunakan oleh `TagihanSppService` untuk generate tagihan SPP.

Keputusan revisi:

- tetap dipertahankan;
- jangan otomatis diubah menjadi `tarif_pembayaran`;
- desain tarif pembayaran selain SPP: **TBD**.

---

## 7. AS-IS: tagihan_spp

Existing fungsi:

- tagihan bulanan siswa;
- terhubung ke siswa, riwayat kelas, dan tarif SPP;
- menyimpan bulan, tahun, nominal, status, dan tanggal lunas;
- memiliki constraint untuk mencegah duplikasi periode per siswa.

Keputusan revisi:

- tetap digunakan untuk flow SPP existing pada fase awal;
- generalisasi ke model `tagihan` umum belum boleh dilakukan sampai aturan UJIKOM/pembayaran lain final.

Status generalisasi: **TBD / BLOCKED**.

---

## 8. AS-IS: pembayaran dan detail_pembayaran

`pembayaran` existing menyimpan transaksi/kwitansi, siswa, user/petugas, tanggal bayar, total, status aktif/dibatalkan, serta metadata pembatalan.

`detail_pembayaran` menghubungkan transaksi dengan tagihan SPP dan nominal bayar.

Keputusan revisi:

- business logic pembayaran SPP existing harus tetap berfungsi;
- pembatalan existing tetap digunakan;
- perluasan agar menerima tagihan non-SPP belum final;
- jangan membuat tabel pembayaran terpisah per jenis (`pembayaran_ujikom`, dll.) tanpa requirement final.

Status perluasan: **TBD / BLOCKED**.

---

## 9. AS-IS: arsip_kwitansi_siswa

Existing kolom domain:

| Kolom | Keterangan |
|---|---|
| `id_arsip_kwitansi` | Primary key |
| `id_siswa` | FK siswa |
| `id_pembayaran` | FK pembayaran, nullable untuk kompatibilitas arsip lama |
| `path` | Path file private |
| `mime_type` | MIME file |
| `ukuran_file` | Ukuran file |

Existing constraint penting:

- `id_pembayaran` unique sehingga satu transaksi memiliki satu arsip foto baru;
- foto ditautkan ke transaksi aktif milik siswa;
- file disimpan pada storage private;
- foto dapat diganti pada transaksi aktif.

Keputusan revisi:

- struktur ini dipertahankan pada fase role/page separation;
- jangan mengubah relasi menjadi langsung ke `tagihan_spp`;
- cakupan untuk transaksi non-SPP: **TBD**.

---

## 10. PROPOSED: jenis_pembayaran

Tujuan konseptual:

- menyediakan master jenis penerimaan selain SPP;
- menghindari hard-code tabel/controller terpisah per jenis pembayaran.

Contoh kandidat jenis:

- SPP;
- UJIKOM;
- pembayaran lain sesuai keputusan sekolah.

Namun kolom final, tipe pembayaran, aturan tarif, relasi tagihan, dan business rule belum final.

Status: **PROPOSED / DO NOT IMPLEMENT YET**.

---

## 11. PROPOSED: Pengeluaran

Calon entitas yang mungkin dibutuhkan:

- kategori pengeluaran;
- transaksi pengeluaran.

Data minimal secara konsep:

- tanggal;
- kategori;
- keterangan;
- nominal;
- user pencatat.

Detail schema final belum boleh ditentukan sampai pihak TU mengonfirmasi:

- kategori;
- bukti transaksi;
- koreksi/pembatalan;
- pihak yang berwenang;
- kebutuhan pelaporan.

Status: **PROPOSED / DO NOT IMPLEMENT YET**.

---

## 12. Relationship yang Harus Tetap Aman pada Fase Awal

```text
User -> Pembayaran
User(siswa) -> Siswa
Siswa -> SiswaKelas
Siswa -> TagihanSpp
Siswa -> Pembayaran
Siswa -> ArsipKwitansiSiswa
TahunAjaran -> SiswaKelas
TahunAjaran -> TarifSpp
TarifSpp -> TagihanSpp
TagihanSpp -> DetailPembayaran
Pembayaran -> DetailPembayaran
Pembayaran -> ArsipKwitansiSiswa
```

Refactor authorization tidak boleh merusak relasi tersebut.

---

## 13. Migration Strategy untuk Revisi Role

Perubahan role adalah perubahan database pertama yang sudah siap.

Requirement:

1. Buat migration baru.
2. Ubah domain nilai role agar menerima:
   - `admin`
   - `tu`
   - `kepala_sekolah`
   - `siswa`
3. Migrasikan row existing:
   - `petugas` menjadi `tu`.
4. Pertahankan `id_siswa` untuk akun siswa.
5. Jangan mengubah tabel transaksi SPP pada migration ini.
6. Sediakan `down()` yang aman sejauh memungkinkan, dengan mempertimbangkan adanya role baru setelah migration dijalankan.

Detail implementasi teknis enum harus menyesuaikan DBMS/project dan diuji sebelum production.

---

## 14. Database Change Gate

Sebelum membuat schema untuk penerimaan non-SPP/pengeluaran, requirement berikut wajib tersedia:

- jenis penerimaan;
- aturan tarif;
- siapa yang dikenakan;
- sekali bayar/berkala;
- cicilan;
- aturan kwitansi;
- hubungan dengan Portal Siswa;
- kategori pengeluaran;
- bukti pengeluaran;
- mekanisme koreksi/pembatalan pengeluaran.

Jika belum tersedia, implementasi database harus berhenti pada perubahan role yang sudah disetujui.
