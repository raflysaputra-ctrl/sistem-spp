# Product Requirements Document (PRD)
## Sistem Pembayaran SPP Sekolah

**Versi:** 1.0  
**Status:** Perencanaan  
**Platform:** Web  
**Framework:** Laravel  
**Database:** MySQL  
**Environment Pengembangan:** XAMPP  
**Pengguna Utama:** Petugas Tata Usaha (TU)

---

## 1. Latar Belakang

Sekolah membutuhkan aplikasi untuk membantu Petugas Tata Usaha mencatat pembayaran SPP siswa.

Pembayaran SPP tidak selalu dilakukan untuk bulan berjalan. Siswa dapat:

- membayar tunggakan bulan sebelumnya;
- membayar bulan berjalan;
- membayar bulan berikutnya;
- membayar beberapa bulan sekaligus dalam satu transaksi.

Sekolah juga membutuhkan bukti pembayaran berupa kwitansi dan rekap pembayaran.

---

## 2. Tujuan

Sistem bertujuan untuk:

1. Mempermudah pencatatan pembayaran SPP.
2. Memungkinkan pembayaran beberapa bulan sekaligus.
3. Mendukung pembayaran tunggakan.
4. Mendukung pembayaran bulan berikutnya.
5. Mencegah pembayaran ganda pada periode yang sama.
6. Menyediakan riwayat pembayaran siswa.
7. Menghasilkan kwitansi.
8. Menyediakan rekap pembayaran.
9. Menyimpan riwayat kelas siswa.
10. Menjaga histori tarif SPP per tahun ajaran.

---

## 3. Pengguna

### Petugas Tata Usaha

Petugas TU dapat:

- login;
- logout;
- mengelola data siswa;
- melihat data jurusan;
- melihat/mengelola kelas sesuai scope aplikasi;
- melihat/mengelola tahun ajaran;
- melihat/mengelola tarif SPP;
- mencari siswa berdasarkan NIPD atau nama;
- melihat tagihan siswa;
- mencatat pembayaran;
- memilih beberapa bulan dalam satu transaksi;
- melihat riwayat pembayaran;
- mencetak kwitansi;
- melihat rekap pembayaran.

Role tambahan selain Petugas TU: **TBD**

---

## 4. Struktur Akademik

Jurusan:

- PPLG
- DKV

Tingkat:

- Tingkat 1 / Kelas X
- Tingkat 2 / Kelas XI
- Tingkat 3 / Kelas XII

Rombel awal setiap jurusan dan tingkat:

- 1
- 2
- 3
- 4

Rombel tambahan dapat dibuat sesuai kebutuhan sekolah dengan nomor 1-255.

Contoh kelas:

- X PPLG 1
- X PPLG 2
- XI PPLG 3
- XII PPLG 4
- X DKV 1
- XI DKV 2
- XII DKV 4

---

## 5. Tarif SPP

| Tingkat | Tarif |
|---|---:|
| X / Tingkat 1 | Rp150.000 |
| XI / Tingkat 2 | Rp120.000 |
| XII / Tingkat 3 | Rp120.000 |

Tarif harus disimpan berdasarkan tahun ajaran.

Perubahan tarif pada tahun ajaran berikutnya tidak boleh mengubah histori tagihan atau pembayaran sebelumnya.

---

## 6. Tahun Ajaran

Data awal:

- Tahun ajaran: `2026/2027`
- Tanggal mulai: `2026-07-01`
- Tanggal selesai: `2027-06-30`
- Status: aktif

Sistem harus mendukung pergantian tahun ajaran.

---

## 7. Scope MVP

Fitur MVP:

1. Login Petugas TU.
2. Logout.
3. Dashboard.
4. Master data siswa.
5. Data jurusan.
6. Data kelas.
7. Tahun ajaran.
8. Tarif SPP.
9. Riwayat kelas siswa.
10. Tagihan SPP bulanan.
11. Pembayaran satu bulan.
12. Pembayaran multi-bulan.
13. Pembayaran tunggakan.
14. Pembayaran bulan berikutnya.
15. Status tagihan belum bayar/lunas.
16. Nomor kwitansi unik.
17. Kwitansi.
18. Riwayat pembayaran.
19. Rekap pembayaran.
20. Filter rekap.
21. Pencegahan pembayaran ganda.

