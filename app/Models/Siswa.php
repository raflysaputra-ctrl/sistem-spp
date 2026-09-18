<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
    use SoftDeletes;

    protected $table = 'siswa';

    protected $primaryKey = 'id_siswa';

    protected $fillable = [
        'nipd',
        'nama_siswa',
        'jenis_kelamin',
        'angkatan',
        'status_siswa',
    ];

    protected function casts(): array
    {
        return [
            'angkatan' => 'integer',
        ];
    }

    public function siswaKelas(): HasMany
    {
        return $this->hasMany(SiswaKelas::class, 'id_siswa', 'id_siswa');
    }

    public function tagihanSpp(): HasMany
    {
        return $this->hasMany(TagihanSpp::class, 'id_siswa', 'id_siswa');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'id_siswa', 'id_siswa');
    }

    public function akunSiswa(): HasOne
    {
        return $this->hasOne(User::class, 'id_siswa', 'id_siswa')->where('role', 'siswa');
    }

    public function arsipKwitansi(): HasMany
    {
        return $this->hasMany(ArsipKwitansiSiswa::class, 'id_siswa', 'id_siswa');
    }
}
