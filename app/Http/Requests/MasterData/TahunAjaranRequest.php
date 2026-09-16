<?php

namespace App\Http\Requests\MasterData;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TahunAjaranRequest extends FormRequest
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
        if ($this->isLockedEdit()) {
            return [];
        }

        return [
            'tanggal_selesai' => [
                'required',
                'date',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        if ($this->isLockedEdit()) {
            return [];
        }

        return [function (Validator $validator): void {
            if (
                $validator->errors()->has('tanggal_selesai')
            ) {
                return;
            }

            $tahunAjaran = $this->route('tahunAjaran');

            if (Carbon::parse($this->input('tanggal_selesai'))->lte($tahunAjaran->tanggal_mulai)) {
                $validator->errors()->add('tanggal_selesai', 'Tanggal selesai harus setelah tanggal mulai.');
            }

        }];
    }

    private function isLockedEdit(): bool
    {
        $tahunAjaran = $this->route('tahunAjaran');

        return $this->isMethod('put') && ! $tahunAjaran?->isPersiapan();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.date' => 'Tanggal selesai tidak valid.',
        ];
    }
}