---

## 8. Di Luar Scope MVP

Belum termasuk:

- payment gateway;
- QRIS;
- pembayaran online;
- akun orang tua selain portal wali tanpa login;
- aplikasi mobile;
- WhatsApp notification;
- SMS;
- email notification;
- integrasi bank;
- denda;
- diskon;
- beasiswa;
- cicilan parsial;
- integrasi sistem akademik.

Jika kemudian dibutuhkan: **TBD**

---

## 9. Data Siswa

Minimal:

- NIPD;
- nama siswa;
- jenis kelamin;
- angkatan;
- status siswa.

Status siswa:

- aktif;
- lulus;
- pindah.

NIPD harus unik.

Siswa dinonaktifkan menggunakan soft delete agar histori kelas, tagihan, dan pembayaran tetap tersedia. Petugas wajib mengetik ulang NIPD siswa sebagai konfirmasi sebelum dinonaktifkan. Hapus permanen hanya untuk koreksi salah input, jika siswa belum memiliki pembayaran dan belum memiliki tagihan lunas; tagihan belum bayar serta riwayat kelasnya ikut dihapus.

Kelas siswa tidak disimpan sebagai satu nilai permanen pada tabel siswa. Riwayat kelas disimpan per tahun ajaran.

---

## 10. Riwayat Kelas

Contoh:

```text
Ahmad
2026/2027 -> X PPLG 2
2027/2028 -> XI PPLG 2
2028/2029 -> XII PPLG 3
```

Satu siswa hanya boleh memiliki satu kelas dalam satu tahun ajaran.

Perubahan kelas tidak boleh menghapus histori sebelumnya.

---

## 11. Authentication

Login menggunakan:

- username;
- password.

Password harus di-hash menggunakan Laravel.

Halaman internal harus diproteksi dengan authentication middleware.

Reset password: **TBD**

Jumlah akun petugas: **TBD**

---

## 12. Dashboard

Dashboard menyediakan ringkasan sistem.

Detail widget final: **TBD**

Kandidat informasi yang relevan:

- jumlah siswa;
- jumlah transaksi;
- transaksi hari ini;
- total pembayaran pada periode tertentu;
- ringkasan status tagihan.

---

## 13. Tagihan SPP

Satu tagihan mewakili:

- satu siswa;
- satu bulan;
- satu tahun;
- satu nominal.

Contoh:

```text
Ahmad
Juli 2026
Rp150.000
Belum Bayar
```

Status:

- `belum_bayar`
- `lunas`

Satu siswa tidak boleh memiliki dua tagihan untuk bulan dan tahun yang sama.

---

## 14. Pembayaran

Alur dasar:

1. Petugas mencari siswa.
2. Sistem menampilkan tagihan siswa.
3. Petugas memilih satu atau beberapa tagihan.
4. Sistem menghitung total.
5. Petugas mengonfirmasi.
6. Sistem membuat transaksi pembayaran.
7. Sistem membuat detail pembayaran.
8. Tagihan yang dibayar berubah menjadi lunas.
9. Sistem membuat nomor kwitansi.
10. Kwitansi dapat ditampilkan dan dicetak.

---

## 15. Pembayaran Bulan Sebelumnya

Contoh:

Tanggal transaksi:

`15 Agustus 2026`

Periode SPP:

`Juli 2026`

Sistem harus menyimpan kedua data tersebut secara terpisah.

---

## 16. Pembayaran Bulan Berikutnya

Petugas boleh memilih periode setelah bulan berjalan.

Batas maksimal pembayaran ke depan: **TBD**

---

## 17. Pembayaran Multi-Bulan

Contoh:

```text
Juli 2026        Rp150.000
Agustus 2026     Rp150.000
September 2026   Rp150.000
Total            Rp450.000
```

Semua periode tersebut dapat berada dalam satu transaksi/kwitansi.

---

## 18. Pencegahan Pembayaran Ganda

Tagihan yang sudah lunas tidak boleh dibayar kembali.

