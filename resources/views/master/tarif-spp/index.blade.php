@extends('layouts.app')

@section('title', 'Tarif SPP | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Tarif SPP</h2>
            <p>Daftar nominal SPP berdasarkan tahun ajaran dan tingkat kelas.</p>
        </div>
        <a class="button button-primary" href="{{ route('master.tarif-spp.create') }}">Tambah Tarif</a>
    </div>

    <section class="data-card">
        @if (session('status'))
            <div class="flash-message">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <form class="filter-bar" method="GET" action="{{ route('master.tarif-spp.index') }}">
            <div class="filter-field">
                <label for="id_tahun_ajaran">Tahun Ajaran</label>
                <select id="id_tahun_ajaran" name="id_tahun_ajaran" onchange="this.form.submit()">
                    @forelse ($tahunAjaran as $periode)
                        <option value="{{ $periode->id_tahun_ajaran }}" @selected($idTahunAjaran == $periode->id_tahun_ajaran)>
                            {{ $periode->tahun_ajaran }}{{ $periode->aktif ? ' (Aktif)' : '' }}
                        </option>
                    @empty
                        <option value="">Belum ada tahun ajaran</option>
                    @endforelse
                </select>
            </div>

        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tahun Ajaran</th>
                        <th>Tingkat</th>
                        <th>Nama Tingkat</th>
                        <th class="text-right">Nominal per Bulan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tarifSpp as $item)
                        <tr>
                            <td class="text-mono">{{ $item->tahunAjaran->tahun_ajaran }}</td>
                            <td>Tingkat {{ $item->tingkat }}</td>
                            <td class="text-muted">Kelas {{ [1 => 'X', 2 => 'XI', 3 => 'XII'][$item->tingkat] }}</td>
                            <td class="text-right text-mono">Rp {{ number_format((int) $item->nominal, 0, ',', '.') }}</td>
                            <td class="text-right">
                                @if ($item->tagihan_spp_count === 0)
                                    <a class="button button-secondary button-small" href="{{ route('master.tarif-spp.edit', $item) }}">Edit</a>
                                @else
                                    <span class="reference-note">Digunakan tagihan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="5">Belum ada tarif untuk tahun ajaran ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
