<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagihanSpp extends Model
{
    protected $table = 'tagihan_spp';

    protected $primaryKey = 'id_tagihan';

    protected $fillable = [
        'id_siswa',
        'id_siswa_kelas',
        'id_tarif',
        'bulan',
        'tahun',
        'nominal',
        'status',
        'tanggal_lunas',
    ];

    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
            'nominal' => 'decimal:0',
            'tanggal_lunas' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa')->withTrashed();
    }

    public function siswaKelas(): BelongsTo
    {
        return $this->belongsTo(SiswaKelas::class, 'id_siswa_kelas', 'id_siswa_kelas');
    }

    public function tarifSpp(): BelongsTo
    {
        return $this->belongsTo(TarifSpp::class, 'id_tarif', 'id_tarif');
    }

    public function detailPembayaran(): HasMany
    {
        return $this->hasMany(DetailPembayaran::class, 'id_tagihan', 'id_tagihan');
    }

    public function adalahTunggakan(): bool
    {
        $sekarang = now();

        return $this->status === 'belum_bayar'
            && ($this->tahun < $sekarang->year
                || ($this->tahun === $sekarang->year && $this->bulan < $sekarang->month));
    }
}
