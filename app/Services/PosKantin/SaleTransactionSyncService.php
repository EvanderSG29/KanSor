<?php

namespace App\Services\PosKantin;

use App\Models\Sale;
use App\Models\SaleItem;

class SaleTransactionSyncService
{
    public function __construct(
        protected PosKantinMutationDispatcher $dispatcher,
        protected SaleTransactionSyncPayloadFactory $payloadFactory,
    ) {}

    /**
     * @param  array<int, int|string>  $deletedSaleItemIds
     * @return array{status: 'applied'|'queued'|'unsupported'|'failed', message: string, warning: string|null}
     */
    public function syncSale(Sale $sale, array $deletedSaleItemIds = []): array
    {
        $sale->loadMissing(['supplier', 'user', 'items.food']);

        $results = [];

        foreach ($deletedSaleItemIds as $deletedSaleItemId) {
            $results[] = $this->dispatchDeleteForSaleItemId($deletedSaleItemId);
        }

        foreach ($sale->items as $item) {
            $results[] = $this->dispatchSaveForSaleItem($sale, $item);
        }

        return $this->aggregateResults($results);
    }

    /**
     * @param  array<int, int|string>  $saleItemIds
     * @return array{status: 'applied'|'queued'|'unsupported'|'failed', message: string, warning: string|null}
     */
    public function deleteSaleItems(array $saleItemIds): array
    {
        return $this->aggregateResults(array_map(
            fn (int|string $saleItemId): array => $this->dispatchDeleteForSaleItemId($saleItemId),
            $saleItemIds,
        ));
    }

    /**
     * @return array{status: 'applied'|'queued'|'unsupported'|'failed', message: string, warning: string|null}
     */
    private function dispatchSaveForSaleItem(Sale $sale, SaleItem $item): array
    {
        $payload = $this->payloadFactory->make($sale, $item);

        return $this->dispatcher->dispatch('saveTransaction', [$payload], [
            'entity' => 'sale_item',
            'id' => $payload['id'],
        ]);
    }

    /**
     * @return array{status: 'applied'|'queued'|'unsupported'|'failed', message: string, warning: string|null}
     */
    private function dispatchDeleteForSaleItemId(int|string $saleItemId): array
    {
        $remoteId = $this->payloadFactory->remoteIdForSaleItemId($saleItemId);

        return $this->dispatcher->dispatch('deleteTransaction', [$remoteId], [
            'entity' => 'sale_item',
            'id' => $remoteId,
        ]);
    }

    /**
     * @param  array<int, array{status: 'applied'|'queued'|'unsupported'|'failed', message: string, warning: string|null}>  $results
     * @return array{status: 'applied'|'queued'|'unsupported'|'failed', message: string, warning: string|null}
     */
    private function aggregateResults(array $results): array
    {
        foreach ($results as $result) {
            if ($result['status'] === 'failed') {
                return $result;
            }
        }

        foreach ($results as $result) {
            if ($result['status'] === 'unsupported') {
                return $result;
            }
        }

        if ($results === []) {
            return [
                'status' => 'queued',
                'message' => 'Tidak ada perubahan transaksi yang perlu dikirim ke spreadsheet.',
                'warning' => null,
            ];
        }

        $allApplied = collect($results)->every(fn (array $result): bool => $result['status'] === 'applied');

        if ($allApplied) {
            return [
                'status' => 'applied',
                'message' => 'Data transaksi lokal berhasil disimpan dan sinkronisasi spreadsheet langsung diterapkan.',
                'warning' => null,
            ];
        }

        return [
            'status' => 'queued',
            'message' => 'Data transaksi lokal berhasil disimpan dan sinkronisasi spreadsheet sudah masuk antrean.',
            'warning' => null,
        ];
    }
}
