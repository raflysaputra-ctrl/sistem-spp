<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

class ActivateTahunAjaranRequest extends FormRequest
{
    protected $dontFlash = [
        'password',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Password petugas wajib diisi untuk mengaktifkan tahun ajaran.',
            'password.string' => 'Password petugas tidak valid.',
            'password.max' => 'Password petugas tidak valid.',
        ];
    }
}
