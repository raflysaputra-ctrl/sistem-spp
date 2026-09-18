<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArsipKwitansiSiswa extends Model
{
    protected $table = 'arsip_kwitansi_siswa';

    protected $primaryKey = 'id_arsip_kwitansi';

    protected $fillable = [
        'id_siswa',
        'id_pembayaran',
        'path',
        'mime_type',
        'ukuran_file',
    ];

    protected function casts(): array
    {
        return [
            'ukuran_file' => 'integer',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa')->withTrashed();
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'id_pembayaran', 'id_pembayaran');
    }
}
