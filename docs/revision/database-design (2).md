# Database Design - Revision
## Sistem Informasi Keuangan SMK Informatika CBI

**Status:** Draft Desain Revisi  
**Pendekatan:** AS-IS + TO-BE  
**Catatan:** Struktur penerimaan non-SPP masih mengikuti keputusan per jenis. Struktur pengeluaran R6 telah dikonfirmasi untuk scope tanpa bukti, metode pembayaran, nomor transaksi, atau approval.

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

## 10. R3: jenis_pembayaran

Tujuan konseptual:

- menyediakan master jenis penerimaan selain SPP;
- menghindari hard-code tabel/controller terpisah per jenis pembayaran.

Contoh kandidat jenis:

- SPP;
- UJIKOM;
- pembayaran lain sesuai keputusan sekolah.

Namun kolom final, tipe pembayaran, aturan tarif, relasi tagihan, dan business rule belum final.

Implementasi R3 menambahkan metadata berikut melalui migration baru:

- `aturan_pembayaran`: `sekali_bayar` atau `cicilan`;
- `tipe_periode`: `semester`, `gelombang`, atau `tahunan`.

Aturan ini dipakai saat Admin membuat tagihan dan disnapshot ke tagihan (`bisa_cicil`) agar perubahan master di masa depan tidak mengubah aturan transaksi yang sudah diterbitkan.

Status: **IMPLEMENTED FOR R3**.

### 10.1 R3: tagihan_pembayaran dan kwitansi multi-tagihan

`tagihan_pembayaran` tetap terpisah dari `tagihan_spp` agar business rule SPP tidak berubah. Tagihan non-SPP memiliki `kode_periode` dengan unique constraint:

```text
(id_siswa, id_jenis_pembayaran, id_tahun_ajaran, kode_periode)
```

Sebelum unique constraint periode diterapkan, migration preflight memeriksa seluruh kombinasi legacy siswa, jenis pembayaran, dan tahun ajaran. Upgrade dihentikan bila ditemukan lebih dari satu tagihan pada kombinasi tersebut agar default periode tidak menyebabkan migrasi schema parsial atau mengubah klasifikasi histori.

Satu kwitansi non-SPP disimpan pada `pembayaran_non_spp` sebagai header transaksi, sedangkan rincian alokasi disimpan di `detail_pembayaran_non_spp`.

```text
PembayaranNonSpp (1 kwitansi)
        ↓
DetailPembayaranNonSpp (1..n tagihan non-SPP)
        ↓
TagihanPembayaran
```

Satu detail menyimpan nominal pembayaran dan snapshot `total_terbayar_setelah` agar kwitansi lama tidak berubah ketika terjadi cicilan berikutnya. FK menggunakan pembatasan hapus untuk menjaga histori.

Pembatalan dilakukan pada level header kwitansi dan menghitung ulang status setiap tagihan dari detail pembayaran aktif. Untuk tagihan cicilan, pembatalan ditolak jika akan menyisakan pembayaran aktif lebih dari nol tetapi kurang dari `minimal_dp`.

---

## 11. R4: Header Penerimaan Gabungan

Transaksi baru menggunakan `penerimaan` sebagai header satu kwitansi untuk satu siswa. Header dapat memiliki paling banyak satu transaksi SPP dan satu transaksi non-SPP:

```text
Penerimaan
  ├── Pembayaran SPP -> DetailPembayaran -> TagihanSpp
  └── PembayaranNonSpp -> DetailPembayaranNonSpp -> TagihanPembayaran
```

Relasi `id_penerimaan` pada tabel pembayaran bersifat nullable agar seluruh histori lama tetap valid. Nomor kwitansi yang dicetak berasal dari `penerimaan`; nomor transaksi child tetap dipertahankan sebagai identitas internal. `arsip_kwitansi_siswa.id_penerimaan` menyimpan satu foto untuk satu kwitansi gabungan.

---

## 12. R6: Pengeluaran

Tabel `kategori_pengeluaran` menyimpan master kategori dengan nama lengkap dan status aktif. Kategori yang sudah tidak dipakai dinonaktifkan agar histori tetap memiliki referensi yang valid.

Tabel `pengeluaran` menyimpan transaksi uang keluar dengan struktur:

- `id_kategori_pengeluaran`;
- `id_user` sebagai pencatat TU;
- `tanggal_pengeluaran`;
- `keterangan`;
- `nominal`;
- status `aktif` atau `dibatalkan`;
- alasan, pelaku, dan waktu pembatalan.

Relasi `Pengeluaran -> KategoriPengeluaran` dan `Pengeluaran -> User` memakai foreign key `restrictOnDelete`. Tidak ada tabel atau kolom bukti, metode pembayaran, nomor transaksi, maupun approval pada scope R6. Pembatalan tidak menghapus row transaksi.

Kategori awal disediakan melalui seeder:

- Alat Tulis Kantor;
- Listrik dan Internet;
- Pemeliharaan Sarana;
- Kegiatan Sekolah.

---

## 13. Relationship yang Harus Tetap Aman pada Fase Awal

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

Sebelum membuat schema untuk penerimaan non-SPP, requirement berikut wajib tersedia:

- jenis penerimaan;
- aturan tarif;
- siapa yang dikenakan;
- sekali bayar/berkala;
- cicilan;
- aturan kwitansi;
- hubungan dengan Portal Siswa;
Untuk Pengeluaran, schema R6 hanya boleh diperluas setelah kategori, bukti, koreksi/pembatalan, pihak berwenang, dan kebutuhan pelaporan baru dikonfirmasi.
