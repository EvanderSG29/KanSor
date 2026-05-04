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
            'clientSaleId' => (string) $sale->getKey(),
            'clientSaleItemId' => (string) $item->getKey(),
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
        $supplierPaidAt = $sale->supplier_paid_at
            ?? ($sale->status_i === Sale::STATUS_SUPPLIER_PAID ? $sale->paid_at : null);
        $supplierPaidAmount = $sale->supplier_paid_amount
            ?? ($sale->status_i === Sale::STATUS_SUPPLIER_PAID ? $sale->paid_amount : null);
        $supplierPaymentNote = trim((string) (
            $sale->supplier_payment_note
            ?? ($sale->status_i === Sale::STATUS_SUPPLIER_PAID ? $sale->taken_note : null)
        ));
        $canteenDepositedAt = $sale->canteen_deposited_at
            ?? ($sale->status_ii === Sale::STATUS_CANTEEN_DEPOSITED ? $sale->paid_at : null);
        $canteenDepositedAmount = $sale->canteen_deposited_amount
            ?? ($sale->status_ii === Sale::STATUS_CANTEEN_DEPOSITED ? $sale->paid_amount : null);
        $canteenDepositNote = trim((string) (
            $sale->canteen_deposit_note
            ?? ($sale->status_ii === Sale::STATUS_CANTEEN_DEPOSITED ? $sale->taken_note : null)
        ));

        $additionalUsers = array_values(array_filter(array_map(
            static fn (mixed $value): string => (string) $value,
            (array) ($sale->additional_users ?? []),
        )));

        if ($additionalUsers !== []) {
            $parts[] = 'additionalUsers='.implode(',', $additionalUsers);
        }

        if ($supplierPaidAt !== null) {
            $parts[] = 'supplierPaidAt='.$supplierPaidAt->format('Y-m-d');
        }

        if ($supplierPaidAmount !== null) {
            $parts[] = 'supplierPaidAmount='.(int) $supplierPaidAmount;
        }

        if ($supplierPaymentNote !== '') {
            $parts[] = 'supplierPaymentNote='.str_replace(['|', "\r", "\n"], ['/', ' ', ' '], $supplierPaymentNote);
        }

        if ($canteenDepositedAt !== null) {
            $parts[] = 'canteenDepositedAt='.$canteenDepositedAt->format('Y-m-d');
        }

        if ($canteenDepositedAmount !== null) {
            $parts[] = 'canteenDepositedAmount='.(int) $canteenDepositedAmount;
        }

        if ($canteenDepositNote !== '') {
            $parts[] = 'canteenDepositNote='.str_replace(['|', "\r", "\n"], ['/', ' ', ' '], $canteenDepositNote);
        }

        return implode(' | ', $parts);
    }
}
