<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
use App\Models\PosKantinSyncConflict;
use App\Models\PosKantinSyncOutbox;
use App\Services\Audit\AuditLogger;
use App\Services\PosKantin\PosKantinSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function index(Request $request, PosKantinSyncService $posKantinSyncService): View
    {
        $user = $request->user();

        return view('pos-kantin.sync.index', [
            'conflicts' => $posKantinSyncService->unresolvedConflicts($user),
            'recentRuns' => $posKantinSyncService->recentRuns($user),
            'syncStatus' => $posKantinSyncService->statusForUser($user),
        ]);
    }

    public function status(Request $request, PosKantinSyncService $posKantinSyncService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $posKantinSyncService->statusForUser($request->user()),
        ]);
    }

    public function auto(Request $request, PosKantinSyncService $posKantinSyncService): JsonResponse
    {
        $result = $posKantinSyncService->sync($request->user(), 'auto');
        $statusCode = $result['ok']
            ? 200
            : (($result['category'] ?? null) === 'locked' ? 423 : 422);

        return response()->json([
            'success' => $result['ok'],
            'data' => $result,
        ], $statusCode);
    }

    public function run(Request $request, PosKantinSyncService $posKantinSyncService): RedirectResponse
    {
        $result = $posKantinSyncService->sync($request->user(), 'manual');

        return back()->with($result['ok'] ? 'status' : 'error', $result['ok']
            ? 'Sinkronisasi berhasil dijalankan.'
            : ($result['message'] ?? 'Sinkronisasi gagal dijalankan.'));
    }

    public function retryFailed(Request $request, PosKantinSyncService $posKantinSyncService): RedirectResponse
    {
        $posKantinSyncService->retryFailed($request->user());
        $result = $posKantinSyncService->sync($request->user(), 'retry');

        return back()->with($result['ok'] ? 'status' : 'error', $result['ok']
            ? 'Sinkronisasi gagal/conflict sudah dicoba ulang.'
            : ($result['message'] ?? 'Retry sinkronisasi gagal.'));
    }

    public function discard(
        Request $request,
        PosKantinSyncService $posKantinSyncService,
        AuditLogger $auditLogger,
        int $outboxId,
    ): RedirectResponse {
        $outbox = PosKantinSyncOutbox::query()
            ->with('conflict')
            ->whereBelongsTo($request->user(), 'user')
            ->findOrFail($outboxId);

        $posKantinSyncService->discardOutbox($request->user(), $outboxId);

        $auditLogger->log(
            $request,
            'sync.conflict.resolved_with_server',
            PosKantinSyncConflict::class,
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
        PosKantinSyncService $posKantinSyncService,
        AuditLogger $auditLogger,
        int $outboxId,
    ): RedirectResponse {
        $outbox = PosKantinSyncOutbox::query()
            ->with('conflict')
            ->whereBelongsTo($request->user(), 'user')
            ->findOrFail($outboxId);

        $posKantinSyncService->resendOutbox($request->user(), $outboxId);
        $result = $posKantinSyncService->sync($request->user(), 'resend');

        $auditLogger->log(
            $request,
            'sync.conflict.retry_local',
            PosKantinSyncConflict::class,
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
