<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $idJenisSekaliBayar = DB::table('jenis_pembayaran')
            ->whereIn('kode_jenis', ['PTS', 'PAS'])
            ->pluck('id_jenis_pembayaran');

        if ($idJenisSekaliBayar->isNotEmpty()) {
            DB::table('tagihan_pembayaran')
                ->whereIn('id_jenis_pembayaran', $idJenisSekaliBayar)
                ->update([
                    'bisa_cicil' => false,
                    'minimal_dp' => 0,
                ]);
        }

        foreach (DB::table('tagihan_pembayaran as tagihan')
            ->join('jenis_pembayaran as jenis', 'jenis.id_jenis_pembayaran', '=', 'tagihan.id_jenis_pembayaran')
            ->whereIn('jenis.tipe_periode', ['semester', 'gelombang'])
            ->where('tagihan.kode_periode', 'tahunan')
            ->select([
                'tagihan.id_tagihan_pembayaran',
                'tagihan.periode_keterangan',
                'jenis.tipe_periode',
            ])
            ->cursor() as $tagihan) {
            $kodePeriode = $this->inferKodePeriode(
                $tagihan->tipe_periode,
                (string) ($tagihan->periode_keterangan ?? ''),
            );

            DB::table('tagihan_pembayaran')
                ->where('id_tagihan_pembayaran', $tagihan->id_tagihan_pembayaran)
                ->update([
                    'kode_periode' => $kodePeriode,
                    'periode_keterangan' => $kodePeriode === 'legacy'
                        ? ($tagihan->periode_keterangan ?: 'Periode legacy - perlu verifikasi')
                        : $this->labelPeriode($kodePeriode),
                ]);
        }
    }

    public function down(): void
    {
        // Data period snapshots must not be reverted because that would lose
        // confirmed historical classification.
    }

    private function inferKodePeriode(string $tipePeriode, string $keterangan): string
    {
        $keterangan = strtolower($keterangan);

        if ($tipePeriode === 'semester') {
            if (str_contains($keterangan, 'semester 1') || str_contains($keterangan, 'sem 1')) {
                return 'semester_1';
            }

            if (str_contains($keterangan, 'semester 2') || str_contains($keterangan, 'sem 2')) {
                return 'semester_2';
            }
        }

        if ($tipePeriode === 'gelombang') {
            foreach ([1, 2, 3] as $gelombang) {
                if (str_contains($keterangan, "gelombang {$gelombang}")) {
                    return "gelombang_{$gelombang}";
                }
            }
        }

        return 'legacy';
    }

    private function labelPeriode(string $kodePeriode): string
    {
        return [
            'semester_1' => 'Semester 1',
            'semester_2' => 'Semester 2',
            'gelombang_1' => 'Gelombang 1',
            'gelombang_2' => 'Gelombang 2',
            'gelombang_3' => 'Gelombang 3',
        ][$kodePeriode];
    }
};
