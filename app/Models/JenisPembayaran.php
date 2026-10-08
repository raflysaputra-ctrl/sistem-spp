<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPembayaran extends Model
{
    public const ATURAN_SEKALI_BAYAR = 'sekali_bayar';

    public const ATURAN_CICILAN = 'cicilan';

    public const TIPE_PERIODE_SEMESTER = 'semester';

    public const TIPE_PERIODE_GELOMBANG = 'gelombang';

    public const TIPE_PERIODE_TAHUNAN = 'tahunan';

    public const KODE_JENIS_SEKALI_BAYAR_TETAP = ['PTS', 'PAS'];

    public const KODE_JENIS_BIAYA_AWAL_MASUK = 'BIAYA_AWAL_MASUK';

    protected $table = 'jenis_pembayaran';

    protected $primaryKey = 'id_jenis_pembayaran';

    protected $fillable = [
        'kode_jenis',
        'nama_jenis',
        'target_tingkat',
        'aturan_pembayaran',
        'tipe_periode',
        'keterangan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'target_tingkat' => 'array',
        ];
    }

    public function tagihanPembayaran(): HasMany
    {
        return $this->hasMany(TagihanPembayaran::class, 'id_jenis_pembayaran', 'id_jenis_pembayaran');
    }

    public function bisaDicicil(): bool
    {
        return ! $this->wajibLunasSekaliBayar()
            && $this->aturan_pembayaran === self::ATURAN_CICILAN;
    }

    public function wajibLunasKarenaKodeTetap(): bool
    {
        return in_array(strtoupper($this->kode_jenis), self::KODE_JENIS_SEKALI_BAYAR_TETAP, true);
    }

    public function adalahBiayaAwalMasuk(): bool
    {
        return strtoupper($this->kode_jenis) === self::KODE_JENIS_BIAYA_AWAL_MASUK;
    }

    public function wajibLunasSekaliBayar(): bool
    {
        return $this->wajibLunasKarenaKodeTetap()
            || $this->aturan_pembayaran === self::ATURAN_SEKALI_BAYAR;
    }

    /**
     * @return array<string, string>
     */
    public function pilihanPeriode(): array
    {
        return match ($this->tipe_periode) {
            self::TIPE_PERIODE_SEMESTER => [
                'semester_1' => 'Semester 1',
                'semester_2' => 'Semester 2',
            ],
            self::TIPE_PERIODE_GELOMBANG => [
                'gelombang_1' => 'Gelombang 1',
                'gelombang_2' => 'Gelombang 2',
                'gelombang_3' => 'Gelombang 3',
            ],
            default => ['tahunan' => 'Satu kali per tahun ajaran'],
        };
    }

    public static function labelPeriode(string $kodePeriode): string
    {
        return [
            'semester_1' => 'Semester 1',
            'semester_2' => 'Semester 2',
            'gelombang_1' => 'Gelombang 1',
            'gelombang_2' => 'Gelombang 2',
            'gelombang_3' => 'Gelombang 3',
            'tahunan' => 'Satu kali per tahun ajaran',
        ][$kodePeriode] ?? $kodePeriode;
    }
}
