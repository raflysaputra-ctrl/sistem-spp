<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $primaryKey = 'id_pembayaran';

    protected $fillable = [
        'no_kwitansi',
        'id_siswa',
        'id_user',
        'tanggal_bayar',
        'total_bayar',
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
            'total_bayar' => 'decimal:0',
            'dibatalkan_pada' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function detailPembayaran(): HasMany
    {
        return $this->hasMany(DetailPembayaran::class, 'id_pembayaran', 'id_pembayaran');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh', 'id_user');
    }

    public function arsipKwitansi(): HasOne
    {
        return $this->hasOne(ArsipKwitansiSiswa::class, 'id_pembayaran', 'id_pembayaran');
    }
}
