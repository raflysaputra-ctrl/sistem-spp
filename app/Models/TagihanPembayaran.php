<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagihanPembayaran extends Model
{
    protected $table = 'tagihan_pembayaran';

    protected $primaryKey = 'id_tagihan_pembayaran';

    protected $fillable = [
        'id_siswa',
        'id_jenis_pembayaran',
        'id_tahun_ajaran',
        'total_tagihan',
        'minimal_dp',
        'bisa_cicil',
        'periode_keterangan',
        'kode_periode',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_tagihan' => 'decimal:0',
            'minimal_dp' => 'decimal:0',
            'bisa_cicil' => 'boolean',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa')->withTrashed();
    }

    public function jenisPembayaran(): BelongsTo
    {
        return $this->belongsTo(JenisPembayaran::class, 'id_jenis_pembayaran', 'id_jenis_pembayaran');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id_user');
    }

    public function pembayaranNonSpp(): HasMany
    {
        return $this->hasMany(PembayaranNonSpp::class, 'id_tagihan_pembayaran', 'id_tagihan_pembayaran');
    }

    public function detailPembayaranNonSpp(): HasMany
    {
        return $this->hasMany(DetailPembayaranNonSpp::class, 'id_tagihan_pembayaran', 'id_tagihan_pembayaran');
    }

    public function getTotalDibayarAttribute(): int
    {
        return (int) $this->detailPembayaranNonSpp()
            ->whereHas('pembayaranNonSpp', fn ($query) => $query->where('status', 'aktif'))
            ->sum('nominal_bayar');
    }

    public function getSisaTagihanAttribute(): int
    {
        return max(0, (int) $this->total_tagihan - $this->total_dibayar);
    }

    public function getPeriodeLabelAttribute(): string
    {
        if ($this->kode_periode === 'legacy' && $this->periode_keterangan) {
            return $this->periode_keterangan;
        }

        return JenisPembayaran::labelPeriode($this->kode_periode);
    }
}
