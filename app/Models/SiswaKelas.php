<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiswaKelas extends Model
{
    protected $table = 'siswa_kelas';

    protected $primaryKey = 'id_siswa_kelas';

    protected $fillable = [
        'id_siswa',
        'id_kelas',
        'id_tahun_ajaran',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa')->withTrashed();
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function tagihanSpp(): HasMany
    {
        return $this->hasMany(TagihanSpp::class, 'id_siswa_kelas', 'id_siswa_kelas');
    }
}
