@extends('layouts.app')

@section('title', 'Tahun Ajaran | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Tahun Ajaran</h2>
            <p>Kelola periode akademik dan tentukan satu periode aktif.</p>
        </div>
        @if ($dapatMenyiapkanTahunAjaran)
            <form class="inline-form" method="POST" action="{{ route('master.tahun-ajaran.store') }}" data-confirm data-confirm-title="Siapkan tahun ajaran berikutnya?" data-confirm-message="Sistem akan membuat tahun ajaran {{ $tahunAjaranBerikutnya }} sebagai periode persiapan." data-confirm-submit="Siapkan Tahun Ajaran">
                @csrf
                <button class="button button-primary" type="submit">Siapkan Tahun Ajaran Berikutnya</button>
            </form>
        @elseif ($tahunAjaranAktif)
            <span class="reference-note">Tahun ajaran berikutnya sudah disiapkan.</span>
        @else
            <span class="reference-note">Belum ada tahun ajaran aktif.</span>
        @endif
    </div>

    @if (session('status'))
        <div class="flash-message">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="error-message">{{ session('error') }}</div>
    @endif

    <section class="data-card">
        <div class="card-header">
            <div>
                <h3>Daftar Tahun Ajaran</h3>
                <p>Periode aktif digunakan sebagai acuan operasional akademik.</p>
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tahun Ajaran</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tahunAjaran as $periode)
                        <tr>
                            <td class="text-mono">{{ $periode->tahun_ajaran }}</td>
                            <td class="text-muted">{{ $periode->tanggal_mulai->format('d/m/Y') }}</td>
                            <td class="text-muted">{{ $periode->tanggal_selesai->format('d/m/Y') }}</td>
                            <td>
                                <span class="status-badge {{ match ($periode->status) {
                                    'persiapan' => 'status-warning',
                                    'aktif' => 'status-active',
                                    default => 'status-inactive',
                                } }}">
                                    {{ match ($periode->status) {
                                        'persiapan' => 'Persiapan',
                                        'aktif' => 'Aktif',
                                        default => 'Ditutup',
                                    } }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if ($periode->isPersiapan())
                                    <a class="button button-secondary button-small" href="{{ route('master.tahun-ajaran.edit', $periode) }}">Edit</a>
                                    <form class="inline-form" method="POST" action="{{ route('master.tahun-ajaran.activate', $periode) }}" data-confirm data-confirm-title="Aktifkan tahun ajaran?" data-confirm-message="Masukkan password petugas untuk melanjutkan. Tahun ajaran aktif saat ini akan ditutup dan tidak dapat digunakan kembali." data-confirm-submit="Aktifkan" data-confirm-input-name="password" data-confirm-input-type="password" data-confirm-input-label="Password akun petugas" data-confirm-input-autocomplete="current-password">
                                        @csrf
                                        @method('PATCH')
                                        <button class="button button-primary button-small" type="submit">Aktifkan</button>
                                    </form>
                                    @if ($periode->siswa_kelas_count === 0 && $periode->tarif_spp_count === 0)
                                        <form class="inline-form" method="POST" action="{{ route('master.tahun-ajaran.destroy', $periode) }}" data-confirm data-confirm-title="Hapus tahun ajaran?" data-confirm-message="Tahun ajaran persiapan yang belum digunakan akan dihapus permanen." data-confirm-submit="Hapus Tahun Ajaran">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button button-danger button-small" type="submit">Hapus</button>
                                        </form>
                                    @else
                                        <span class="reference-note">Sudah digunakan</span>
                                    @endif
                                @elseif ($periode->isAktif())
                                    @if ($periode->siswa_kelas_count === 0 && $periode->tarif_spp_digunakan_count === 0)
                                        <form class="inline-form" method="POST" action="{{ route('master.tahun-ajaran.deactivate', $periode) }}" data-confirm data-confirm-title="Batalkan aktivasi tahun ajaran?" data-confirm-message="Tahun ajaran akan dikembalikan ke status persiapan. Fitur yang bergantung pada tahun ajaran aktif tidak akan berfungsi sampai ada tahun ajaran lain yang diaktifkan." data-confirm-submit="Batalkan Aktivasi">
                                            @csrf
                                            @method('PATCH')
                                            <button class="button button-secondary button-small" type="submit">Batalkan Aktivasi</button>
                                        </form>
                                    @else
                                        <span class="reference-note">Sudah digunakan</span>
                                    @endif
                                @else
                                    <span class="reference-note">Arsip</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="5">Belum ada data tahun ajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
