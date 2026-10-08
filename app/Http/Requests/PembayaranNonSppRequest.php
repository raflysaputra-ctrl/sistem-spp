<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

class PembayaranNonSppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
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

            $items = $this->selectedItems();

            if ($items->isEmpty()) {
                $validator->errors()->add('items', 'Pilih minimal satu tagihan untuk dibayar.');

                return;
            }

            if ($items->pluck('id_tagihan_pembayaran')->unique()->count() !== $items->count()) {
                $validator->errors()->add('items', 'Satu tagihan hanya boleh dipilih satu kali dalam satu kwitansi.');
            }

            foreach ($items as $index => $item) {
                if (! isset($item['nominal_bayar']) || (int) $item['nominal_bayar'] < 1) {
                    $validator->errors()->add("items.{$index}.nominal_bayar", 'Masukkan nominal pembayaran untuk tagihan yang dipilih.');
                }
            }
        }];
    }

    /**
     * @return Collection<int, array{id_tagihan_pembayaran: int, nominal_bayar: int}>
     */
    public function selectedItems(): Collection
    {
        return collect($this->validated('items'))
            ->filter(fn (array $item): bool => (bool) ($item['selected'] ?? false))
            ->map(fn (array $item): array => [
                'id_tagihan_pembayaran' => (int) $item['id_tagihan_pembayaran'],
                'nominal_bayar' => (int) ($item['nominal_bayar'] ?? 0),
            ])
            ->values();
    }
}
