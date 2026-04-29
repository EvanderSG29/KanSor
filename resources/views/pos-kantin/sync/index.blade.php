@extends('layouts.app')

@section('title', 'Sinkronisasi')
@section('page_subtitle', 'Pantau status offline/online, antrean perubahan lokal, dan konflik sinkronisasi.')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($syncStatus['pendingCount'] ?? 0) }}</h3>
                <p>Queue pending</p>
            </div>
            <div class="icon">
                <i class="fas fa-upload"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($syncStatus['failedCount'] ?? 0) }}</h3>
                <p>Queue gagal</p>
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
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $syncStatus['lastRemoteSyncAt'] ? \Illuminate\Support\Carbon::parse($syncStatus['lastRemoteSyncAt'])->format('H:i') : '-' }}</h3>
                <p>Sync terakhir</p>
            </div>
            <div class="icon">
                <i class="fas fa-sync"></i>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">Kontrol sinkronisasi</h3>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('pos-kantin.sync.run') }}" class="mr-2 mb-2">
                @csrf
                <button type="submit" class="btn btn-primary">Sync sekarang</button>
            </form>
            <form method="POST" action="{{ route('pos-kantin.sync.retry') }}" class="mr-2 mb-2">
                @csrf
                <button type="submit" class="btn btn-outline-warning">Retry gagal / konflik</button>
            </form>
        </div>
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
        <h3 class="card-title">Konflik unresolved</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th>Entitas</th>
                    <th>ID remote</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($conflicts as $conflict)
                    <tr>
                        <td>{{ $conflict['entityType'] }}</td>
                        <td>{{ $conflict['entityRemoteId'] ?: '-' }}</td>
                        <td>{{ $conflict['createdAt'] ? \Illuminate\Support\Carbon::parse($conflict['createdAt'])->format('d M Y H:i') : '-' }}</td>
                        <td class="d-flex">
                            <form method="POST" action="{{ route('pos-kantin.sync.outbox.discard', $conflict['outboxId']) }}" class="mr-2">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">Pakai server</button>
                            </form>
                            <form method="POST" action="{{ route('pos-kantin.sync.outbox.resend', $conflict['outboxId']) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">Kirim ulang</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Belum ada konflik sinkronisasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat sync terbaru</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th>Trigger</th>
                    <th>Status</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentRuns as $run)
                    <tr>
                        <td>{{ strtoupper($run['trigger']) }}</td>
                        <td>
                            <span class="badge badge-{{ $run['status'] === 'success' ? 'success' : ($run['status'] === 'failed' ? 'danger' : 'secondary') }}">
                                {{ strtoupper($run['status']) }}
                            </span>
                        </td>
                        <td>{{ $run['startedAt'] ? \Illuminate\Support\Carbon::parse($run['startedAt'])->format('d M Y H:i') : '-' }}</td>
                        <td>{{ $run['endedAt'] ? \Illuminate\Support\Carbon::parse($run['endedAt'])->format('d M Y H:i') : '-' }}</td>
                        <td>{{ $run['errorMessage'] ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada riwayat sinkronisasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
