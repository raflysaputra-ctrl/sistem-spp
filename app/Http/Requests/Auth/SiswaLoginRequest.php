<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SiswaLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $guard = Auth::guard('siswa');

        if (! $guard->attempt([...$this->only('username', 'password'), 'role' => 'siswa'])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'Username atau password tidak sesuai.',
            ]);
        }

        if (! $guard->user()->siswa || $guard->user()->siswa->status_siswa !== 'aktif') {
            $guard->logout();

            throw ValidationException::withMessages([
                'username' => 'Akun siswa tidak dapat digunakan.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'username' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.RateLimiter::availableIn($this->throttleKey()).' detik.',
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate('siswa|'.Str::lower((string) $this->input('username')).'|'.$this->ip());
    }
}
