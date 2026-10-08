# R3 — Perancangan dan Implementasi Pembayaran Non-SPP

## 1. Tujuan
R3 menambahkan pembayaran non-SPP tanpa merusak SPP yang sudah berjalan.

Jenis pembayaran:
- PTS
- PAS
- PKL
- UJIKOM
- Biaya Awal Masuk

Prinsip:
1. Nominal tidak di-hardcode.
2. Admin membuat/mengatur tagihan.
3. Admin menentukan total tagihan dan minimal pembayaran pertama/DP.
4. Pembayaran pertama boleh lebih besar dari minimal DP, tidak harus pas.
5. Pembayaran berikutnya dapat dicicil sampai lunas.
6. SPP tetap menggunakan alur yang sudah ada.

## 2. Data Bisnis

### 2.1 PTS
- Semester 1 kelas 1–3: Rp75.000
- Semester 2 kelas 1–3: Rp75.000

Nominal menjadi referensi bisnis, tetapi sistem harus mengambil nominal dari data tagihan, bukan source code.

PTS wajib dibayar lunas dalam satu transaksi.

### 2.2 PAS
- Semester 1 kelas 1–2: Rp85.000
- Semester 2 kelas 1–2: Rp85.000
- Kelas 3 tidak memiliki PAS.

Nominal harus dapat diatur Admin.

PAS wajib dibayar lunas dalam satu transaksi.

### 2.3 PKL Kelas 2
- Total: Rp1.300.000
- Bisa dicicil.
- Minimal pembayaran pertama/DP: Rp800.000.
- Pembayaran pertama boleh lebih besar dari Rp800.000.
- Setelah DP terpenuhi, sisa dapat dibayar bertahap.

### 2.4 UJIKOM Kelas 3
- Total: Rp1.600.000
- Bisa dicicil.
- Minimal pembayaran pertama/DP: Rp1.000.000.
- Pembayaran pertama boleh lebih besar dari Rp1.000.000.
- Setelah DP terpenuhi, sisa dapat dibayar bertahap.

### 2.5 Biaya Awal Masuk
Terdapat 3 gelombang. Contoh harga tahun sebelumnya:
- Gelombang 1: Rp2.000.000
- Gelombang 2: Rp2.300.000
- Gelombang 3: Rp2.500.000

Harga dapat berubah setiap tahun sehingga tidak boleh hardcode.

Aturan:
- Bisa dicicil.
- Pembayaran pertama wajib memenuhi minimal DP.
- Pembayaran pertama boleh lebih besar dari minimal DP.
- Setelah DP terpenuhi, sisa dapat dibayar bertahap.
- DP tiap gelombang harus dapat diatur Admin.
- DP gelombang 2 dan 3 belum diberikan, jangan diasumsikan.

## 3. Nominal Tagihan

Jangan membuat aturan seperti:
```php
if ($jenis === 'PKL') {
    $total = 1300000;
}
```

atau:
```php
$minimalDp = 800000;
```

Nominal harus berasal dari database.

Admin membuat/mengatur tagihan dengan minimal data:
- jenis pembayaran;
- siswa atau kelompok siswa;
- tahun ajaran/periode;
- total tagihan;
- minimal pembayaran pertama/DP;
- semester/gelombang bila diperlukan;
- status tagihan.

Tagihan menjadi sumber kebenaran nominal.

Contoh:
```text
Total = Rp1.300.000
Minimal DP = Rp800.000

Bayar Rp700.000  -> ditolak
Bayar Rp800.000  -> diterima
Bayar Rp900.000  -> diterima
Bayar Rp1.300.000 -> diterima, lunas
Bayar Rp1.400.000 -> ditolak
```

Jika pembayaran pertama Rp900.000:
```text
Total       Rp1.300.000
Sudah bayar Rp900.000
Sisa        Rp400.000
Status      Sudah Bayar Sebagian
```

## 4. Alur Pembayaran TU

Alur utama:

```text
TU
↓
Pilih siswa
↓
Pilih satu atau beberapa tagihan non-SPP
↓
Lihat total tagihan, sudah dibayar, sisa tagihan, dan aturan setiap tagihan
↓
Masukkan nominal untuk setiap tagihan yang dipilih
↓
Sistem menghitung total satu kwitansi
↓
Validasi
↓
Simpan pembayaran
↓
Update jumlah pembayaran dan status
↓
Generate/cetak kwitansi
```

