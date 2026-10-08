<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengeluaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_kategori_pengeluaran' => ['required', 'integer', 'exists:kategori_pengeluaran,id_kategori_pengeluaran'],
            'tanggal_pengeluaran' => ['required', 'date'],
            'keterangan' => ['required', 'string', 'max:1000'],
            'nominal' => ['required', 'integer', 'min:1'],
        ];
    }
}
