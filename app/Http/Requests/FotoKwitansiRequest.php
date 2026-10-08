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
            'id_pembayaran' => ['nullable', 'required_without:id_penerimaan', 'integer'],
            'id_penerimaan' => ['nullable', 'required_without:id_pembayaran', 'integer'],
            'foto' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