Pencegahan dilakukan pada:

- validasi aplikasi;
- unique constraint database.

---

## 19. Kwitansi

Kwitansi minimal menampilkan:

- nomor kwitansi;
- tanggal pembayaran;
- NIPD;
- nama siswa;
- kelas;
- periode SPP yang dibayar;
- nominal per periode;
- total;
- petugas TU.

Format nomor kwitansi final: **TBD**

PDF: **TBD**

Ukuran/template cetak: **TBD**

---

## 20. Riwayat Pembayaran

Riwayat pembayaran harus tetap tersedia meskipun:

- siswa naik kelas;
- tarif berubah;
- tahun ajaran berubah.

Minimal menampilkan:

- nomor kwitansi;
- tanggal pembayaran;
- periode SPP;
- nominal;
- total;
- petugas.

---

## 21. Rekap Pembayaran

Filter:

- bulan;
- tahun;
- jurusan;
- tingkat;
- rombel;
- siswa.

Periode SPP dan tanggal transaksi harus dapat dibedakan.

Contoh:

Siswa membayar SPP Juli pada Agustus. Data tersebut tetap masuk ke rekap periode Juli jika filter berdasarkan periode SPP.

Export Excel/PDF: **TBD**

---

## 22. Aturan Bisnis

### BR-01
NIPD siswa harus unik.

### BR-02
Satu siswa hanya boleh memiliki satu kelas dalam satu tahun ajaran.

### BR-03
Kombinasi jurusan, tingkat, dan rombel harus unik.

### BR-04
Satu kombinasi tahun ajaran dan tingkat hanya memiliki satu tarif.

### BR-05
Tingkat 1 / X memiliki tarif Rp150.000.

### BR-06
Tingkat 2 / XI memiliki tarif Rp120.000.

### BR-07
Tingkat 3 / XII memiliki tarif Rp120.000.

### BR-08
Pembayaran bulan sebelumnya diperbolehkan.

### BR-09
Pembayaran bulan berikutnya diperbolehkan.

### BR-10
Satu transaksi boleh membayar beberapa tagihan.

### BR-11
Satu tagihan hanya boleh dilunasi sekali.

### BR-12
Nomor kwitansi harus unik.

### BR-13
Petugas yang melakukan transaksi harus dicatat.

### BR-14
Nominal tagihan lama tidak boleh berubah jika tarif berubah.

### BR-15
Riwayat kelas lama tidak boleh hilang.

### BR-16
Periode SPP dan tanggal transaksi adalah data berbeda.

### BR-17
Transaksi pembayaran harus atomic.

### BR-18
Jika proses pembayaran gagal di tengah, seluruh perubahan harus rollback.

### BR-19
Pembayaran dapat dibatalkan satu kali oleh pengguna yang sudah login dengan alasan dan password yang benar. Pembatalan tidak menghapus histori pembayaran maupun detail pembayaran, tetapi mengembalikan seluruh tagihan transaksi menjadi `belum_bayar`.

### BR-20
Penerimaan aktif, dashboard, dan total rekap tidak menghitung transaksi berstatus `dibatalkan`.

---

## 23. Kebutuhan Fungsional

- FR-01 Login petugas.
- FR-02 Logout petugas.
- FR-03 CRUD/kelola data siswa sesuai kebutuhan.
- FR-04 Penyimpanan jurusan.
- FR-05 Penyimpanan kelas.
- FR-06 Penyimpanan tahun ajaran.
- FR-07 Riwayat kelas.
- FR-08 Tarif SPP per tingkat dan tahun ajaran.
- FR-09 Tagihan bulanan siswa.
- FR-10 Menampilkan tagihan belum lunas.
- FR-11 Pembayaran satu tagihan.
- FR-12 Pembayaran beberapa tagihan.
- FR-13 Pembayaran tunggakan.
- FR-14 Pembayaran bulan berikutnya.
- FR-15 Hitung total otomatis.
- FR-16 Update tagihan menjadi lunas.
- FR-17 Catat tanggal lunas.
- FR-18 Nomor kwitansi unik.
- FR-19 Preview kwitansi.
- FR-20 Print kwitansi.
- FR-21 Riwayat pembayaran.
- FR-22 Rekap pembayaran.
- FR-23 Filter rekap.
- FR-24 Catat petugas transaksi.
- FR-25 Cegah pembayaran ganda.
- FR-26 Nonaktifkan siswa menggunakan soft delete dengan konfirmasi NIPD; hapus permanen hanya untuk siswa tanpa pembayaran dan tanpa tagihan lunas.
- FR-27 Aksi penting pada master data menampilkan konfirmasi sebelum diproses.

