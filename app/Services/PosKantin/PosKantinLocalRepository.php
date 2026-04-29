<?php

namespace App\Services\PosKantin;

use App\Models\User;
use Illuminate\Support\Collection;

class PosKantinLocalRepository
{
    public function __construct(
        protected PosKantinLocalStore $localStore,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int|bool>, summary: array<string, int|float>}
     */
    public function transactions(User $user, array $filters = []): array
    {
        $items = collect($this->localStore->payloads($user, 'transactions'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->when($user->role !== 'admin', function (Collection $collection) use ($user): Collection {
                return $collection->filter(fn (array $item): bool => (string) ($item['inputByUserId'] ?? '') === (string) $user->remote_user_id);
            })
            ->when($filters['transactionDate'] ?? null, fn (Collection $collection, string $value): Collection => $collection->where('transactionDate', $value))
            ->when($filters['startDate'] ?? null, fn (Collection $collection, string $value): Collection => $collection->filter(fn (array $item): bool => (string) ($item['transactionDate'] ?? '') >= $value))
            ->when($filters['endDate'] ?? null, fn (Collection $collection, string $value): Collection => $collection->filter(fn (array $item): bool => (string) ($item['transactionDate'] ?? '') <= $value))
            ->when($filters['supplierId'] ?? null, fn (Collection $collection, string $value): Collection => $collection->where('supplierId', $value))
            ->when($filters['commissionBaseType'] ?? null, fn (Collection $collection, string $value): Collection => $collection->where('commissionBaseType', $value))
            ->when($filters['search'] ?? null, function (Collection $collection, string $search): Collection {
                $needle = mb_strtolower(trim($search));

                return $collection->filter(function (array $item) use ($needle): bool {
                    return collect([
                        $item['itemName'] ?? null,
                        $item['supplierName'] ?? null,
                        $item['inputByName'] ?? null,
                        $item['notes'] ?? null,
                    ])->filter()->contains(function (string $value) use ($needle): bool {
                        return str_contains(mb_strtolower($value), $needle);
                    });
                });
            })
            ->sortByDesc(fn (array $item): string => (string) ($item['transactionDate'] ?? '').'|'.(string) ($item['updatedAt'] ?? ''))
            ->values();

        $summary = [
            'rowCount' => $items->count(),
            'totalGrossSales' => $items->sum(fn (array $item): float => $this->number($item['grossSales'] ?? 0)),
            'totalProfit' => $items->sum(fn (array $item): float => $this->number($item['profitAmount'] ?? 0)),
            'unsettledSupplierNetAmount' => $items
                ->filter(fn (array $item): bool => blank($item['supplierPayoutId'] ?? null))
                ->sum(fn (array $item): float => $this->number($item['supplierNetAmount'] ?? 0)),
        ];

        $paged = $this->paginate($items, (int) ($filters['page'] ?? 1), (int) ($filters['pageSize'] ?? 10));

        return [
            'items' => $paged['items'],
            'pagination' => $paged['pagination'],
            'summary' => $summary,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, summary: array<string, int|float>}
     */
    public function savings(User $user): array
    {
        $items = collect($this->localStore->payloads($user, 'savings'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->sortBy('studentName')
            ->values()
            ->all();

        return [
            'items' => $items,
            'summary' => [
                'count' => count($items),
                'depositAmount' => collect($items)->sum(fn (array $item): float => $this->number($item['depositAmount'] ?? 0)),
                'changeBalance' => collect($items)->sum(fn (array $item): float => $this->number($item['changeBalance'] ?? 0)),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function suppliers(User $user, array $filters = []): array
    {
        $includeInactive = ($filters['includeInactive'] ?? false) === true && $user->role === 'admin';

        $items = collect($this->localStore->payloads($user, 'suppliers'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->when(! $includeInactive, function (Collection $collection): Collection {
                return $collection->filter(fn (array $item): bool => ($item['isActive'] ?? true) === true);
            })
            ->sortBy('supplierName')
            ->values()
            ->all();

        return [
            'items' => $items,
            'summary' => [
                'count' => count($items),
                'activeCount' => (int) collect($items)->where('isActive', true)->count(),
            ],
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function users(User $user): array
    {
        $items = collect($this->localStore->payloads($user, 'users'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->sortBy('fullName')
            ->values()
            ->all();

        return [
            'items' => $items,
            'summary' => [
                'count' => count($items),
                'activeCount' => (int) collect($items)->where('status', 'aktif')->count(),
                'adminCount' => (int) collect($items)->where('role', 'admin')->count(),
            ],
        ];
    }

    /**
     * @return array{summary: array<string, int|float|array<int, array<string, int|float>>>, outstanding: array<int, array<string, mixed>>, history: array<int, array<string, mixed>>}
     */
    public function supplierPayouts(User $user): array
    {
        $history = collect($this->localStore->payloads($user, 'supplierPayouts'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->sortByDesc(fn (array $item): string => (string) ($item['dueDate'] ?? '').'|'.(string) ($item['updatedAt'] ?? ''))
            ->values()
            ->all();

        $outstanding = $this->buildOutstandingSupplierPayouts(
            collect($this->localStore->payloads($user, 'transactions'))
                ->reject(fn (array $item): bool => $this->isDeleted($item))
                ->values()
                ->all(),
        );

        return [
            'summary' => $this->buildSupplierPayoutSummary($outstanding, $history),
            'outstanding' => $outstanding,
            'history' => $history,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardSummary(User $user): array
    {
        $today = now()->timezone(config('app.timezone'))->format('Y-m-d');
        $transactions = collect($this->localStore->payloads($user, 'transactions'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->values();
        $suppliers = collect($this->localStore->payloads($user, 'suppliers'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->values();
        $buyers = collect($this->localStore->payloads($user, 'buyers'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->values();
        $savings = collect($this->localStore->payloads($user, 'savings'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->values();
        $changeEntries = collect($this->localStore->payloads($user, 'changeEntries'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->values();
        $users = collect($this->localStore->payloads($user, 'users'))
            ->reject(fn (array $item): bool => $this->isDeleted($item))
            ->values();
        $outstanding = collect($this->buildOutstandingSupplierPayouts($transactions->all()));

        return [
            'todayTransactionCount' => $transactions->where('transactionDate', $today)->count(),
            'todayGrossSales' => $transactions->where('transactionDate', $today)->sum(fn (array $item): float => $this->number($item['grossSales'] ?? 0)),
            'activeSuppliers' => $suppliers->where('isActive', true)->count(),
            'overdueSupplierPayoutCount' => $outstanding->where('dueStatus', 'overdue')->count(),
            'transactionCount' => $transactions->count(),
            'totalGrossSales' => $transactions->sum(fn (array $item): float => $this->number($item['grossSales'] ?? 0)),
            'totalProfit' => $transactions->sum(fn (array $item): float => $this->number($item['profitAmount'] ?? 0)),
            'totalCommission' => $transactions->sum(fn (array $item): float => $this->number($item['commissionAmount'] ?? 0)),
            'userCount' => $users->count(),
            'activeBuyerCount' => $buyers->where('status', 'aktif')->count(),
            'savingsCount' => $savings->count(),
            'pendingChangeAmount' => $changeEntries
                ->filter(fn (array $item): bool => ($item['status'] ?? 'belum') !== 'selesai')
                ->sum(fn (array $item): float => $this->number($item['changeAmount'] ?? 0)),
            'recentTransactions' => $transactions
                ->sortByDesc(fn (array $item): string => (string) ($item['transactionDate'] ?? '').'|'.(string) ($item['updatedAt'] ?? ''))
                ->take(5)
                ->values()
                ->all(),
            'outstandingPayoutBuckets' => $outstanding
                ->groupBy(fn (array $item): string => (string) ($item['payoutTermDays'] ?? 0))
                ->map(function (Collection $group): array {
                    return [
                        'payoutTermDays' => (int) ($group->first()['payoutTermDays'] ?? 0),
                        'totalSupplierNetAmount' => $group->sum(fn (array $item): float => $this->number($item['totalSupplierNetAmount'] ?? 0)),
                    ];
                })
                ->sortBy('payoutTermDays')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<int, array<string, mixed>>
     */
    protected function buildOutstandingSupplierPayouts(array $transactions): array
    {
        $grouped = [];
        $today = now()->timezone(config('app.timezone'))->format('Y-m-d');

        foreach ($transactions as $record) {
            if (blank($record['supplierPayoutId'] ?? null) === false) {
                continue;
            }

            $supplierId = (string) ($record['supplierId'] ?? $record['supplierName'] ?? 'SUPPLIER-UNKNOWN');
            $dueDate = (string) ($record['payoutDueDate'] ?? $record['transactionDate'] ?? '');
            $groupKey = $supplierId.'||'.$dueDate;

            $current = $grouped[$groupKey] ?? [
                'groupKey' => $groupKey,
                'supplierId' => $record['supplierId'] ?? '',
                'supplierName' => $record['supplierName'] ?? 'Tanpa nama pemasok',
                'supplierNameSnapshot' => $record['supplierName'] ?? 'Tanpa nama pemasok',
                'payoutTermDays' => (int) ($record['payoutTermDays'] ?? 0),
                'dueDate' => $dueDate,
                'transactionCount' => 0,
                'totalGrossSales' => 0,
                'totalProfit' => 0,
                'totalCommission' => 0,
                'totalSupplierNetAmount' => 0,
                'periodStart' => $record['transactionDate'] ?? '',
                'periodEnd' => $record['transactionDate'] ?? '',
                'transactionIds' => [],
            ];

            $current['transactionCount']++;
            $current['totalGrossSales'] += $this->number($record['grossSales'] ?? 0);
            $current['totalProfit'] += $this->number($record['profitAmount'] ?? 0);
            $current['totalCommission'] += $this->number($record['commissionAmount'] ?? 0);
            $current['totalSupplierNetAmount'] += $this->number($record['supplierNetAmount'] ?? 0);
            $current['transactionIds'][] = $record['id'] ?? null;

            if (($record['transactionDate'] ?? '') !== '' && ($current['periodStart'] === '' || (string) $record['transactionDate'] < (string) $current['periodStart'])) {
                $current['periodStart'] = $record['transactionDate'];
            }

            if (($record['transactionDate'] ?? '') !== '' && ($current['periodEnd'] === '' || (string) $record['transactionDate'] > (string) $current['periodEnd'])) {
                $current['periodEnd'] = $record['transactionDate'];
            }

            $current['dueStatus'] = $this->dueStatus($dueDate, $today);
            $grouped[$groupKey] = $current;
        }

        return collect($grouped)
            ->sortBy([
                ['dueDate', 'asc'],
                ['supplierName', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $outstanding
     * @param  array<int, array<string, mixed>>  $history
     * @return array<string, int|float|array<int, array<string, int|float>>>
     */
    protected function buildSupplierPayoutSummary(array $outstanding, array $history): array
    {
        $today = now()->timezone(config('app.timezone'))->format('Y-m-d');
        $outstandingCollection = collect($outstanding);
        $historyCollection = collect($history);

        return [
            'outstandingCount' => $outstandingCollection->count(),
            'outstandingAmount' => $outstandingCollection->sum(fn (array $item): float => $this->number($item['totalSupplierNetAmount'] ?? 0)),
            'dueCount' => $outstandingCollection->filter(fn (array $item): bool => (string) ($item['dueDate'] ?? '') <= $today)->count(),
            'dueAmount' => $outstandingCollection
                ->filter(fn (array $item): bool => (string) ($item['dueDate'] ?? '') <= $today)
                ->sum(fn (array $item): float => $this->number($item['totalSupplierNetAmount'] ?? 0)),
            'overdueCount' => $outstandingCollection->where('dueStatus', 'overdue')->count(),
            'overdueAmount' => $outstandingCollection
                ->where('dueStatus', 'overdue')
                ->sum(fn (array $item): float => $this->number($item['totalSupplierNetAmount'] ?? 0)),
            'settledCount' => $historyCollection->count(),
            'settledAmount' => $historyCollection->sum(fn (array $item): float => $this->number($item['totalSupplierNetAmount'] ?? 0)),
            'termBuckets' => $outstandingCollection
                ->groupBy(fn (array $item): string => (string) ($item['payoutTermDays'] ?? 0))
                ->map(function (Collection $group): array {
                    return [
                        'payoutTermDays' => (int) ($group->first()['payoutTermDays'] ?? 0),
                        'count' => $group->count(),
                        'totalSupplierNetAmount' => $group->sum(fn (array $item): float => $this->number($item['totalSupplierNetAmount'] ?? 0)),
                    ];
                })
                ->sortBy('payoutTermDays')
                ->values()
                ->all(),
        ];
    }

    protected function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int|bool>}
     */
    protected function paginate(Collection $items, int $page, int $pageSize): array
    {
        $safePageSize = max($pageSize, 1);
        $safePage = max($page, 1);
        $totalItems = $items->count();
        $totalPages = max((int) ceil(max($totalItems, 1) / $safePageSize), 1);
        $resolvedPage = min($safePage, $totalPages);
        $offset = ($resolvedPage - 1) * $safePageSize;
        $pageItems = $items->slice($offset, $safePageSize)->values()->all();
        $startItem = $totalItems === 0 ? 0 : $offset + 1;
        $endItem = $totalItems === 0 ? 0 : min($offset + count($pageItems), $totalItems);

        return [
            'items' => $pageItems,
            'pagination' => [
                'page' => $resolvedPage,
                'pageSize' => $safePageSize,
                'totalItems' => $totalItems,
                'startItem' => $startItem,
                'endItem' => $endItem,
                'hasPrev' => $resolvedPage > 1,
                'hasNext' => $resolvedPage < $totalPages,
            ],
        ];
    }

    protected function isDeleted(array $payload): bool
    {
        return filled($payload['deletedAt'] ?? null);
    }

    protected function dueStatus(string $dueDate, string $today): string
    {
        if ($dueDate === '') {
            return 'unknown';
        }

        if ($dueDate < $today) {
            return 'overdue';
        }

        if ($dueDate === $today) {
            return 'today';
        }

        return 'upcoming';
    }
}