### Validasi
1. Semua tagihan tersedia untuk siswa yang sama.
2. Tagihan berasal dari domain non-SPP; SPP tidak dicampur dalam kwitansi ini.
3. Tagihan belum lunas.
4. Nominal setiap detail > 0 dan tidak melebihi sisa tagihan.
5. PTS/PAS harus dibayar tepat sebesar sisa tagihan.
6. Jika pembayaran pertama untuk tagihan cicilan, nominal >= minimal DP.
7. Jika nominal total detail = sisa tagihan, status tagihan menjadi Lunas.

DP bukan status permanen. Status berdasarkan jumlah yang sudah dibayar:
- Belum Bayar
- Sudah Bayar Sebagian
- Lunas

### Pembatalan Kwitansi Cicilan

- Pembatalan tetap dilakukan per seluruh kwitansi oleh Admin dan tidak menghapus histori.
- Pembatalan boleh mengembalikan tagihan menjadi Belum Bayar jika tidak ada pembayaran aktif yang tersisa.
- Pembatalan ditolak jika akan menyisakan pembayaran aktif lebih dari Rp0 tetapi kurang dari minimal DP.
- Dalam kondisi tersebut, kwitansi cicilan yang lebih baru harus dibatalkan terlebih dahulu agar urutan pembayaran tetap valid.

## 5. Arsitektur Data

Gunakan konsep umum:

```text
Jenis Pembayaran
       ↓
Tagihan
       ↓
Kwitansi / Penerimaan Non-SPP
       ↓
Detail Pembayaran per Tagihan
```

Jenis pembayaran dapat mencakup:
- SPP
- PTS
- PAS
- PKL
- UJIKOM
- Biaya Awal Masuk
- Pembayaran Lainnya

Tetapi aturan bisnis tiap jenis tetap dapat berbeda. Satu kwitansi boleh memiliki beberapa detail tagihan non-SPP dari siswa yang sama.

Contoh:
- SPP: periodik/bulanan.
- PTS/PAS: semester.
- PKL/UJIKOM: total + minimal DP + cicilan.
- Biaya Awal Masuk: gelombang + total + minimal DP + cicilan.

## 6. R3.1 — Finalisasi Requirement

Dokumentasikan:
- jenis pembayaran;
- pihak pembuat tagihan;
- total;
- minimal DP;
- aturan pembayaran pertama;
- aturan pembayaran berikutnya;
- aturan lunas;
- semester/periode/gelombang;
- hubungan tagihan dengan siswa.

Jangan mengimplementasikan aturan yang belum diberikan.

## 7. R3.2 — Perancangan Database

Database harus dapat menyimpan:
- jenis pembayaran;
- tagihan per siswa;
- total tagihan;
- minimal DP;
- jumlah yang sudah dibayar;
- sisa;
- semester/periode/gelombang jika diperlukan;
- riwayat pembayaran;
- kwitansi.

Konsep data tagihan:
```text
Tagihan
--------------------------------
id
siswa_id
jenis_pembayaran_id
tahun_ajaran_id
total_tagihan
minimal_dp
periode/semester/gelombang
status
created_by
timestamps
```

Struktur final harus menyesuaikan migration/model existing. Jangan membuat tabel baru jika struktur existing masih dapat dikembangkan dengan aman.

## 8. R3.3 — Implementasi Bertahap

Urutan:
1. Data jenis pembayaran dan aturan periode.
2. Admin membuat tagihan massal per jenis/periode.
3. Daftar/detail tagihan.
4. TU memilih beberapa tagihan non-SPP milik satu siswa.
5. Validasi pembayaran setiap detail.
6. Simpan header kwitansi dan seluruh detail secara atomik.
7. Update status setiap tagihan.
8. Generate/cetak satu kwitansi.
9. Pembatalan seluruh kwitansi oleh Admin.
10. Perluasan Rekap Penerimaan.

Jangan langsung mengerjakan semuanya sekaligus.

## 9. R3.4 — Pengujian

