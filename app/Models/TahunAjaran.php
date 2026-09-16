<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    protected $primaryKey = 'id_tahun_ajaran';

    protected $fillable = [
        'tahun_ajaran',
        'tanggal_mulai',
        'tanggal_selesai',
        'aktif',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'aktif' => 'boolean',
            'status' => 'string',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function isPersiapan(): bool
    {
        return $this->status === 'persiapan';
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function isTutup(): bool
    {
        return $this->status === 'ditutup';
    }

    public function siswaKelas(): HasMany
    {
        return $this->hasMany(SiswaKelas::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function tarifSpp(): HasMany
    {
        return $this->hasMany(TarifSpp::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }
}
