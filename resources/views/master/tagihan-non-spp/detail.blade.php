@extends('layouts.app')

@section('title', 'Detail Tagihan Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Detail Tagihan Non-SPP')

@section('content')
    <div class="page-header">
        <div>
            <h2>{{ $jenisPembayaran->nama_jenis }} - {{ \App\Models\JenisPembayaran::labelPeriode($filters['kode_periode']) }}</h2>
            <p>{{ $tahunAjaran->tahun_ajaran }}. Tagihan dibuat massal dan nilai keuangan tidak dapat diubah setelah ada pembayaran.</p>
        </div>
        <a class="button button-secondary" href="{{ route('master.tagihan-non-spp.index', ['id_tahun_ajaran' => $tahunAjaran->id_tahun_ajaran]) }}">Kembali</a>
    </div>

    <section class="data-card">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIPD</th>
                        <th>Siswa</th>
                        <th class="text-right">Total Tagihan</th>
                        <th class="text-right">Sudah Dibayar</th>
                        <th class="text-right">Sisa</th>
                        <th>Status</th>
                        @if ($jenisPembayaran->adalahBiayaAwalMasuk())
                            <th>Penetapan Gelombang</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tagihanPembayaran as $item)
                        @php($totalDibayar = (int) ($item->total_dibayar_aktif ?? 0))
                        <tr>
                            <td class="text-mono">{{ $item->siswa->nipd }}</td>
                            <td>{{ $item->siswa->nama_siswa }}</td>
                            <td class="text-mono text-right">Rp {{ number_format($item->total_tagihan, 0, ',', '.') }}</td>
                            <td class="text-mono text-right">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</td>
                            <td class="text-mono text-right">Rp {{ number_format(max(0, (int) $item->total_tagihan - $totalDibayar), 0, ',', '.') }}</td>
                            <td>
                                <span class="status-badge {{ $item->status === 'lunas' ? 'status-lunas' : ($item->status === 'sebagian' ? 'status-warning' : ($item->status === 'tidak_berlaku' ? 'status-inactive' : 'status-belum-bayar')) }}">
                                    {{ $item->status === 'lunas' ? 'Lunas' : ($item->status === 'sebagian' ? 'Bayar Sebagian' : ($item->status === 'tidak_berlaku' ? 'Tidak Berlaku' : 'Belum Bayar')) }}
                                </span>
                            </td>
                            @if ($jenisPembayaran->adalahBiayaAwalMasuk())
                                @php($penetapan = $penetapanBamBySiswa->get($item->id_siswa))
                                <td>
                                    @if ($penetapan)
                                        <form method="POST" action="{{ route('master.tagihan-non-spp.gelombang-bam.koreksi', $item->siswa) }}" class="inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="id_tahun_ajaran" value="{{ $tahunAjaran->id_tahun_ajaran }}">
                                            <select name="kode_periode" aria-label="Gelombang BAM {{ $item->siswa->nama_siswa }}">
                                                @foreach (['gelombang_1' => 'Gelombang 1', 'gelombang_2' => 'Gelombang 2', 'gelombang_3' => 'Gelombang 3'] as $kode => $label)
                                                    <option value="{{ $kode }}" @selected($penetapan->kode_periode === $kode)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <input name="alasan_koreksi" maxlength="255" required placeholder="Alasan koreksi">
                                            <button class="button button-secondary" type="submit">Koreksi</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Belum ditetapkan TU</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td class="empty-state" colspan="{{ $jenisPembayaran->adalahBiayaAwalMasuk() ? 7 : 6 }}">Tidak ada tagihan untuk pilihan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tagihanPembayaran->hasPages())
            <div class="table-footer">{{ $tagihanPembayaran->links() }}</div>
        @endif
    </section>
@endsection
