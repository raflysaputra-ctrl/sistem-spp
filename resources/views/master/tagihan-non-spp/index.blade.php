@extends('layouts.app')

@section('title', 'Tagihan Non-SPP | Sistem Informasi Keuangan')
@section('page-title', 'Tagihan Non-SPP')

@section('content')
    <div class="page-header">
        <div>
            <h2>Rekap Tagihan Non-SPP</h2>
            <p>Ringkasan tagihan yang telah digenerate massal per jenis pembayaran dan tahun ajaran.</p>
        </div>
        <a class="button button-primary" href="{{ route('master.tagihan-non-spp.create') }}">Atur & Generate Tagihan</a>
    </div>

    @if (session('status'))
        <div class="flash-message">{{ session('status') }}</div>
    @endif

    @if (session('warning'))
        <div style="margin-bottom: 1rem; padding: .75rem 1rem; border: 1px solid #ffb4ab; border-radius: .25rem; background: #ffdad6; color: #93000a; font-size: .88rem; white-space: pre-wrap; line-height: 1.6;">
            {{ session('warning') }}
        </div>
    @endif

    <section class="data-card">
        <form class="filter-bar" method="GET" action="{{ route('master.tagihan-non-spp.index') }}">
            <div class="filter-field">
                <label for="id_tahun_ajaran">Tahun Ajaran</label>
                <select id="id_tahun_ajaran" name="id_tahun_ajaran" onchange="this.form.submit()">
                    @foreach ($tahunAjaran as $ta)
                        <option value="{{ $ta->id_tahun_ajaran }}" @selected($idTahunAjaran == $ta->id_tahun_ajaran)>
                            {{ $ta->tahun_ajaran }} {{ $ta->aktif ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="button button-primary" type="submit">Filter</button></noscript>
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Jenis Pembayaran</th>
                        <th>Tahun Ajaran</th>
                        <th>Periode</th>
                        <th>Target Kelas</th>
                        <th class="text-right">Jumlah Siswa</th>
                        <th class="text-right">Biaya Tagihan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tagihanSummary as $item)
                        <tr>
                            <td><strong>{{ $item->jenisPembayaran->nama_jenis }}</strong></td>
                            <td>{{ $item->tahunAjaran->tahun_ajaran }}</td>
                            <td>{{ \App\Models\JenisPembayaran::labelPeriode($item->kode_periode) }}</td>
                            <td>
                                @if (empty($item->jenisPembayaran->target_tingkat))
                                    <span class="text-muted">Semua Kelas</span>
                                @else
                                    {{ implode(', ', array_map(fn($t) => "Kelas $t", $item->jenisPembayaran->target_tingkat)) }}
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($item->jumlah_tagihan, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item->biaya_tagihan, 0, ',', '.') }}</td>
                            <td class="text-right">
                                <a class="button button-secondary button-small" href="{{ route('master.tagihan-non-spp.detail', [
                                    'id_jenis_pembayaran' => $item->id_jenis_pembayaran,
                                    'id_tahun_ajaran' => $item->id_tahun_ajaran,
                                    'kode_periode' => $item->kode_periode,
                                ]) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="7">Belum ada tagihan non-SPP yang digenerate untuk tahun ajaran ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
