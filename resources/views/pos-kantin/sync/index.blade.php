@extends('layouts.app')

@section('title', 'Status Sinkronisasi')
@section('page_header')
    <x-pos.page-header title="Status Sinkronisasi" subtitle="Pantau status offline/online, antrean perubahan lokal, dan konflik sinkronisasi.">
        <x-slot:actions>
            <form method="POST" action="{{ route('pos-kantin.sync.run') }}" class="d-inline-block mr-2">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">Sync sekarang</button>
            </form>
            <form method="POST" action="{{ route('pos-kantin.sync.retry') }}" class="d-inline-block">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">Retry gagal / konflik</button>
            </form>
        </x-slot:actions>
    </x-pos.page-header>
@endsection

@push('styles')
<style>
    .kansor-conflict-table th,
    .kansor-conflict-table td {
        vertical-align: top;
        white-space: normal;
    }

    .kansor-conflict-cell {
        min-width: 13rem;
    }

    .kansor-conflict-value {
        display: block;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        line-height: 1.45;
        word-break: break-word;
    }
</style>
@endpush

@section('content')
@include('pos-kantin.partials.alerts')

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($syncStatus['queuedCount'] ?? $syncStatus['pendingCount'] ?? 0) }}</h3>
                <p>Antrean</p>
            </div>
            <div class="icon">
                <i class="fas fa-upload"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($syncStatus['appliedCount'] ?? 0) }}</h3>
                <p>Tersinkron</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($syncStatus['failedCount'] ?? 0) }}</h3>
                <p>Gagal</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format($syncStatus['conflictCount'] ?? 0) }}</h3>
                <p>Konflik</p>
            </div>
            <div class="icon">
                <i class="fas fa-random"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $syncStatus['lastRemoteSyncAt'] ? \Illuminate\Support\Carbon::parse($syncStatus['lastRemoteSyncAt'])->format('H:i') : '-' }}</h3>
                <p>Sinkron terakhir</p>
            </div>
            <div class="icon">
                <i class="fas fa-sync"></i>
            </div>
        </div>
    </div>
</div>

@if (($syncStatus['lastRun']['summary']['push'] ?? null) !== null)
    <div class="alert alert-light border">
        Push terakhir:
        antrean {{ number_format($syncStatus['lastRun']['summary']['push']['queued'] ?? 0) }},
        tersinkron {{ number_format($syncStatus['lastRun']['summary']['push']['applied'] ?? 0) }},
        gagal {{ number_format($syncStatus['lastRun']['summary']['push']['failed'] ?? 0) }},
        konflik {{ number_format($syncStatus['lastRun']['summary']['push']['conflicts'] ?? 0) }}.
    </div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">Kontrol sinkronisasi</h3>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">Gunakan tombol pada page header untuk menjalankan sinkronisasi manual atau mencoba ulang antrean yang gagal.</p>
        <dl class="row mt-3 mb-0">
            <dt class="col-sm-4">Trusted device berlaku sampai</dt>
            <dd class="col-sm-8">{{ $syncStatus['trustedDeviceExpiresAt'] ? \Illuminate\Support\Carbon::parse($syncStatus['trustedDeviceExpiresAt'])->format('d M Y H:i') : '-' }}</dd>
            <dt class="col-sm-4">Offline login berlaku sampai</dt>
            <dd class="col-sm-8">{{ $syncStatus['offlineLoginExpiresAt'] ? \Illuminate\Support\Carbon::parse($syncStatus['offlineLoginExpiresAt'])->format('d M Y H:i') : '-' }}</dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Konflik sinkronisasi</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <x-pos.data-table
            class="kansor-conflict-table"
            :empty="count($conflicts) === 0"
            :colspan="8"
            empty-title="Belum ada konflik sinkronisasi"
            empty-message="Saat ada perbedaan data lokal dan server, detail resolusinya akan muncul di sini."
        >
            <x-slot:head>
                <tr>
                    <th>Entitas</th>
                    <th>ID Server</th>
                    <th>Field Berbeda</th>
                    <th>Nilai Lokal</th>
                    <th>Nilai Server</th>
                    <th>Waktu Perubahan Lokal</th>
                    <th>Waktu Perubahan Server</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:head>
            @foreach ($conflicts as $conflict)
                @php
                    $fieldDiffs = $conflict['fieldDiffs'] ?? [];
                    $canResolve = (bool) ($conflict['hasComparisonContext'] ?? false);
                @endphp
                <tr>
                    <td class="kansor-conflict-cell">
                        <div class="font-weight-bold">{{ $conflict['entityLabel'] ?? $conflict['entityType'] }}</div>
                        <div class="small text-muted mt-1">
                            Terdeteksi {{ $conflict['createdAt'] ? \Illuminate\Support\Carbon::parse($conflict['createdAt'])->format('d M Y H:i') : '-' }}
                        </div>
                        @if (! empty($conflict['lastError']))
                            <div class="small text-warning mt-2">{{ $conflict['lastError'] }}</div>
                        @endif
                    </td>
                    <td>{{ $conflict['entityRemoteId'] ?: '-' }}</td>
                    <td class="kansor-conflict-cell">
                        @if ($fieldDiffs !== [])
                            @foreach ($fieldDiffs as $fieldDiff)
                                <span class="badge badge-light border mr-1 mb-1">{{ $fieldDiff['field'] }}</span>
                            @endforeach
                        @else
                            <span class="text-muted small">Detail perbedaan belum tersedia.</span>
                        @endif
                    </td>
                    <td class="kansor-conflict-cell">
                        @if ($fieldDiffs !== [])
                            @foreach ($fieldDiffs as $fieldDiff)
                                <div class="mb-2">
                                    <div class="small text-muted font-weight-bold mb-1">{{ $fieldDiff['field'] }}</div>
                                    <span class="kansor-conflict-value">{{ $fieldDiff['localValue'] }}</span>
                                </div>
                            @endforeach
                        @else
                            <span class="text-muted small">Belum ada nilai lokal yang bisa dibandingkan.</span>
                        @endif
                    </td>
                    <td class="kansor-conflict-cell">
                        @if ($fieldDiffs !== [])
                            @foreach ($fieldDiffs as $fieldDiff)
                                <div class="mb-2">
                                    <div class="small text-muted font-weight-bold mb-1">{{ $fieldDiff['field'] }}</div>
                                    <span class="kansor-conflict-value">{{ $fieldDiff['serverValue'] }}</span>
                                </div>
                            @endforeach
                        @else
                            <span class="text-muted small">Belum ada nilai server yang bisa dibandingkan.</span>
                        @endif
                    </td>
                    <td>{{ $conflict['localUpdatedAt'] ? \Illuminate\Support\Carbon::parse($conflict['localUpdatedAt'])->format('d M Y H:i') : '-' }}</td>
                    <td>{{ $conflict['serverUpdatedAt'] ? \Illuminate\Support\Carbon::parse($conflict['serverUpdatedAt'])->format('d M Y H:i') : '-' }}</td>
                    <td>
                        @if ($canResolve)
                            <div class="d-flex flex-column align-items-start">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm mb-2"
                                    data-toggle="modal"
                                    data-target="#syncConflictDiscardModal"
                                    data-action-url="{{ route('pos-kantin.sync.outbox.discard', $conflict['outboxId']) }}"
                                    data-entity-label="{{ $conflict['entityLabel'] ?? $conflict['entityType'] }}"
                                    data-remote-id="{{ $conflict['entityRemoteId'] ?: '-' }}"
                                >
                                    Pakai server
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-toggle="modal"
                                    data-target="#syncConflictResendModal"
                                    data-action-url="{{ route('pos-kantin.sync.outbox.resend', $conflict['outboxId']) }}"
                                    data-entity-label="{{ $conflict['entityLabel'] ?? $conflict['entityType'] }}"
                                    data-remote-id="{{ $conflict['entityRemoteId'] ?: '-' }}"
                                >
                                    Kirim ulang lokal
                                </button>
                            </div>
                        @else
                            <span class="text-muted small">Aksi resolusi menunggu detail konflik.</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
