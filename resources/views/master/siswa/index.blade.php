@extends('layouts.app')

@section('title', 'Data Siswa | Sistem Pembayaran SPP')
@section('page-title', 'Master Data')

@section('content')
    <div class="page-header">
        <div>
            <h2>Daftar Siswa</h2>
            <p>Kelola data induk siswa dan lihat penempatan kelas tahun ajaran aktif.</p>
        </div>
        <span class="action-stack">
            <a class="button button-secondary" href="{{ route('master.siswa.nonaktif') }}">Siswa Nonaktif</a>
            <a class="button button-secondary" href="{{ route('master.siswa.import.form') }}">Import Siswa</a>
            <a class="button button-primary" href="{{ route('master.siswa.create') }}">Tambah Siswa</a>
        </span>
    </div>

    <section class="data-card">
        @if (session('status'))
            <div class="flash-message">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="error-message">{{ session('error') }}</div>
        @endif

        <form class="filter-bar" method="GET" action="{{ route('master.siswa.index') }}">
            <div class="filter-field" style="min-width: min(100%, 24rem); flex: 1;">
                <label for="cari">Cari Siswa</label>
                <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa">
            </div>
            <div class="filter-field">
                <label for="status_siswa">Status Siswa</label>
                <select id="status_siswa" name="status_siswa">
                    <option value="">Aktif dan Pindah</option>
                    @foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'pindah' => 'Pindah'] as $nilaiStatus => $namaStatus)
                        <option value="{{ $nilaiStatus }}" @selected(($filters['status_siswa'] ?? null) === $nilaiStatus)>{{ $namaStatus }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="id_jurusan">Jurusan</label>
                <select id="id_jurusan" name="id_jurusan">
                    <option value="">Semua Jurusan</option>
                    @foreach ($jurusan as $itemJurusan)
                        <option value="{{ $itemJurusan->id_jurusan }}" @selected(($filters['id_jurusan'] ?? null) === $itemJurusan->id_jurusan)>{{ $itemJurusan->nama_jurusan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="tingkat">Tingkat</label>
                <select id="tingkat" name="tingkat">
                    <option value="">Semua Tingkat</option>
                    @foreach ([1 => 'X', 2 => 'XI', 3 => 'XII'] as $nilaiTingkat => $namaTingkat)
                        <option value="{{ $nilaiTingkat }}" @selected(($filters['tingkat'] ?? null) === $nilaiTingkat)>{{ $namaTingkat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label for="rombel">Rombel</label>
                <input id="rombel" name="rombel" type="number" value="{{ $filters['rombel'] ?? '' }}" min="1" max="255" step="1" placeholder="Semua Rombel">
            </div>
            <div class="filter-field">
                <label>Tahun Ajaran Ditampilkan</label>
                <span class="reference-note">{{ $tahunAjaranAktif?->tahun_ajaran ?? 'Belum ada tahun ajaran aktif' }}</span>
            </div>
            <button class="button button-primary" type="submit">Terapkan Filter</button>
            @if (count($filters) > 0)
                <a class="button button-secondary" href="{{ route('master.siswa.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>NIPD</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>Angkatan</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $item)
                        @php($penempatanAktif = $item->siswaKelas->first())
                        <tr>
                            <td class="text-mono">{{ $item->nipd }}</td>
                            <td>{{ $item->nama_siswa }}</td>
                            <td>{{ $penempatanAktif?->kelas?->nama_kelas ?? '-' }}</td>
                            <td class="text-mono">{{ $penempatanAktif?->kelas?->jurusan?->kode_jurusan ?? '-' }}</td>
                            <td>{{ $item->angkatan }}</td>
                            <td>
                                <span class="status-badge {{ $item->status_siswa === 'aktif' ? 'status-active' : 'status-inactive' }}">{{ $item->status_siswa }}</span>
                            </td>
                            <td class="text-right">
                                <span class="action-stack">
                                     <a class="button button-secondary button-small" href="{{ route('status-spp.show', $item) }}">Status SPP</a>
                                     <a class="button button-secondary button-small" href="{{ route('master.siswa.edit', $item) }}">Edit</a>
                                      <form class="inline-form" method="POST" action="{{ route('master.siswa.nonaktifkan', $item) }}" data-confirm data-confirm-title="Non-aktifkan siswa?" data-confirm-message="Siswa akan disembunyikan dari daftar aktif. Riwayat kelas, tagihan, dan pembayaran tetap tersimpan." data-confirm-submit="Non-aktifkan Siswa" data-confirm-input-name="konfirmasi_nipd" data-confirm-input-label="Ketik NIPD {{ $item->nipd }} untuk melanjutkan" data-confirm-input-value="{{ $item->nipd }}">
                                           @csrf
                                           @method('PATCH')
                                           <button class="button button-danger button-small" type="submit">Non-aktifkan</button>
                                      </form>
                                      @if (! $item->memiliki_pembayaran && ! $item->memiliki_tagihan_lunas)
                                          <form class="inline-form" method="POST" action="{{ route('master.siswa.destroy', $item) }}" data-confirm data-confirm-title="Hapus permanen siswa?" data-confirm-message="Siswa, seluruh tagihan belum bayar, dan riwayat kelas akan dihapus permanen. Tindakan ini tidak dapat dibatalkan." data-confirm-submit="Hapus Permanen" data-confirm-input-name="konfirmasi_nipd" data-confirm-input-label="Ketik NIPD {{ $item->nipd }} untuk melanjutkan" data-confirm-input-value="{{ $item->nipd }}">
                                              @csrf
                                              @method('DELETE')
                                              <button class="button button-danger button-small" type="submit">Hapus Permanen</button>
                                          </form>
                                      @endif
                                 </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty-state" colspan="7">Tidak ada siswa yang sesuai dengan pencarian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($siswa->count() > 0)
            <div class="table-footer">
                <span>Menampilkan {{ $siswa->firstItem() }}-{{ $siswa->lastItem() }} dari {{ $siswa->total() }} siswa</span>
                @if ($siswa->hasPages())
                    <nav class="pagination-links" aria-label="Pagination daftar siswa">
                        @if ($siswa->onFirstPage())
                            <span>Sebelumnya</span>
                        @else
                            <a href="{{ $siswa->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                        @endif

                        @if ($siswa->hasMorePages())
                            <a href="{{ $siswa->nextPageUrl() }}" rel="next">Selanjutnya</a>
                        @else
                            <span>Selanjutnya</span>
                        @endif
                    </nav>
                @endif
            </div>
        @endif
    </section>
@endsection
