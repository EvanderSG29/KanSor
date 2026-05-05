<?php

namespace App\Http\Controllers\Kansor;

use App\Http\Controllers\Controller;
use App\Models\KansorSyncConflict;
use App\Models\KansorSyncOutbox;
use App\Services\Audit\AuditLogger;
use App\Services\Kansor\KansorSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function index(Request $request, KansorSyncService $KansorSyncService): View
    {
        $user = $request->user();

<<<<<<< HEAD:app/Http/Controllers/PosKantin/SyncController.php
        return view('kansor.sync.index', [
            'conflicts' => $posKantinSyncService->unresolvedConflicts($user),
            'recentRuns' => $posKantinSyncService->recentRuns($user),
            'syncStatus' => $posKantinSyncService->statusForUser($user),
            'pendingOutboxItems' => PosKantinSyncOutbox::query()->whereBelongsTo($user, 'user')->whereIn('status', ['pending', 'failed', 'conflict'])->latest()->limit(50)->get(),
=======
        return view('pos-kantin.sync.index', [
            'conflicts' => $KansorSyncService->unresolvedConflicts($user),
            'recentRuns' => $KansorSyncService->recentRuns($user),
            'syncStatus' => $KansorSyncService->statusForUser($user),
            'pendingOutboxItems' => KansorSyncOutbox::query()->whereBelongsTo($user, 'user')->whereIn('status', ['pending', 'failed', 'conflict'])->latest()->limit(50)->get(),
>>>>>>> 6549984 (	modified:   .env.example):app/Http/Controllers/Kansor/SyncController.php
        ]);
    }

    public function status(Request $request, KansorSyncService $KansorSyncService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $KansorSyncService->statusForUser($request->user()),
        ]);
    }

    public function auto(Request $request, KansorSyncService $KansorSyncService): JsonResponse
    {
        $result = $KansorSyncService->sync($request->user(), 'auto');
        $statusCode = $result['ok']
            ? 200
            : (($result['category'] ?? null) === 'locked' ? 423 : 422);

        return response()->json([
            'success' => $result['ok'],
            'data' => $result,
        ], $statusCode);
    }

    public function run(Request $request, KansorSyncService $KansorSyncService): RedirectResponse
    {
        $selectedOutboxIds = collect((array) $request->input('selected_outbox_ids', []))->map(fn (mixed $id): int => (int) $id)->filter(fn (int $id): bool => $id > 0)->values()->all();
        $trigger = $selectedOutboxIds === [] ? 'manual' : 'manual_selected';
        $result = $KansorSyncService->sync($request->user(), $trigger, $selectedOutboxIds);

        return back()->with($result['ok'] ? 'status' : 'error', $result['ok']
            ? 'Sinkronisasi berhasil dijalankan.'
            : ($result['message'] ?? 'Sinkronisasi gagal dijalankan.'));
    }


    public function runSelected(Request $request, KansorSyncService $KansorSyncService): RedirectResponse
    {
        $selectedOutboxIds = collect((array) $request->input('selected_outbox_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($selectedOutboxIds === []) {
            return back()->with('error', 'Pilih minimal satu antrean outbox untuk sinkronisasi terpilih.');
        }

        $result = $KansorSyncService->sync($request->user(), 'manual_selected', $selectedOutboxIds);

        return back()->with($result['ok'] ? 'status' : 'error', $result['ok']
            ? 'Sinkronisasi terpilih berhasil dijalankan.'
            : ($result['message'] ?? 'Sinkronisasi terpilih gagal dijalankan.'));
    }

    public function retryFailed(Request $request, KansorSyncService $KansorSyncService): RedirectResponse
    {
        $KansorSyncService->retryFailed($request->user());
        $result = $KansorSyncService->sync($request->user(), 'retry');

        return back()->with($result['ok'] ? 'status' : 'error', $result['ok']
            ? 'Sinkronisasi gagal/conflict sudah dicoba ulang.'
            : ($result['message'] ?? 'Retry sinkronisasi gagal.'));
    }

    public function discard(
        Request $request,
        KansorSyncService $KansorSyncService,
        AuditLogger $auditLogger,
        int $outboxId,
    ): RedirectResponse {
        $outbox = KansorSyncOutbox::query()
            ->with('conflict')
            ->whereBelongsTo($request->user(), 'user')
            ->findOrFail($outboxId);

        $KansorSyncService->discardOutbox($request->user(), $outboxId);

        $auditLogger->log(
            $request,
            'sync.conflict.resolved_with_server',
            KansorSyncConflict::class,
            $outbox->conflict?->getKey() ?? $outbox->getKey(),
            [
                'outbox_id' => $outbox->getKey(),
                'entity_type' => $outbox->entity_type,
                'entity_remote_id' => $outbox->entity_remote_id,
                'previous_resolution_status' => $outbox->conflict?->resolution_status,
                'has_server_snapshot' => is_array($outbox->server_snapshot),
            ],
        );

        return back()->with('status', 'Perubahan lokal dibuang dan versi server dipakai.');
    }

    public function resend(
        Request $request,
        KansorSyncService $KansorSyncService,
        AuditLogger $auditLogger,
        int $outboxId,
    ): RedirectResponse {
        $outbox = KansorSyncOutbox::query()
            ->with('conflict')
            ->whereBelongsTo($request->user(), 'user')
            ->findOrFail($outboxId);

        $KansorSyncService->resendOutbox($request->user(), $outboxId);
        $result = $KansorSyncService->sync($request->user(), 'resend');

        $auditLogger->log(
            $request,
            'sync.conflict.retry_local',
            KansorSyncConflict::class,
            $outbox->conflict?->getKey() ?? $outbox->getKey(),
            [
                'outbox_id' => $outbox->getKey(),
                'entity_type' => $outbox->entity_type,
                'entity_remote_id' => $outbox->entity_remote_id,
                'previous_resolution_status' => $outbox->conflict?->resolution_status,
            ],
        );

        return back()->with($result['ok'] ? 'status' : 'error', $result['ok']
            ? 'Perubahan lokal dijadwalkan ulang dan sinkronisasi dijalankan.'
            : ($result['message'] ?? 'Gagal mengirim ulang perubahan lokal.'));
    }
}

<<<<<<< HEAD:app/Http/Controllers/PosKantin/SyncController.php
=======

>>>>>>> 6549984 (	modified:   .env.example):app/Http/Controllers/Kansor/SyncController.php
