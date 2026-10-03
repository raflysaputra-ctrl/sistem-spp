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
            'password.required' => 'Password Admin wajib diisi untuk mengaktifkan tahun ajaran.',
            'password.string' => 'Password Admin tidak valid.',
            'password.max' => 'Password Admin tidak valid.',
        ];
    }
}
