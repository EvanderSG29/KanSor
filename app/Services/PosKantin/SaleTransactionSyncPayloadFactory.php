<?php

namespace App\Services\PosKantin;

use App\Models\Sale;
use App\Models\SaleItem;

class SaleTransactionSyncPayloadFactory
{
    /**
     * @return array<int, array<string, int|string|null>>
     */
    public function makeMany(Sale $sale): array
    {
        return $sale->items
            ->map(fn (SaleItem $item): array => $this->make($sale, $item))
            ->all();
    }

    /**
     * @return array<string, int|string|null>
     */
    public function make(Sale $sale, SaleItem $item): array
    {
        $remainingQuantity = max((int) ($item->leftover ?? 0), 0);
        $soldQuantity = max((int) $item->quantity - $remainingQuantity, 0);
        $grossSales = (int) $item->total_item;
        $commissionAmount = (int) $item->cut_amount;

        return [
            'id' => $this->remoteIdForSaleItemId($item->getKey()),
            'transactionDate' => $sale->date->format('Y-m-d'),
            'inputByUserId' => (string) $sale->user_id,
            'inputByName' => $sale->user?->name,
            'supplierId' => (string) $sale->supplier_id,
            'foodId' => (string) $item->food_id,
            'itemName' => $item->food?->name,
            'unitName' => $item->unit,
            'quantity' => (int) $item->quantity,
            'remainingQuantity' => $remainingQuantity,
            'soldQuantity' => $soldQuantity,
            'unitPrice' => (int) $item->price_per_unit,
            'grossSales' => $grossSales,
            'totalValue' => $grossSales,
            'profitAmount' => $commissionAmount,
            'commissionAmount' => $commissionAmount,
            'supplierNetAmount' => $grossSales - $commissionAmount,
            'notes' => $this->buildNotes($sale),
        ];
    }

    public function remoteIdForSaleItemId(int|string $saleItemId): string
    {
        return 'SALEITEM-'.$saleItemId;
    }

    private function buildNotes(Sale $sale): string
    {
        $parts = [
            'saleId='.$sale->getKey(),
            'statusI='.$sale->status_i,
            'statusII='.$sale->status_ii,
        ];

        $additionalUsers = array_values(array_filter(array_map(
            static fn (mixed $value): string => (string) $value,
            (array) ($sale->additional_users ?? []),
        )));

        if ($additionalUsers !== []) {
            $parts[] = 'additionalUsers='.implode(',', $additionalUsers);
        }

        if ($sale->paid_at !== null) {
            $parts[] = 'paidAt='.$sale->paid_at->format('Y-m-d');
        }

        if ($sale->paid_amount !== null) {
            $parts[] = 'paidAmount='.(int) $sale->paid_amount;
        }

        $takenNote = trim((string) ($sale->taken_note ?? ''));

        if ($takenNote !== '') {
            $parts[] = 'localNote='.str_replace(['|', "\r", "\n"], ['/', ' ', ' '], $takenNote);
        }

        return implode(' | ', $parts);
    }
}
