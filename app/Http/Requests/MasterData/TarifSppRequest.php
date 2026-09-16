<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifSppRequest extends FormRequest
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
        $tarifSpp = $this->route('tarifSpp');
        $kombinasiTarif = Rule::unique('tarif_spp', 'tingkat')
            ->where('id_tahun_ajaran', $this->input('id_tahun_ajaran'))
            ->ignore($tarifSpp?->id_tarif, 'id_tarif');

        return [
            'id_tahun_ajaran' => ['required', 'integer', Rule::exists('tahun_ajaran', 'id_tahun_ajaran')],
            'tingkat' => ['required', 'integer', Rule::in([1, 2, 3]), $kombinasiTarif],
            'nominal' => ['required', 'integer', 'min:1', 'max:999999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_tahun_ajaran.required' => 'Tahun ajaran wajib dipilih.',
            'id_tahun_ajaran.integer' => 'Tahun ajaran yang dipilih tidak valid.',
            'id_tahun_ajaran.exists' => 'Tahun ajaran yang dipilih tidak ditemukan.',
            'tingkat.required' => 'Tingkat wajib dipilih.',
            'tingkat.integer' => 'Tingkat yang dipilih tidak valid.',
            'tingkat.in' => 'Tingkat hanya dapat bernilai 1, 2, atau 3.',
            'tingkat.unique' => 'Tarif untuk tahun ajaran dan tingkat yang dipilih sudah tersedia.',
            'nominal.required' => 'Nominal tarif wajib diisi.',
            'nominal.integer' => 'Nominal tarif harus berupa angka bulat.',
            'nominal.min' => 'Nominal tarif minimal Rp 1.',
            'nominal.max' => 'Nominal tarif terlalu besar.',
        ];
    }
}
