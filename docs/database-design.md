# Database Design
## Sistem Pembayaran SPP Sekolah

Database: `db_spp_sekolah`

DBMS: MySQL

---

## 1. Prinsip Desain

Database dirancang untuk mendukung:

- pembayaran bulan sebelumnya;
- pembayaran bulan berjalan;
- pembayaran bulan berikutnya;
- pembayaran beberapa bulan dalam satu transaksi;
- kwitansi;
- rekap;
- riwayat kelas;
- histori tarif;
- pencegahan pembayaran ganda.

Migration Laravel menjadi sumber utama schema database.

---

## 2. Tabel Utama

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

Tabel framework Laravel seperti `migrations`, `cache`, `jobs`, dan lainnya dapat tetap ada.

---

## 3. users

Primary key: `id_user`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_user | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| nama | VARCHAR(100) | wajib |
| username | VARCHAR(50) | wajib, UNIQUE |
| password | VARCHAR(255) | wajib |
| remember_token | VARCHAR(100) | nullable |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Relasi:

- satu user dapat mencatat banyak pembayaran.

---

## 4. jurusan

Primary key: `id_jurusan`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_jurusan | BIGINT UNSIGNED | PK |
| kode_jurusan | VARCHAR(10) | UNIQUE |
| nama_jurusan | VARCHAR(50) | wajib |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Data awal:

- PPLG
- DKV

Relasi:

- satu jurusan memiliki banyak kelas.

---

## 5. tahun_ajaran

Primary key: `id_tahun_ajaran`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_tahun_ajaran | BIGINT UNSIGNED | PK |
| tahun_ajaran | VARCHAR(9) | UNIQUE |
| tanggal_mulai | DATE | wajib |
| tanggal_selesai | DATE | wajib |
| aktif | BOOLEAN | default false |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Relasi:

- satu tahun ajaran memiliki banyak data `siswa_kelas`;
- satu tahun ajaran memiliki banyak `tarif_spp`.

---

## 6. kelas

Primary key: `id_kelas`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_kelas | BIGINT UNSIGNED | PK |
| id_jurusan | BIGINT UNSIGNED | FK |
| tingkat | TINYINT UNSIGNED | 1-3 |
| rombel | TINYINT UNSIGNED | 1-255 |
| nama_kelas | VARCHAR(30) | wajib |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Constraint:

```text
UNIQUE(id_jurusan, tingkat, rombel)
```

Foreign key:

```text
kelas.id_jurusan -> jurusan.id_jurusan
```

Relasi:

- belongsTo `jurusan`;
- hasMany `siswa_kelas`.

---

## 7. siswa

Primary key: `id_siswa`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_siswa | BIGINT UNSIGNED | PK |
| nipd | VARCHAR(30) | UNIQUE |
| nama_siswa | VARCHAR(100) | wajib |
| jenis_kelamin | ENUM('L','P') | wajib |
| angkatan | YEAR | wajib |
| status_siswa | ENUM('aktif','lulus','pindah') | default aktif |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |
| deleted_at | TIMESTAMP | nullable, soft delete |

Relasi:

- hasMany `siswa_kelas`;
- hasMany `tagihan_spp`;
- hasMany `pembayaran`.

Catatan:

`id_kelas` tidak disimpan langsung di tabel siswa karena kelas berubah setiap tahun ajaran.

Siswa dinonaktifkan menggunakan soft delete agar histori kelas, tagihan, dan pembayaran tidak terhapus. Hapus permanen hanya diperbolehkan untuk koreksi salah input tanpa pembayaran, detail pembayaran, atau tagihan lunas; tagihan belum bayar dan riwayat kelasnya dihapus bersama siswa.

---

## 8. siswa_kelas

Primary key: `id_siswa_kelas`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_siswa_kelas | BIGINT UNSIGNED | PK |
| id_siswa | BIGINT UNSIGNED | FK |
| id_kelas | BIGINT UNSIGNED | FK |
| id_tahun_ajaran | BIGINT UNSIGNED | FK |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Constraint:

```text
UNIQUE(id_siswa, id_tahun_ajaran)
```

Foreign key:

```text
siswa_kelas.id_siswa -> siswa.id_siswa
siswa_kelas.id_kelas -> kelas.id_kelas
siswa_kelas.id_tahun_ajaran -> tahun_ajaran.id_tahun_ajaran
```

Tujuan:

- menyimpan histori kelas siswa per tahun ajaran.

---

## 9. tarif_spp

Primary key: `id_tarif`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_tarif | BIGINT UNSIGNED | PK |
| id_tahun_ajaran | BIGINT UNSIGNED | FK |
| tingkat | TINYINT UNSIGNED | 1-3 |
| nominal | DECIMAL(12,0) UNSIGNED | > 0 |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Constraint:

```text
UNIQUE(id_tahun_ajaran, tingkat)
```

Foreign key:

