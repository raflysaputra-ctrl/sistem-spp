<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PembayaranRequest extends FormRequest
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
        return [
            'id_tagihan' => ['required', 'array', 'min:1'],
            'id_tagihan.*' => ['required', 'integer', 'distinct', Rule::exists('tagihan_spp', 'id_tagihan')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_tagihan.required' => 'Pilih minimal satu tagihan untuk dibayar.',
            'id_tagihan.array' => 'Data tagihan yang dipilih tidak valid.',
            'id_tagihan.min' => 'Pilih minimal satu tagihan untuk dibayar.',
            'id_tagihan.*.required' => 'Tagihan yang dipilih tidak valid.',
            'id_tagihan.*.integer' => 'Tagihan yang dipilih tidak valid.',
            'id_tagihan.*.distinct' => 'Tagihan tidak boleh dipilih lebih dari satu kali.',
            'id_tagihan.*.exists' => 'Tagihan yang dipilih tidak ditemukan.',
        ];
    }
}
