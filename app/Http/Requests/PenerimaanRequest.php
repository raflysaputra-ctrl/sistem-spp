<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PenerimaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_tagihan_spp' => ['nullable', 'array'],
            'id_tagihan_spp.*' => ['integer', 'distinct', Rule::exists('tagihan_spp', 'id_tagihan')],
            'items' => ['nullable', 'array'],
            'items.*.id_tagihan_pembayaran' => ['required', 'integer', 'exists:tagihan_pembayaran,id_tagihan_pembayaran'],
            'items.*.selected' => ['nullable', 'boolean'],
            'items.*.nominal_bayar' => ['nullable', 'integer', 'min:1'],
            'kode_periode_bam' => ['nullable', 'in:gelombang_1,gelombang_2,gelombang_3'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $tagihanSpp = collect($this->validated('id_tagihan_spp', []));
            $tagihanNonSpp = $this->selectedNonSppItems();

            if ($tagihanSpp->isEmpty() && $tagihanNonSpp->isEmpty()) {
                $validator->errors()->add('penerimaan', 'Pilih minimal satu tagihan untuk dibayar.');
            }

            foreach ($tagihanNonSpp as $index => $item) {
                if ($item['nominal_bayar'] < 1) {
                    $validator->errors()->add("items.{$index}.nominal_bayar", 'Masukkan nominal pembayaran untuk tagihan yang dipilih.');
                }
            }
        }];
    }

    /**
     * @return Collection<int, array{id_tagihan_pembayaran: int, nominal_bayar: int}>
     */
    public function selectedNonSppItems(): Collection
    {
        return collect($this->validated('items', []))
            ->filter(fn (array $item): bool => (bool) ($item['selected'] ?? false))
            ->map(fn (array $item): array => [
                'id_tagihan_pembayaran' => (int) $item['id_tagihan_pembayaran'],
                'nominal_bayar' => (int) ($item['nominal_bayar'] ?? 0),
            ])
            ->values();
    }
}
