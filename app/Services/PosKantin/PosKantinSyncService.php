<?php

namespace App\Services\PosKantin;

use App\Exceptions\PosKantinException;
use App\Models\PosKantinDeviceCredential;
use App\Models\PosKantinSyncConflict;
use App\Models\PosKantinSyncOutbox;
use App\Models\PosKantinSyncRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosKantinSyncService
{
    public function __construct(
        protected PosKantinLocalStore $localStore,
        protected PosKantinSessionClient $sessionClient,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function sync(User $user, string $trigger = 'manual'): array
    {
        $run = PosKantinSyncRun::query()->create([
            'scope_owner_user_id' => $user->getKey(),
            'trigger' => $trigger,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $sessionToken = $this->ensureRemoteSession($user);
            $pushSummary = $this->pushOutbox($user, $sessionToken);
            $pullSummary = $this->pullChanges($user, $sessionToken);

            $run->fill([
                'status' => 'success',
                'ended_at' => now(),
                'summary' => [
                    'push' => $pushSummary,
                    'pull' => $pullSummary,
                ],
            ])->save();

            return [
                'ok' => true,
                'runId' => $run->getKey(),
                'summary' => $run->summary,
            ];
        } catch (PosKantinException $exception) {
            $run->fill([
                'status' => 'failed',
                'ended_at' => now(),
                'error_message' => $exception->getMessage(),
            ])->save();

            return [
                'ok' => false,
                'runId' => $run->getKey(),
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function statusForUser(User $user): array
    {
        $latestRun = PosKantinSyncRun::query()
            ->whereBelongsTo($user, 'user')
            ->latest('started_at')
            ->first();

        $credential = PosKantinDeviceCredential::query()
            ->whereBelongsTo($user, 'user')
            ->first();

        return [
            'queuedCount' => PosKantinSyncOutbox::query()->whereBelongsTo($user, 'user')->where('status', 'pending')->count(),
            'pendingCount' => PosKantinSyncOutbox::query()->whereBelongsTo($user, 'user')->where('status', 'pending')->count(),
            'appliedCount' => PosKantinSyncOutbox::query()->whereBelongsTo($user, 'user')->where('status', 'applied')->count(),
            'failedCount' => PosKantinSyncOutbox::query()->whereBelongsTo($user, 'user')->where('status', 'failed')->count(),
            'conflictCount' => PosKantinSyncConflict::query()->whereBelongsTo($user, 'user')->where('resolution_status', 'unresolved')->count(),
            'lastRun' => $latestRun?->only(['id', 'trigger', 'status', 'started_at', 'ended_at', 'error_message', 'summary']),
            'lastRemoteSyncAt' => optional($credential?->last_remote_sync_at)->toIso8601String(),
            'offlineLoginExpiresAt' => optional($user->offline_login_expires_at)->toIso8601String(),
            'trustedDeviceExpiresAt' => optional($credential?->trusted_device_expires_at)->toIso8601String(),
            'syncIntervalSeconds' => (int) config('services.pos_kantin.sync_interval_seconds', 60),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function unresolvedConflicts(User $user): array
    {
        return PosKantinSyncConflict::query()
            ->with('outbox')
            ->whereBelongsTo($user, 'user')
            ->where('resolution_status', 'unresolved')
            ->latest()
            ->get()
            ->map(function (PosKantinSyncConflict $conflict): array {
                return [
                    'id' => $conflict->getKey(),
                    'entityType' => $conflict->entity_type,
                    'entityRemoteId' => $conflict->entity_remote_id,
                    'localPayload' => $conflict->local_payload,
                    'serverPayload' => $conflict->server_payload,
                    'outboxId' => $conflict->outbox_id,
                    'createdAt' => optional($conflict->created_at)->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentRuns(User $user, int $limit = 10): array
    {
        return PosKantinSyncRun::query()
            ->whereBelongsTo($user, 'user')
            ->latest('started_at')
            ->limit($limit)
            ->get()
            ->map(fn (PosKantinSyncRun $run): array => [
                'id' => $run->getKey(),
                'trigger' => $run->trigger,
                'status' => $run->status,
                'startedAt' => optional($run->started_at)->toIso8601String(),
                'endedAt' => optional($run->ended_at)->toIso8601String(),
                'summary' => $run->summary,
                'errorMessage' => $run->error_message,
            ])
            ->all();
    }

    public function retryFailed(User $user): void
    {
        PosKantinSyncOutbox::query()
            ->whereBelongsTo($user, 'user')
            ->whereIn('status', ['failed', 'conflict'])
            ->update([
                'status' => 'pending',
                'last_error' => null,
            ]);
    }

    public function discardOutbox(User $user, int $outboxId): void
    {
        $outbox = PosKantinSyncOutbox::query()
            ->whereBelongsTo($user, 'user')
            ->findOrFail($outboxId);

        if (is_array($outbox->server_snapshot)) {
            $this->localStore->applyServerPayload($user, $outbox->entity_type, $outbox->server_snapshot);
        }

        $outbox->update([
            'status' => 'discarded',
            'last_error' => null,
        ]);

        PosKantinSyncConflict::query()
            ->where('outbox_id', $outbox->getKey())
            ->update([
                'resolution_status' => 'accepted_server',
            ]);
    }

    public function resendOutbox(User $user, int $outboxId): void
    {
        $outbox = PosKantinSyncOutbox::query()
            ->whereBelongsTo($user, 'user')
            ->findOrFail($outboxId);

        $expectedUpdatedAt = is_array($outbox->server_snapshot)
            ? ($outbox->server_snapshot['updatedAt'] ?? $outbox->expected_updated_at)
            : $outbox->expected_updated_at;

        $outbox->update([
            'status' => 'pending',
            'expected_updated_at' => $expectedUpdatedAt,
            'last_error' => null,
        ]);

        PosKantinSyncConflict::query()
            ->where('outbox_id', $outbox->getKey())
            ->update([
                'resolution_status' => 'resent',
            ]);
    }

    public function queueMutation(
        User $user,
        string $action,
        string $entityType,
        array $payload,
        ?string $entityRemoteId = null,
        ?string $expectedUpdatedAt = null,
    ): PosKantinSyncOutbox {
        return PosKantinSyncOutbox::query()->create([
            'scope_owner_user_id' => $user->getKey(),
            'client_mutation_id' => (string) Str::uuid(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_remote_id' => $entityRemoteId,
            'payload' => $payload,
            'expected_updated_at' => $expectedUpdatedAt,
            'status' => 'pending',
        ]);
    }

    protected function ensureRemoteSession(User $user): string
    {
        $credential = PosKantinDeviceCredential::query()
            ->whereBelongsTo($user, 'user')
            ->first();

        if ($credential === null) {
            throw new PosKantinException('Perangkat ini belum punya kredensial sinkronisasi POS Kantin.', [
                'category' => 'authentication',
            ]);
        }

        if ($credential->remote_session_token !== null && $credential->remote_session_expires_at !== null && $credential->remote_session_expires_at->isFuture()) {
            return $credential->remote_session_token;
        }

        if ($credential->trusted_device_token === null) {
            throw new PosKantinException('Token trusted device POS Kantin tidak tersedia untuk sinkronisasi.', [
                'category' => 'authentication',
            ]);
        }

        $login = $this->sessionClient->loginWithTrustedDevice($credential->trusted_device_token);

        $credential->fill([
            'remote_session_token' => $login['token'],
            'remote_session_expires_at' => $login['expiresAt'] ?? null,
            'trusted_device_expires_at' => $login['trustedDeviceExpiresAt'] ?? $credential->trusted_device_expires_at,
        ])->save();

        $this->synchronizeLocalUserFromRemotePayload($user, $login['user'] ?? []);

        return (string) $login['token'];
    }

    /**
     * @return array<string, int>
     */
    protected function pushOutbox(User $user, string $sessionToken): array
    {
        $pendingItems = PosKantinSyncOutbox::query()
            ->whereBelongsTo($user, 'user')
            ->whereIn('status', ['pending', 'failed'])
            ->orderBy('created_at')
            ->get();

        if ($pendingItems->isEmpty()) {
            return [
                'queued' => 0,
                'applied' => 0,
                'failed' => 0,
                'conflicts' => 0,
            ];
        }

        $results = $this->sessionClient->syncPush(
            $sessionToken,
            $pendingItems->map(function (PosKantinSyncOutbox $outbox): array {
                return [
                    'clientMutationId' => $outbox->client_mutation_id,
                    'action' => $outbox->action,
                    'entityType' => $outbox->entity_type,
                    'entityId' => $outbox->entity_remote_id,
                    'expectedUpdatedAt' => $outbox->expected_updated_at,
                    'payload' => (object) $outbox->payload,
                ];
            })->all(),
        );

        $summary = [
            'queued' => $pendingItems->count(),
            'applied' => 0,
            'failed' => 0,
            'conflicts' => 0,
        ];

        foreach (($results['results'] ?? []) as $result) {
            $clientMutationId = (string) ($result['clientMutationId'] ?? '');
            $outbox = $pendingItems->firstWhere('client_mutation_id', $clientMutationId);

            if (! $outbox instanceof PosKantinSyncOutbox) {
                continue;
            }

            $status = (string) ($result['status'] ?? 'failed');

            if ($status === 'applied') {
                $outbox->update([
                    'status' => 'applied',
                    'attempts' => $outbox->attempts + 1,
                    'last_error' => null,
                    'server_snapshot' => null,
                ]);
                $summary['applied']++;

                continue;
            }

            if ($status === 'conflict') {
                $serverPayload = is_array($result['serverRecord'] ?? null) ? $result['serverRecord'] : null;

                $outbox->update([
                    'status' => 'conflict',
                    'attempts' => $outbox->attempts + 1,
                    'last_error' => (string) ($result['message'] ?? 'Terjadi konflik sinkronisasi.'),
                    'server_snapshot' => $serverPayload,
                ]);

                PosKantinSyncConflict::query()->updateOrCreate(
                    [
                        'outbox_id' => $outbox->getKey(),
                    ],
                    [
                        'scope_owner_user_id' => $user->getKey(),
                        'entity_type' => $outbox->entity_type,
                        'entity_remote_id' => $outbox->entity_remote_id,
                        'local_payload' => $outbox->payload,
                        'server_payload' => $serverPayload,
                        'resolution_status' => 'unresolved',
                    ],
                );

                $summary['conflicts']++;

                continue;
            }

            $outbox->update([
                'status' => 'failed',
                'attempts' => $outbox->attempts + 1,
                'last_error' => (string) ($result['message'] ?? 'Gagal mengirim perubahan lokal.'),
            ]);
            $summary['failed']++;
        }

        return $summary;
    }

    /**
     * @return array<string, int>
     */
    protected function pullChanges(User $user, string $sessionToken): array
    {
        $cursorMap = DB::table('pos_sync_cursors')
            ->where('scope_owner_user_id', $user->getKey())
            ->pluck('cursor', 'resource')
            ->all();

        $payload = $this->sessionClient->syncPull($sessionToken, $cursorMap);
        $summary = [];

        foreach ($this->localStore->resources() as $resource) {
            $records = $payload[$resource] ?? [];
            $summary[$resource] = $this->localStore->upsertMirrorRecords($user, $resource, is_array($records) ? $records : []);

            DB::table('pos_sync_cursors')->updateOrInsert(
                [
                    'scope_owner_user_id' => $user->getKey(),
                    'resource' => $resource,
                ],
                [
                    'cursor' => $payload['cursors'][$resource] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        $userRecord = collect($payload['users'] ?? [])
            ->first(fn (array $record): bool => (string) ($record['id'] ?? '') === (string) $user->remote_user_id);

        if (is_array($userRecord)) {
            $this->synchronizeLocalUserFromRemotePayload($user, $userRecord);
        }

        PosKantinDeviceCredential::query()
            ->whereBelongsTo($user, 'user')
            ->update([
                'last_remote_sync_at' => now(),
            ]);

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $remoteUser
     */
    protected function synchronizeLocalUserFromRemotePayload(User $user, array $remoteUser): void
    {
        $authUpdatedAt = (string) ($remoteUser['authUpdatedAt'] ?? '');

        $updates = [
            'remote_user_id' => (string) ($remoteUser['id'] ?? $user->remote_user_id),
            'name' => (string) ($remoteUser['fullName'] ?? $user->name),
            'email' => (string) ($remoteUser['email'] ?? $user->email),
            'role' => (string) ($remoteUser['role'] ?? $user->role),
            'status' => (string) ($remoteUser['status'] ?? $user->status),
            'active' => (string) ($remoteUser['status'] ?? $user->status) === User::STATUS_ACTIVE,
            'remote_auth_updated_at' => $authUpdatedAt !== '' ? $authUpdatedAt : $user->remote_auth_updated_at,
        ];

        if ($user->remote_auth_updated_at !== null && $authUpdatedAt !== '' && $authUpdatedAt !== $user->remote_auth_updated_at) {
            $updates['offline_login_expires_at'] = now();
        }

        $user->fill($updates)->save();
    }
}
