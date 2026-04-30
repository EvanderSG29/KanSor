<?php

namespace App\Http\Requests\PosKantin;

use App\Models\Food;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActiveUser() && ($this->user()?->isAdmin() || $this->user()?->isPetugas());
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function (mixed $item): array {
                $record = is_array($item) ? $item : [];

                return [
                    'id' => $record['id'] ?? null,
                    'food_id' => $record['food_id'] ?? null,
                    'unit' => trim((string) ($record['unit'] ?? '')),
                    'quantity' => (int) preg_replace('/[^\d-]/', '', (string) ($record['quantity'] ?? '0')),
                    'leftover' => ($record['leftover'] ?? null) === '' ? null : (int) preg_replace('/[^\d-]/', '', (string) ($record['leftover'] ?? '0')),
                    'price_per_unit' => (int) preg_replace('/[^\d-]/', '', (string) ($record['price_per_unit'] ?? '0')),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'supplier_id' => $this->input('supplier_id') !== null ? (int) $this->input('supplier_id') : null,
            'additional_users' => array_values(array_filter(array_map(
                static fn (mixed $id): int => (int) $id,
                (array) $this->input('additional_users', []),
            ))),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        /** @var Sale|null $sale */
        $sale = $this->route('sale');

        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    if (CarbonImmutable::parse($value, 'Asia/Jakarta')->isAfter(now('Asia/Jakarta'))) {
                        $fail('Tanggal transaksi tidak boleh di masa depan.');
                    }
                },
            ],
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query
                    ->where('active', true)
                    ->whereNull('deleted_at')),
            ],
            'additional_users' => ['nullable', 'array'],
            'additional_users.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', User::ROLE_PETUGAS)
                    ->where('active', true)),
            ],
            'items' => ['required', 'array', 'list', 'min:1'],
            'items.*' => ['required', 'array:id,food_id,unit,quantity,leftover,price_per_unit'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.food_id' => ['required', 'integer'],
            'items.*.unit' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.leftover' => ['nullable', 'integer', 'min:0'],
            'items.*.price_per_unit' => ['required', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $supplierId = (int) $this->input('supplier_id');
                $items = $this->input('items', []);
                $foodIds = collect($items)->pluck('food_id')->filter()->map(fn (mixed $id): int => (int) $id)->unique()->values();

                if ($foodIds->isEmpty()) {
                    return;
                }

                $foods = Food::query()
                    ->whereIn('id', $foodIds)
                    ->where('supplier_id', $supplierId)
                    ->where('active', true)
                    ->get()
                    ->keyBy('id');

                foreach ($items as $index => $item) {
                    $foodId = (int) ($item['food_id'] ?? 0);

                    if (! $foods->has($foodId)) {
                        $validator->errors()->add("items.$index.food_id", 'Makanan harus aktif dan milik pemasok yang dipilih.');
                    }

                    $quantity = (int) ($item['quantity'] ?? 0);
                    $leftover = $item['leftover'] ?? null;

                    if ($leftover !== null && (int) $leftover > $quantity) {
                        $validator->errors()->add("items.$index.leftover", 'Sisa tidak boleh lebih besar dari qty.');
                    }
                }

                /** @var Sale|null $sale */
                $sale = $this->route('sale');
                $duplicateSaleQuery = Sale::query()
                    ->whereDate('date', (string) $this->input('date'))
                    ->where('supplier_id', $supplierId);

                if ($sale instanceof Sale) {
                    $duplicateSaleQuery->whereKeyNot($sale->getKey());
                }

                if ($duplicateSaleQuery->exists()) {
                    $validator->errors()->add('date', 'Transaksi untuk pemasok ini pada tanggal yang sama sudah ada.');
                }

                if ($sale instanceof Sale) {
                    $allowedItemIds = $sale->items()->pluck('id')->all();

                    foreach ($items as $index => $item) {
                        if (($item['id'] ?? null) !== null && ! in_array((int) $item['id'], $allowedItemIds, true)) {
                            $validator->errors()->add("items.$index.id", 'Baris item transaksi tidak valid.');
                        }
                    }
                }

                if (in_array((int) $this->user()?->getKey(), (array) $this->input('additional_users', []), true)) {
                    $validator->errors()->add('additional_users', 'Petugas utama tidak perlu dimasukkan lagi sebagai petugas tambahan.');
                }
            },
        ];
    }
}