---

## 24. Kebutuhan Nonfungsional

### Portal Wali dan Siswa

- Portal wali dapat diakses tanpa login menggunakan NIPD siswa.
- Portal wali hanya menampilkan nama, kelas, periode tagihan yang tersedia, status pembayaran, dan tanggal bayar dari transaksi aktif.
- Portal wali tidak menampilkan nominal, nomor kwitansi, foto, atau tautan login petugas.
- Akun siswa dibuat dan passwordnya direset oleh Petugas TU; tidak ada pendaftaran publik.
- Login Petugas TU dan siswa memakai guard session terpisah agar dapat aktif bersamaan pada browser yang sama; logout hanya mengakhiri guard yang sesuai.
- Siswa hanya dapat melihat status SPP miliknya dan mengunggah foto kwitansi fisik miliknya.
- Setiap unggahan baru foto kwitansi wajib ditautkan ke satu transaksi pembayaran aktif milik siswa dan satu transaksi hanya memiliki satu foto; siswa dapat mengganti foto pada transaksi aktif tersebut, sedangkan arsip lama tanpa transaksi tetap dipertahankan.
- Unggahan foto tidak mengubah status tagihan, pembayaran, detail pembayaran, atau tanggal lunas.
- Foto hanya boleh diakses Petugas TU dari arsip privat; tidak boleh tersedia melalui URL publik.
- Foto hanya menerima JPEG atau PNG, maksimal 2 MB per unggahan, dan dikompresi sebelum disimpan.

### Security

- password di-hash;
- route internal menggunakan auth middleware;
- input divalidasi;
- gunakan Eloquent/Query Builder;
- pembayaran menggunakan database transaction.

### Data Integrity

Gunakan:

- primary key;
- foreign key;
- unique constraint;
- index sesuai kebutuhan.

### Reliability

Tidak boleh terjadi kondisi pembayaran tersimpan sebagian.

### Performance

Target spesifik: **TBD**

### Compatibility

Browser/perangkat target: **TBD**

---

## 25. Acceptance Criteria MVP

MVP dianggap memenuhi kebutuhan apabila:

1. Petugas dapat login dan logout.
2. Petugas dapat menambahkan serta mencari siswa.
3. Siswa dapat ditempatkan pada kelas per tahun ajaran.
4. Sistem mengetahui tarif berdasarkan tingkat dan tahun ajaran.
5. Sistem menampilkan tagihan siswa.
6. Tunggakan dapat dibayar.
7. Bulan berjalan dapat dibayar.
8. Bulan berikutnya dapat dibayar.
9. Beberapa bulan dapat dibayar dalam satu transaksi.
10. Total dihitung otomatis.
11. Periode yang sama tidak dapat dibayar dua kali.
12. Nomor kwitansi dihasilkan.
13. Kwitansi menampilkan transaksi dengan benar.
14. Riwayat pembayaran dapat dilihat.
15. Rekap dapat difilter.
16. Riwayat kelas tidak hilang setelah siswa naik kelas.
17. Perubahan tarif tidak mengubah histori lama.
18. Kegagalan proses pembayaran tidak menghasilkan data parsial.

---

## 26. TBD

1. Format nomor kwitansi.
2. Desain kwitansi.
3. PDF kwitansi.
4. Batas pembayaran bulan berikutnya.
5. Widget dashboard.
6. Hak CRUD data jurusan.
7. Reset password.
8. Jumlah akun petugas.
9. Frontend framework.
10. Target performa.
11. Browser minimum.
12. Export rekap.
13. Hosting production.
14. Backup database.
15. Audit trail tambahan.
