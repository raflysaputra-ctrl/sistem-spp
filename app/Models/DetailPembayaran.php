<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPembayaran extends Model
{
    protected $table = 'detail_pembayaran';

    protected $primaryKey = 'id_detail_pembayaran';

    protected $fillable = [
        'id_pembayaran',
        'id_tagihan',
        'nominal_bayar',
    ];

    protected function casts(): array
    {
        return [
            'nominal_bayar' => 'decimal:0',
        ];
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'id_pembayaran', 'id_pembayaran');
    }

    public function tagihanSpp(): BelongsTo
    {
        return $this->belongsTo(TagihanSpp::class, 'id_tagihan', 'id_tagihan');
    }
}