```text
tarif_spp.id_tahun_ajaran -> tahun_ajaran.id_tahun_ajaran
```

Data awal 2026/2027:

```text
tingkat 1 -> 150000
tingkat 2 -> 120000
tingkat 3 -> 120000
```

---

## 10. tagihan_spp

Primary key: `id_tagihan`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_tagihan | BIGINT UNSIGNED | PK |
| id_siswa | BIGINT UNSIGNED | FK |
| id_siswa_kelas | BIGINT UNSIGNED | FK |
| id_tarif | BIGINT UNSIGNED | FK |
| bulan | TINYINT UNSIGNED | 1-12 |
| tahun | YEAR | wajib |
| nominal | DECIMAL(12,0) UNSIGNED | > 0 |
| status | ENUM('belum_bayar','lunas') | default belum_bayar |
| tanggal_lunas | DATETIME | nullable |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Constraint:

```text
UNIQUE(id_siswa, bulan, tahun)
```

Foreign key:

```text
tagihan_spp.id_siswa -> siswa.id_siswa
tagihan_spp.id_siswa_kelas -> siswa_kelas.id_siswa_kelas
tagihan_spp.id_tarif -> tarif_spp.id_tarif
```

Catatan penting:

`nominal` tetap disimpan di tagihan walaupun tarif tersedia pada `tarif_spp`.

Tujuannya adalah membuat snapshot nominal pada saat tagihan dibuat sehingga histori tidak berubah ketika tarif diubah di masa depan.

---

## 11. pembayaran

Primary key: `id_pembayaran`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_pembayaran | BIGINT UNSIGNED | PK |
| no_kwitansi | VARCHAR(50) | UNIQUE |
| id_siswa | BIGINT UNSIGNED | FK |
| id_user | BIGINT UNSIGNED | FK |
| tanggal_bayar | DATETIME | wajib |
| total_bayar | DECIMAL(12,0) UNSIGNED | > 0 |
| keterangan | VARCHAR(255) | nullable |
| status | ENUM('aktif','dibatalkan') | default aktif |
| alasan_pembatalan | VARCHAR(255) | nullable |
| dibatalkan_oleh | BIGINT UNSIGNED | nullable, FK ke users |
| dibatalkan_pada | DATETIME | nullable |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Foreign key:

```text
pembayaran.id_siswa -> siswa.id_siswa
pembayaran.id_user -> users.id_user
pembayaran.dibatalkan_oleh -> users.id_user
```

Satu `pembayaran` mewakili satu transaksi/kwitansi.

---

## 12. detail_pembayaran

Primary key: `id_detail_pembayaran`

Kolom:

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_detail_pembayaran | BIGINT UNSIGNED | PK |
| id_pembayaran | BIGINT UNSIGNED | FK |
| id_tagihan | BIGINT UNSIGNED | FK, indexed |
| nominal_bayar | DECIMAL(12,0) UNSIGNED | > 0 |
| created_at | TIMESTAMP | nullable |
| updated_at | TIMESTAMP | nullable |

Constraint:

```text
INDEX(id_tagihan)
```

Foreign key:

```text
detail_pembayaran.id_pembayaran -> pembayaran.id_pembayaran
detail_pembayaran.id_tagihan -> tagihan_spp.id_tagihan
```

Riwayat detail pembayaran dipertahankan ketika transaksi dibatalkan. Pencegahan pembayaran ganda aktif dilakukan oleh status tagihan, locking, dan pemeriksaan detail yang hanya berasal dari pembayaran berstatus `aktif`.

---

## 13. Relasi Eloquent yang Diharapkan

### User

```text
User hasMany Pembayaran
User belongsTo Siswa sebagai akun siswa bila role = siswa
```

### Jurusan

```text
Jurusan hasMany Kelas
```

### TahunAjaran

```text
TahunAjaran hasMany SiswaKelas
TahunAjaran hasMany TarifSpp
```

### Kelas

```text
Kelas belongsTo Jurusan
Kelas hasMany SiswaKelas
```

### Siswa

```text
Siswa hasMany SiswaKelas
Siswa hasMany TagihanSpp
Siswa hasMany Pembayaran
Siswa hasOne User sebagai akun siswa
Siswa hasMany ArsipKwitansiSiswa
```

### SiswaKelas

```text
SiswaKelas belongsTo Siswa
SiswaKelas belongsTo Kelas
SiswaKelas belongsTo TahunAjaran
SiswaKelas hasMany TagihanSpp
```

### TarifSpp

```text
TarifSpp belongsTo TahunAjaran
TarifSpp hasMany TagihanSpp
```

### TagihanSpp

```text
TagihanSpp belongsTo Siswa
TagihanSpp belongsTo SiswaKelas
TagihanSpp belongsTo TarifSpp
TagihanSpp hasMany DetailPembayaran
```

### Pembayaran

```text
Pembayaran belongsTo Siswa
Pembayaran belongsTo User
Pembayaran hasMany DetailPembayaran
Pembayaran hasMany ArsipKwitansiSiswa
```

