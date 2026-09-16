<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JurusanRequest extends FormRequest
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
        $jurusan = $this->route('jurusan');

        return [
            'kode_jurusan' => [
                'required',
                'string',
                'max:10',
                Rule::unique('jurusan', 'kode_jurusan')->ignore($jurusan?->id_jurusan, 'id_jurusan'),
            ],
            'nama_jurusan' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_jurusan.required' => 'Kode jurusan wajib diisi.',
            'kode_jurusan.string' => 'Kode jurusan harus berupa teks.',
            'kode_jurusan.max' => 'Kode jurusan maksimal 10 karakter.',
            'kode_jurusan.unique' => 'Kode jurusan sudah digunakan.',
            'nama_jurusan.required' => 'Nama jurusan wajib diisi.',
            'nama_jurusan.string' => 'Nama jurusan harus berupa teks.',
            'nama_jurusan.max' => 'Nama jurusan maksimal 50 karakter.',
        ];
    }
}
