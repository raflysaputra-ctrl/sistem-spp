<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PembayaranNonSpp extends Model
{
    protected $table = 'pembayaran_non_spp';

    protected $primaryKey = 'id_pembayaran_non_spp';

    protected $fillable = [
        'no_kwitansi',
        'id_penerimaan',
        'id_tagihan_pembayaran',
        'id_siswa',
        'id_user',
        'tanggal_bayar',
        'nominal_bayar',
        'keterangan',
        'status',
        'alasan_pembatalan',
        'dibatalkan_oleh',
        'dibatalkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'datetime',
            'nominal_bayar' => 'decimal:0',
            'dibatalkan_pada' => 'datetime',
        ];
    }

    public function tagihanPembayaran(): BelongsTo
    {
        return $this->belongsTo(TagihanPembayaran::class, 'id_tagihan_pembayaran', 'id_tagihan_pembayaran');
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function detailPembayaranNonSpp(): HasMany
    {
        return $this->hasMany(DetailPembayaranNonSpp::class, 'id_pembayaran_non_spp', 'id_pembayaran_non_spp');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh', 'id_user');
    }

    public function getTotalBayarAttribute(): int
    {
        return (int) $this->nominal_bayar;
    }
}
