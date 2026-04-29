<?php

namespace App\Services\PosKantin;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;

class SaleCalculationService
{
    /**
     * @param  array<string, mixed>  $item
     * @return array{unit: string, quantity: int, leftover: int|null, price_per_unit: int, total_item: int, cut_amount: int}
     */
    public function calculateItem(array $item, Supplier $supplier): array
    {
        $quantity = max($this->normalizeInteger($item['quantity'] ?? 0), 1);
        $leftover = $item['leftover'] === null || $item['leftover'] === ''
            ? null
            : max($this->normalizeInteger($item['leftover']), 0);
        $pricePerUnit = max($this->normalizeInteger($item['price_per_unit'] ?? 0), 1);
        $totalItem = $quantity * $pricePerUnit;
        $cutAmount = (int) round($totalItem * ((float) $supplier->percentage_cut / 100));

        return [
            'unit' => (string) ($item['unit'] ?? ''),
            'quantity' => $quantity,
            'leftover' => $leftover,
            'price_per_unit' => $pricePerUnit,
            'total_item' => $totalItem,
            'cut_amount' => $cutAmount,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{total_supplier: int, total_canteen: int}
     */
    public function summarize(array $items): array
    {
        $totalItem = array_sum(array_map(
            fn (array $item): int => $this->normalizeInteger($item['total_item'] ?? 0),
            $items,
        ));
        $totalCanteen = array_sum(array_map(
            fn (array $item): int => $this->normalizeInteger($item['cut_amount'] ?? 0),
            $items,
        ));

        return [
            'total_supplier' => $totalItem - $totalCanteen,
            'total_canteen' => $totalCanteen,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncItems(Sale $sale, Supplier $supplier, array $items): void
    {
        $existingItems = $sale->items()->get()->keyBy('id');
        $keptItemIds = [];
        $calculatedItems = [];

        foreach ($items as $item) {
            $calculated = $this->calculateItem($item, $supplier);
            $attributes = array_merge($calculated, [
                'food_id' => (int) $item['food_id'],
            ]);

            $itemId = isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null;

            if ($itemId !== null && $existingItems->has($itemId)) {
                /** @var SaleItem $saleItem */
                $saleItem = $existingItems->get($itemId);
                $saleItem->fill($attributes)->save();
                $keptItemIds[] = $saleItem->getKey();
                $calculatedItems[] = $saleItem->only(['total_item', 'cut_amount']);

                continue;
            }

            $saleItem = $sale->items()->create($attributes);
            $keptItemIds[] = $saleItem->getKey();
            $calculatedItems[] = $saleItem->only(['total_item', 'cut_amount']);
        }

        $sale->items()
            ->when($keptItemIds !== [], fn ($query) => $query->whereNotIn('id', $keptItemIds))
            ->when($keptItemIds === [], fn ($query) => $query)
            ->get()
            ->each
            ->delete();

        $summary = $this->summarize($calculatedItems);

        $sale->fill($summary)->save();
    }

    public function recalculateSale(Sale $sale): void
    {
        $summary = $this->summarize(
            $sale->items()
                ->get(['total_item', 'cut_amount'])
                ->map(fn (SaleItem $item): array => $item->only(['total_item', 'cut_amount']))
                ->all(),
        );

        $sale->fill($summary)->save();
    }

    public function normalizeInteger(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = preg_replace('/[^\d-]/', '', $value) ?? '0';
        }

        return is_numeric($value) ? (int) $value : 0;
    }
}
