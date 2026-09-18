<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FotoKwitansiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_pembayaran' => ['nullable', 'integer'],
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
