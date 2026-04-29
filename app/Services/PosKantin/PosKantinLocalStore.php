<?php

namespace App\Services\PosKantin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosKantinLocalStore
{
    /**
     * @return array<int, string>
     */
    public function resources(): array
    {
        return array_keys($this->tableMap());
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     */
    public function upsertMirrorRecords(User $user, string $resource, array $records): int
    {
        $table = $this->tableFor($resource);
        $affectedRows = 0;
        $timestamp = now();

        foreach ($records as $record) {
            if (! is_array($record) || ! isset($record['id'])) {
                continue;
            }

            DB::table($table)->updateOrInsert(
                [
                    'scope_owner_user_id' => $user->getKey(),
                    'remote_id' => (string) $record['id'],
                ],
                [
                    'payload' => json_encode($record, JSON_THROW_ON_ERROR),
                    'remote_created_at' => $this->stringValue($record['createdAt'] ?? null),
                    'remote_updated_at' => $this->stringValue($record['updatedAt'] ?? null),
                    'remote_deleted_at' => $this->stringValue($record['deletedAt'] ?? null),
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ],
            );

            $affectedRows++;
        }

        return $affectedRows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function payloads(User $user, string $resource): array
    {
        return DB::table($this->tableFor($resource))
            ->where('scope_owner_user_id', $user->getKey())
            ->orderByDesc('remote_updated_at')
            ->get()
            ->map(function (object $row): array {
                $payload = json_decode((string) $row->payload, true);

                return is_array($payload) ? $payload : [];
            })
            ->filter(fn (array $payload): bool => $payload !== [])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyServerPayload(User $user, string $entityType, array $payload): void
    {
        if (! isset($payload['id'])) {
            return;
        }

        $this->upsertMirrorRecords($user, $this->resourceForEntityType($entityType), [$payload]);
    }

    public function resourceForEntityType(string $entityType): string
    {
        $resource = match ($entityType) {
            'user' => 'users',
            'supplier' => 'suppliers',
            'buyer' => 'buyers',
            'transaction' => 'transactions',
            'saving' => 'savings',
            'dailyFinance' => 'dailyFinance',
            'changeEntry' => 'changeEntries',
            'supplierPayout' => 'supplierPayouts',
            default => $entityType,
        };

        $this->tableFor($resource);

        return $resource;
    }

    public function tableFor(string $resource): string
    {
        $table = $this->tableMap()[$resource] ?? null;

        if ($table === null) {
            throw new InvalidArgumentException(sprintf('Resource POS Kantin tidak dikenal: %s', $resource));
        }

        return $table;
    }

    /**
     * @return array<string, string>
     */
    protected function tableMap(): array
    {
        return [
            'users' => 'pos_users',
            'suppliers' => 'pos_suppliers',
            'buyers' => 'pos_buyers',
            'transactions' => 'pos_transactions',
            'savings' => 'pos_savings',
            'dailyFinance' => 'pos_daily_finance',
            'changeEntries' => 'pos_change_entries',
            'supplierPayouts' => 'pos_supplier_payouts',
        ];
    }

    protected function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value) || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
