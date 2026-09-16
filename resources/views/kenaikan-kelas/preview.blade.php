@extends('layouts.app')

@section('title', 'Kenaikan Kelas | Sistem Pembayaran SPP')
@section('page-title', 'Kenaikan Kelas')

@section('content')
    <div class="page-header">
        <div>
            <h2>Preview Kenaikan Kelas</h2>
            <p>Tinjau siswa yang akan naik kelas atau lulus sebelum proses dijalankan.</p>
        </div>
        <a class="button button-secondary" href="{{ route('master.siswa.index') }}">Data Siswa</a>
    </div>

    @if (session('status'))
        <div class="flash-message">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="error-message">{{ session('error') }}</div>
    @endif

    @if ($error)
        <div class="error-message" role="alert">{{ $error }}</div>
    @elseif ($info)
        <div class="flash-message">{{ $info }}</div>
    @else
        <section class="form-card" style="max-width: none; margin-bottom: 1.5rem;">
            <div class="form-grid">
                <div class="form-field">
                    <label>Tahun Ajaran Asal</label>
                    <strong class="text-mono">{{ $tahunAjaranAsal->tahun_ajaran }}</strong>
                </div>
                <div class="form-field">
                    <label>Tahun Ajaran Tujuan Aktif</label>
                    <strong class="text-mono">{{ $tahunAjaranTujuan->tahun_ajaran }}</strong>
                </div>
            </div>
        </section>

        <section class="report-summary">
            @foreach (['Naik ke XI', 'Naik ke XII', 'Lulus'] as $kelompok)
                <article class="report-summary-item">
                    <span>{{ $kelompok }}</span>
                    <strong>{{ $ringkasan->get($kelompok, 0) }} siswa</strong>
                </article>
            @endforeach
        </section>

        <section class="data-card">
            <div class="card-header">
                <div>
                    <h3>Daftar Kandidat</h3>
                    <p>Cari dan centang siswa yang tetap aktif pada kelas asal di tahun ajaran tujuan.</p>
                </div>
            </div>

            <form id="candidate-filter-form" class="filter-bar" method="GET" action="{{ route('kenaikan-kelas.preview') }}">
                <div class="filter-field" style="min-width: min(100%, 24rem); flex: 1;">
                    <label for="cari">Cari Kandidat</label>
                    <input id="cari" name="cari" type="search" value="{{ $filters['cari'] ?? '' }}" placeholder="Cari NIPD atau nama siswa">
                </div>
                <button id="candidate-filter-reset" class="button button-secondary" type="button">Reset</button>
                <button class="button button-primary" type="submit">Cari</button>
            </form>

            <form id="kenaikan-kelas-form" method="POST" action="{{ route('kenaikan-kelas.proses') }}" data-confirm data-confirm-title="Proses kenaikan kelas?" data-confirm-message="Penempatan kelas baru, tagihan SPP, dan status kelulusan akan dibuat untuk siswa yang tidak dikecualikan." data-confirm-submit="Proses Kenaikan Kelas">
                @csrf
                <div id="selected-candidates">
                    @foreach ($idSiswaTetapKelas as $idSiswa)
                        <input name="tetap_di_kelas_asal[]" type="hidden" value="{{ $idSiswa }}">
                    @endforeach
                </div>

                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NIPD</th>
                                <th>Nama Siswa</th>
                                <th>Kelas Asal</th>
                                <th>Kelas Tujuan</th>
                                <th data-sortable="false">Tetap Kelas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kandidat as $item)
                                <tr>
                                    <td class="text-mono">{{ $item['nipd'] }}</td>
                                    <td>{{ $item['nama_siswa'] }}</td>
                                    <td>{{ $item['kelas_asal'] }}</td>
                                    <td>{{ $item['kelas_tujuan'] }}</td>
                                    <td>
                                        <label class="checkbox-field" style="margin-top: 0;">
                                            <input data-candidate-checkbox data-candidate-nipd="{{ $item['nipd'] }}" data-candidate-name="{{ $item['nama_siswa'] }}" data-candidate-class="{{ $item['kelas_asal'] }}" type="checkbox" value="{{ $item['id_siswa'] }}" @checked(in_array($item['id_siswa'], $idSiswaTetapKelas, true))>
                                            Tetap di {{ $item['kelas_asal'] }}
                                        </label>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="empty-state" colspan="5">Tidak ada kandidat yang sesuai dengan pencarian.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="form-actions" style="margin: 0; padding: 1rem 1.25rem;">
                    <button class="button button-primary" type="submit">Proses Kenaikan Kelas</button>
                </div>
            </form>

            @if ($kandidat->count() > 0)
                <div class="table-footer">
                    <span>Menampilkan {{ $kandidat->firstItem() }}-{{ $kandidat->lastItem() }} dari {{ $kandidat->total() }} kandidat</span>
                    @if ($kandidat->hasPages())
                        <nav id="candidate-pagination-links" class="pagination-links" aria-label="Pagination kandidat kenaikan kelas">
                            @if ($kandidat->onFirstPage())
                                <span>Sebelumnya</span>
                            @else
                                <a href="{{ $kandidat->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                            @endif

                            @if ($kandidat->hasMorePages())
                                <a href="{{ $kandidat->nextPageUrl() }}" rel="next">Selanjutnya</a>
                            @else
                                <span>Selanjutnya</span>
                            @endif
                        </nav>
                    @endif
                </div>
            @endif
        </section>

        <script id="selected-candidate-names" type="application/json">@json($kandidatTetapKelas)</script>
        <script>
            const selectedCandidates = document.getElementById('selected-candidates');
            const selectedIds = new Set([...selectedCandidates.querySelectorAll('input')].map((input) => input.value));
            const selectedCandidateNames = new Map(
                JSON.parse(document.getElementById('selected-candidate-names').textContent)
                    .map((candidate) => [String(candidate.id_siswa), candidate]),
            );

            function syncSelectedCandidates() {
                selectedCandidates.replaceChildren(...[...selectedIds].map((id) => {
                    const input = document.createElement('input');
                    input.name = 'tetap_di_kelas_asal[]';
                    input.type = 'hidden';
                    input.value = id;

                    return input;
                }));
            }

            document.querySelectorAll('[data-candidate-checkbox]').forEach((input) => {
                input.addEventListener('change', () => {
                    if (input.checked) {
                        selectedIds.add(input.value);
                        selectedCandidateNames.set(input.value, {
                            nipd: input.dataset.candidateNipd,
                            nama_siswa: input.dataset.candidateName,
                            kelas_asal: input.dataset.candidateClass,
                        });
                    } else {
                        selectedIds.delete(input.value);
                        selectedCandidateNames.delete(input.value);
                    }

                    syncSelectedCandidates();
                });
            });

            function preserveSelectedCandidates(form) {
                form.querySelectorAll('[data-selected-candidate]').forEach((input) => input.remove());
                selectedIds.forEach((id) => {
                    const input = document.createElement('input');
                    input.dataset.selectedCandidate = 'true';
                    input.name = 'tetap_di_kelas_asal[]';
                    input.type = 'hidden';
                    input.value = id;
                    form.append(input);
                });
            }

            function applySelectedCandidatesToUrl(url) {
                [...url.searchParams.keys()]
                    .filter((key) => key === 'tetap_di_kelas_asal' || /^tetap_di_kelas_asal\[\d*\]$/.test(key))
                    .forEach((key) => url.searchParams.delete(key));
                selectedIds.forEach((id) => url.searchParams.append('tetap_di_kelas_asal[]', id));

                return url;
            }

            const candidateFilterForm = document.getElementById('candidate-filter-form');
            const kenaikanKelasForm = document.getElementById('kenaikan-kelas-form');
            const confirmationDetailsTemplate = document.createElement('template');
            confirmationDetailsTemplate.dataset.confirmDetails = 'true';
            kenaikanKelasForm.append(confirmationDetailsTemplate);

            candidateFilterForm.addEventListener('submit', (event) => {
                preserveSelectedCandidates(event.currentTarget);
            });

            kenaikanKelasForm.addEventListener('submit', () => {
                confirmationDetailsTemplate.content.replaceChildren();

                if (selectedIds.size === 0) {
                    return;
                }

                const description = document.createElement('p');
                description.textContent = `${selectedIds.size} siswa akan tetap di kelas asal:`;
                const list = document.createElement('ul');

                selectedIds.forEach((id) => {
                    const item = document.createElement('li');
                    const candidate = selectedCandidateNames.get(id);
                    item.textContent = candidate
                        ? `NIPD: ${candidate.nipd} | Nama: ${candidate.nama_siswa} | Kelas: ${candidate.kelas_asal}`
                        : `Siswa ID ${id}`;
                    list.append(item);
                });

                confirmationDetailsTemplate.content.append(description, list);
            });

            document.getElementById('candidate-filter-reset').addEventListener('click', () => {
                document.getElementById('cari').value = '';
                candidateFilterForm.requestSubmit();
            });

            document.querySelectorAll('#candidate-pagination-links a').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    window.location.assign(applySelectedCandidatesToUrl(new URL(link.href)));
                });
            });
        </script>
    @endif
@endsection