### DetailPembayaran

```text
DetailPembayaran belongsTo Pembayaran
DetailPembayaran belongsTo TagihanSpp
```

### ArsipKwitansiSiswa

```text
ArsipKwitansiSiswa belongsTo Siswa
ArsipKwitansiSiswa belongsTo Pembayaran (nullable)
```

---

## 14. Ekstensi Portal Wali dan Siswa

### users

Kolom tambahan:

| Kolom | Tipe | Keterangan |
|---|---|---|
| role | ENUM('petugas','siswa') | default petugas |
| id_siswa | BIGINT UNSIGNED | nullable, UNIQUE, FK ke siswa untuk akun siswa |

### arsip_kwitansi_siswa

Primary key: `id_arsip_kwitansi`

| Kolom | Tipe | Keterangan |
|---|---|---|
| id_arsip_kwitansi | BIGINT UNSIGNED | PK |
| id_siswa | BIGINT UNSIGNED | FK |
| id_pembayaran | BIGINT UNSIGNED | nullable, FK |
| path | VARCHAR(255) | path pada storage privat, UNIQUE |
| mime_type | VARCHAR(50) | JPEG atau PNG setelah kompresi |
| ukuran_file | INT UNSIGNED | ukuran hasil kompresi dalam byte |
| created_at | TIMESTAMP | waktu unggah |
| updated_at | TIMESTAMP | nullable |

Foto disimpan pada disk `local` Laravel yang berakar di `storage/app/private`. Tidak ada URL publik; file hanya disajikan oleh route yang diproteksi role Petugas TU.

---

## 15. Diagram Relasi Sederhana

```text
jurusan
  |
  +---< kelas
          |
          +---< siswa_kelas >--- siswa
                  |
                  +--- tahun_ajaran
                  |
                  +---< tagihan_spp >--- tarif_spp
                               |
                               +--- detail_pembayaran >--- pembayaran
                                                           |
                                                           +--- users
                                                           |
                                                           +--- siswa
```

---

## 16. Aturan Integritas

### Siswa

```text
nipd UNIQUE
```

### Kelas

```text
(id_jurusan, tingkat, rombel) UNIQUE
```

### Siswa Kelas

```text
(id_siswa, id_tahun_ajaran) UNIQUE
```

### Tarif

```text
(id_tahun_ajaran, tingkat) UNIQUE
```

### Tagihan

```text
(id_siswa, bulan, tahun) UNIQUE
```

### Pembayaran

```text
no_kwitansi UNIQUE
```

### Detail Pembayaran

```text
id_tagihan INDEX
```

---

## 17. Database Transaction Pembayaran

Proses pembayaran wajib menggunakan `DB::transaction()`.

Urutan logis:

1. Validasi siswa.
2. Ambil tagihan yang dipilih.
3. Lock/recheck tagihan.
4. Pastikan semua status `belum_bayar`.
5. Hitung total server-side.
6. Buat record `pembayaran`.
7. Buat `detail_pembayaran`.
8. Update semua `tagihan_spp` menjadi `lunas`.
9. Isi `tanggal_lunas`.
10. Commit.

Jika salah satu proses gagal:

```text
ROLLBACK
```

Tidak boleh ada transaksi parsial.

---

## 18. Index yang Relevan

Index dibutuhkan untuk:

- `siswa.nama_siswa`;
- `siswa.status_siswa`;
- `siswa.angkatan`;
- `tagihan_spp(tahun, bulan)`;
- `tagihan_spp.status`;
- `pembayaran.tanggal_bayar`;
- foreign key.

Tujuannya mendukung pencarian siswa dan rekap.

---

## 19. Seeder Awal

Seeder menyediakan:

### Jurusan

- PPLG
- DKV

### Tahun Ajaran

- 2026/2027

### Tarif

- Tingkat 1 = 150000
- Tingkat 2 = 120000
- Tingkat 3 = 120000

### Kelas

24 kombinasi:

- X PPLG 1-4
- XI PPLG 1-4
- XII PPLG 1-4
- X DKV 1-4
- XI DKV 1-4
- XII DKV 1-4

---

## 20. Model Naming

Model yang digunakan:

```text
User
Jurusan
TahunAjaran
Kelas
Siswa
SiswaKelas
TarifSpp
TagihanSpp
Pembayaran
DetailPembayaran
ArsipKwitansiSiswa
```

Karena primary key bukan `id`, setiap model harus mendefinisikan `$primaryKey`.

Contoh:

```php
protected $primaryKey = 'id_siswa';
```

Nama tabel juga sebaiknya ditentukan eksplisit untuk menghindari masalah pluralisasi.

---

## 21. TBD Database

Belum diputuskan:

- audit transaksi;
- histori perubahan transaksi;
- metode backup production;

Jangan menambahkannya sebelum kebutuhan disetujui.