Contoh:
```text
Total = Rp1.300.000
Minimal DP = Rp800.000
```

| Pembayaran | Hasil |
|---:|---|
| Rp0 | Ditolak |
| Rp700.000 | Ditolak |
| Rp800.000 | Diterima |
| Rp900.000 | Diterima |
| Rp1.000.000 | Diterima |
| Rp1.300.000 | Diterima, lunas |
| Rp1.400.000 | Ditolak |

Jika pembayaran pertama Rp900.000, sisa Rp400.000:

| Pembayaran berikutnya | Hasil |
|---:|---|
| Rp50.000 | Diterima |
| Rp100.000 | Diterima |
| Rp400.000 | Diterima, lunas |
| Rp500.000 | Ditolak |

## 10. Batasan R3

Jangan sekaligus:
- membangun ulang SPP;
- mengubah portal siswa;
- mengubah portal wali;
- membuat pengeluaran;
- membuat backup;
- mengubah hak akses Kepala Sekolah;
- melakukan refactor besar di luar kebutuhan pembayaran.

Fokus:
```text
Jenis Pembayaran
→ Tagihan
→ Pembayaran
→ Status
→ Kwitansi
```

## 11. Checklist

### Sudah ditetapkan
- [x] Nominal tidak hardcode.
- [x] Admin menentukan total tagihan.
- [x] Admin menentukan minimal DP.
- [x] Pembayaran pertama boleh lebih besar dari minimal DP.
- [x] Pembayaran berikutnya dapat dicicil.
- [x] Pembayaran tidak boleh melebihi sisa.
- [x] Status: Belum Bayar / Sudah Bayar Sebagian / Lunas.
- [x] Admin membuat tagihan massal satu jenis dalam satu form untuk seluruh periode yang berlaku.
- [x] PTS/PAS wajib lunas dalam satu transaksi.
- [x] Periode PTS/PAS: Semester 1 atau Semester 2.
- [x] Periode Biaya Awal Masuk: Gelombang 1, 2, atau 3.
- [x] Periode PKL/UJIKOM: satu kali per tahun ajaran.
- [x] Satu kwitansi boleh memuat beberapa tagihan non-SPP milik satu siswa.
- [x] SPP tidak dicampur ke dalam kwitansi non-SPP.
- [x] Pembatalan seluruh kwitansi non-SPP hanya oleh Admin, dengan alasan dan audit trail.
- [x] Pembatalan tidak boleh menyisakan cicilan aktif di bawah minimal DP.
- [x] Alur TU: pilih siswa → pilih beberapa tagihan → isi nominal per tagihan → validasi → simpan atomik → kwitansi.
- [x] Rekap Penerimaan membedakan penerimaan SPP dan non-SPP pada satu halaman.

### Masih perlu dikonfirmasi sebelum aturan final
- [ ] Format kwitansi non-SPP.
- [ ] Apakah tagihan non-SPP muncul di Portal Siswa/Wali.
- [ ] DP Biaya Awal Masuk Gelombang 2.
- [ ] DP Biaya Awal Masuk Gelombang 3.
- [ ] Bentuk final export Rekap Penerimaan; export existing tetap khusus SPP.
- [ ] Aturan pengeluaran (di luar R3).

## 12. Cara Mengerjakan dengan OpenCode

Kerjakan satu tahap per prompt:

```text
R3.1 Requirement
↓
R3.2 Database
↓
Review migration
↓
Admin membuat tagihan
↓
Pembayaran TU
↓
Validasi
↓
Kwitansi
↓
Rekap
↓
Testing
```

Setiap tahap:
1. cek perubahan;
2. jalankan test/cek aplikasi;
3. pastikan SPP lama tetap berjalan;
4. baru lanjut.

Instruksi umum untuk OpenCode:
- baca migration/model/controller/view existing terlebih dahulu;
- gunakan struktur existing jika masih sesuai;
- jangan membuat asumsi bisnis baru;
- jangan mengubah fitur di luar scope;
- jangan menghapus fitur yang sudah berjalan;
- tampilkan file yang diubah;
- jelaskan perubahan setelah selesai;
- jangan hardcode nominal;
- gunakan database sebagai sumber nominal tagihan.
