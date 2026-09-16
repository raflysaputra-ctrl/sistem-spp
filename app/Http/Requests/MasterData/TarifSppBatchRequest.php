<?php

namespace App\Http\Requests\MasterData;

use App\Models\TarifSpp;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TarifSppBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|array|string|Closure>>
     */
    public function rules(): array
    {
        return [
            'id_tahun_ajaran' => ['required', 'integer', Rule::exists('tahun_ajaran', 'id_tahun_ajaran')],
            'tarif' => ['required', 'array'],
            'tarif.1' => $this->nominalRules(1),
            'tarif.2' => $this->nominalRules(2),
            'tarif.3' => $this->nominalRules(3),
        ];
    }

    /**
     * @return list<ValidationRule|array|string|Closure>
     */
    private function nominalRules(int $tingkat): array
    {
        return [
            'required',
            'integer',
            'min:1',
            'max:999999999999',
            function (string $attribute, mixed $value, Closure $fail) use ($tingkat): void {
                if (TarifSpp::query()
                    ->where('id_tahun_ajaran', $this->input('id_tahun_ajaran'))
                    ->where('tingkat', $tingkat)
                    ->exists()) {
                    $fail('Tarif untuk tahun ajaran dan tingkat ini sudah tersedia.');
                }
            },
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
            'tarif.required' => 'Nominal tarif wajib diisi.',
            'tarif.array' => 'Data nominal tarif tidak valid.',
            'tarif.*.required' => 'Nominal tarif wajib diisi.',
            'tarif.*.integer' => 'Nominal tarif harus berupa angka bulat.',
            'tarif.*.min' => 'Nominal tarif minimal Rp 1.',
            'tarif.*.max' => 'Nominal tarif terlalu besar.',
        ];
    }
}
