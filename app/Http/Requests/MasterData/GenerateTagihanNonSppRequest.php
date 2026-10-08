<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateTagihanNonSppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_jenis_pembayaran' => [
                'required',
                'integer',
                Rule::exists('jenis_pembayaran', 'id_jenis_pembayaran')->where('aktif', true),
            ],
            'id_tahun_ajaran' => ['required', 'integer', 'exists:tahun_ajaran,id_tahun_ajaran'],
            'tagihan' => ['required', 'array', 'min:1', 'max:3'],
            'tagihan.*.kode_periode' => ['required', 'string', 'max:20', 'distinct'],
            'tagihan.*.total_tagihan' => ['required', 'integer', 'min:1'],
            'tagihan.*.minimal_dp' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
