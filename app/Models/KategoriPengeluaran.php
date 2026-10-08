<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPengeluaran extends Model
{
    protected $table = 'kategori_pengeluaran';

    protected $primaryKey = 'id_kategori_pengeluaran';

    protected $fillable = [
        'nama_kategori',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function pengeluaran(): HasMany
    {
        return $this->hasMany(Pengeluaran::class, 'id_kategori_pengeluaran', 'id_kategori_pengeluaran');
    }
}
