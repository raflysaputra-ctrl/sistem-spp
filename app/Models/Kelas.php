<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $primaryKey = 'id_kelas';

    protected $fillable = [
        'id_jurusan',
        'tingkat',
        'rombel',
        'nama_kelas',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'rombel' => 'integer',
        ];
    }

    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan', 'id_jurusan');
    }

    public function siswaKelas(): HasMany
    {
        return $this->hasMany(SiswaKelas::class, 'id_kelas', 'id_kelas');
    }
}
