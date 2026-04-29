<?php

namespace App\Http\Controllers\PosKantin;

use App\Http\Controllers\Controller;
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

        return response()->json([
            'success' => $result['ok'],
            'data' => $result,
        ]);
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

    public function discard(Request $request, PosKantinSyncService $posKantinSyncService, int $outboxId): RedirectResponse
    {
        $posKantinSyncService->discardOutbox($request->user(), $outboxId);

        return back()->with('status', 'Perubahan lokal dibuang dan versi server dipakai.');
    }

    public function resend(Request $request, PosKantinSyncService $posKantinSyncService, int $outboxId): RedirectResponse
    {
        $posKantinSyncService->resendOutbox($request->user(), $outboxId);
        $result = $posKantinSyncService->sync($request->user(), 'resend');

        return back()->with($result['ok'] ? 'status' : 'error', $result['ok']
            ? 'Perubahan lokal dijadwalkan ulang dan sinkronisasi dijalankan.'
            : ($result['message'] ?? 'Gagal mengirim ulang perubahan lokal.'));
    }
}
