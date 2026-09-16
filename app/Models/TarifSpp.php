<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarifSpp extends Model
{
    protected $table = 'tarif_spp';

    protected $primaryKey = 'id_tarif';

    protected $fillable = [
        'id_tahun_ajaran',
        'tingkat',
        'nominal',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'nominal' => 'decimal:0',
        ];
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function tagihanSpp(): HasMany
    {
        return $this->hasMany(TagihanSpp::class, 'id_tarif', 'id_tarif');
    }
}
