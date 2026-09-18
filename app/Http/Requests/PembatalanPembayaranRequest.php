<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class PembatalanPembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan_pembatalan' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('password')) {
                return;
            }

            if (! Hash::check((string) $this->input('password'), $this->user()->password)) {
                $validator->errors()->add('password', 'Password yang dimasukkan tidak sesuai.');
            }
        }];
    }
}
