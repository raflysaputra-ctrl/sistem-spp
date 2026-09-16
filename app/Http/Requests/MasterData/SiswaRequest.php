<?php

namespace App\Http\Requests\MasterData;

use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nama_siswa')) {
            $this->merge([
                'nama_siswa' => $this->normalisasiNama($this->input('nama_siswa', '')),
            ]);
        }
    }

    /**
     * @return array<string, list<ValidationRule|array|string>>
     */
    public function rules(): array
    {
        $siswa = $this->route('siswa');
        $angkatanPilihan = $this->angkatanPilihan($siswa);

        return [
            'nipd' => ['required', 'string', 'max:30', Rule::unique('siswa', 'nipd')->ignore($siswa?->id_siswa, 'id_siswa')],
            'nama_siswa' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'angkatan' => ['required', 'integer', ...($angkatanPilihan ? [Rule::in($angkatanPilihan)] : [])],
            'status_siswa' => ['required', Rule::in(['aktif', 'lulus', 'pindah'])],
            'id_kelas' => [$this->isMethod('post') ? 'required' : 'nullable', 'integer', Rule::exists('kelas', 'id_kelas')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nipd.required' => 'NIPD wajib diisi.',
            'nipd.string' => 'NIPD harus berupa teks.',
            'nipd.max' => 'NIPD maksimal 30 karakter.',
            'nipd.unique' => 'NIPD sudah digunakan oleh siswa lain.',
            'nama_siswa.required' => 'Nama siswa wajib diisi.',
            'nama_siswa.string' => 'Nama siswa harus berupa teks.',
            'nama_siswa.max' => 'Nama siswa maksimal 100 karakter.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin yang dipilih tidak valid.',
            'angkatan.required' => 'Angkatan wajib dipilih.',
            'angkatan.integer' => 'Angkatan yang dipilih tidak valid.',
            'angkatan.in' => 'Angkatan harus sesuai dengan pilihan tahun ajaran aktif.',
            'status_siswa.required' => 'Status siswa wajib dipilih.',
            'status_siswa.in' => 'Status siswa yang dipilih tidak valid.',
            'id_kelas.required' => 'Kelas tahun ajaran aktif wajib dipilih.',
            'id_kelas.integer' => 'Kelas yang dipilih tidak valid.',
            'id_kelas.exists' => 'Kelas yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * @return list<int>
     */
    private function angkatanPilihan(?Siswa $siswa): array
    {
        $tahunAjaranAktif = TahunAjaran::query()->where('aktif', true)->first();

        if (! $tahunAjaranAktif) {
            return [];
        }

        $angkatanPilihan = range(
            $tahunAjaranAktif->tanggal_mulai->year,
            $tahunAjaranAktif->tanggal_mulai->year - 4,
        );

        if ($siswa && ! in_array($siswa->angkatan, $angkatanPilihan, true)) {
            $angkatanPilihan[] = $siswa->angkatan;
        }

        return $angkatanPilihan;
    }

    private function normalisasiNama(string $nama): string
    {
        $nama = trim($nama);
        $nama = preg_replace("/[^\p{L}\s.',-]/u", '', $nama) ?? '';
        $nama = preg_replace('/\s+/u', ' ', $nama) ?? '';
        $nama = mb_convert_case($nama, MB_CASE_TITLE, 'UTF-8');
        $kata = explode(' ', $nama);

        foreach ($kata as $index => $nilai) {
            if ($index > 0 && in_array(mb_strtolower($nilai, 'UTF-8'), ['bin', 'binti', 'van', 'de', 'el', 'al'], true)) {
                $kata[$index] = mb_strtolower($nilai, 'UTF-8');
            }
        }

        return implode(' ', $kata);
    }
}
