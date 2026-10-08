<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPembayaranNonSpp extends Model
{
    protected $table = 'detail_pembayaran_non_spp';

    protected $primaryKey = 'id_detail_pembayaran_non_spp';

    protected $fillable = [
        'id_pembayaran_non_spp',
        'id_tagihan_pembayaran',
        'nominal_bayar',
        'total_terbayar_setelah',
    ];

    protected function casts(): array
    {
        return [
            'nominal_bayar' => 'decimal:0',
            'total_terbayar_setelah' => 'decimal:0',
        ];
    }

    public function pembayaranNonSpp(): BelongsTo
    {
        return $this->belongsTo(PembayaranNonSpp::class, 'id_pembayaran_non_spp', 'id_pembayaran_non_spp');
    }

    public function tagihanPembayaran(): BelongsTo
    {
        return $this->belongsTo(TagihanPembayaran::class, 'id_tagihan_pembayaran', 'id_tagihan_pembayaran');
    }
}
