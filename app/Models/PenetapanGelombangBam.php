<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenetapanGelombangBam extends Model
{
    protected $table = 'penetapan_gelombang_bam';

    protected $primaryKey = 'id_penetapan_gelombang_bam';

    protected $fillable = [
        'id_siswa',
        'id_tahun_ajaran',
        'id_tagihan_pembayaran',
        'kode_periode',
        'ditetapkan_oleh',
        'ditetapkan_pada',
        'dikoreksi_oleh',
        'dikoreksi_pada',
        'alasan_koreksi',
    ];

    protected function casts(): array
    {
        return [
            'ditetapkan_pada' => 'datetime',
            'dikoreksi_pada' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function tagihanPembayaran(): BelongsTo
    {
        return $this->belongsTo(TagihanPembayaran::class, 'id_tagihan_pembayaran', 'id_tagihan_pembayaran');
    }

    public function ditetapkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditetapkan_oleh', 'id_user');
    }

    public function dikoreksiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh', 'id_user');
    }
}