</div>

<div class="modal fade" id="syncConflictDiscardModal" tabindex="-1" role="dialog" aria-labelledby="syncConflictDiscardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="syncConflictDiscardModalLabel">Konfirmasi pakai versi server</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mengganti data lokal dengan versi server. Perubahan lokal yang belum tersinkron dapat hilang. Aksi ini akan dicatat di audit log. Lanjutkan?</p>
                    <div class="alert alert-light border mb-0">
                        <div><strong>Entitas:</strong> <span data-sync-conflict-entity>-</span></div>
                        <div><strong>ID server:</strong> <span data-sync-conflict-remote-id>-</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary">Pakai versi server</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="syncConflictResendModal" tabindex="-1" role="dialog" aria-labelledby="syncConflictResendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="syncConflictResendModalLabel">Konfirmasi kirim ulang data lokal</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mengirim ulang data lokal ke server. Pastikan data lokal adalah versi yang benar. Aksi ini akan dicatat di audit log. Lanjutkan?</p>
                    <div class="alert alert-light border mb-0">
                        <div><strong>Entitas:</strong> <span data-sync-conflict-entity>-</span></div>
                        <div><strong>ID server:</strong> <span data-sync-conflict-remote-id>-</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kirim ulang lokal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat sinkronisasi terbaru</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <x-pos.data-table :empty="count($recentRuns) === 0" :colspan="5" empty-title="Belum ada riwayat sinkronisasi" empty-message="Riwayat eksekusi manual maupun otomatis akan tampil di sini." text-no-wrap>
            <x-slot:head>
                <tr>
                    <th>Trigger</th>
                    <th>Status</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Error</th>
                </tr>
            </x-slot:head>
            @foreach ($recentRuns as $run)
                <tr>
                    <td>{{ strtoupper($run['trigger']) }}</td>
                    <td><x-pos.status-badge :status="$run['status']" context="sync-run" uppercase /></td>
                    <td>{{ $run['startedAt'] ? \Illuminate\Support\Carbon::parse($run['startedAt'])->format('d M Y H:i') : '-' }}</td>
                    <td>{{ $run['endedAt'] ? \Illuminate\Support\Carbon::parse($run['endedAt'])->format('d M Y H:i') : '-' }}</td>
                    <td>{{ $run['errorMessage'] ?: '-' }}</td>
                </tr>
            @endforeach
        </x-pos.data-table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const jQueryInstance = window.jQuery;

        if (! jQueryInstance) {
            return;
        }

        ['syncConflictDiscardModal', 'syncConflictResendModal'].forEach(function (modalId) {
            const modal = document.getElementById(modalId);

            if (! modal) {
                return;
            }

            jQueryInstance(modal).on('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;

                if (! trigger) {
                    return;
                }

                const actionUrl = trigger.getAttribute('data-action-url');
                const entityLabel = trigger.getAttribute('data-entity-label') || '-';
                const remoteId = trigger.getAttribute('data-remote-id') || '-';
                const form = modal.querySelector('form');

                if (form && actionUrl) {
                    form.setAttribute('action', actionUrl);
                }

                modal.querySelectorAll('[data-sync-conflict-entity]').forEach(function (element) {
                    element.textContent = entityLabel;
                });

                modal.querySelectorAll('[data-sync-conflict-remote-id]').forEach(function (element) {
                    element.textContent = remoteId;
                });
            });
        });
    });
</script>
@endpush
