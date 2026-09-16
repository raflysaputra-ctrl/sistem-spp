<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|array|string>>
     */
    public function rules(): array
    {
        $kelas = $this->route('kelas');
        $kombinasiKelas = Rule::unique('kelas', 'rombel')
            ->where('id_jurusan', $this->input('id_jurusan'))
            ->where('tingkat', $this->input('tingkat'))
            ->ignore($kelas?->id_kelas, 'id_kelas');

        return [
            'id_jurusan' => ['required', 'integer', Rule::exists('jurusan', 'id_jurusan')],
            'tingkat' => ['required', 'integer', Rule::in([1, 2, 3])],
            'rombel' => ['required', 'integer', 'min:1', 'max:255', $kombinasiKelas],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_jurusan.required' => 'Jurusan wajib dipilih.',
            'id_jurusan.integer' => 'Jurusan yang dipilih tidak valid.',
            'id_jurusan.exists' => 'Jurusan yang dipilih tidak ditemukan.',
            'tingkat.required' => 'Tingkat wajib dipilih.',
            'tingkat.integer' => 'Tingkat yang dipilih tidak valid.',
            'tingkat.in' => 'Tingkat hanya dapat bernilai 1, 2, atau 3.',
            'rombel.required' => 'Rombel wajib dipilih.',
            'rombel.integer' => 'Rombel yang dipilih tidak valid.',
            'rombel.min' => 'Rombel minimal bernilai 1.',
            'rombel.max' => 'Rombel maksimal bernilai 255.',
            'rombel.unique' => 'Kombinasi jurusan, tingkat, dan rombel sudah tersedia.',
        ];
    }
}
