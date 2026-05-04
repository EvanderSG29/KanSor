@extends('layouts.app')

@php
    $currentUser = auth()->user();
    $isAdmin = $currentUser?->isAdmin() ?? false;
    $queuedCount = (int) ($syncStatus['queuedCount'] ?? $syncStatus['pendingCount'] ?? 0);
    $failedCount = (int) ($syncStatus['failedCount'] ?? 0);
    $conflictCount = (int) ($syncStatus['conflictCount'] ?? 0);
    $syncAttentionCount = $queuedCount + $failedCount + $conflictCount;
@endphp

@section('title', 'Dashboard')
@section('page_subtitle', $isAdmin
    ? 'Pantau operasional harian, proses konfirmasi admin, dan status sinkronisasi server.'
    : 'Fokus pada input transaksi harian, riwayat transaksi, dan status sinkronisasi perangkat.')
@section('page_actions')
    @if ($isAdmin)
        <a href="{{ route('kansor.admin.sales.index') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-check-double mr-1"></i>
            Buka konfirmasi admin
        </a>
    @else
        <a href="{{ route('kansor.sales.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-cash-register mr-1"></i>
            Input transaksi
        </a>
    @endif
@endsection
@section('breadcrumbs')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($summary['todayTransactionCount'] ?? 0) }}</h3>
                <p>Transaksi Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <a href="{{ route('kansor.sales.index') }}" class="small-box-footer">Lihat riwayat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp {{ number_format($summary['todayGrossSales'] ?? 0, 0, ',', '.') }}</h3>
                <p>Omzet Hari Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-cash-register"></i>
            </div>
            <a href="{{ route('home') }}" class="small-box-footer">Refresh ringkasan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($summary['activeSuppliers'] ?? 0) }}</h3>
                <p>Supplier Aktif</p>
            </div>
            <div class="icon">
                <i class="fas fa-user"></i>
            </div>
            <a href="{{ $isAdmin ? route('kansor.admin.suppliers.index') : route('kansor.suppliers.index') }}" class="small-box-footer">Lihat pemasok <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        @if ($isAdmin)
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($summary['overdueSupplierPayoutCount'] ?? 0) }}</h3>
                    <p>Payout Pemasok Overdue</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="{{ route('kansor.supplier-payouts.index') }}" class="small-box-footer">Lihat payout <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        @else
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ number_format($syncAttentionCount) }}</h3>
                    <p>Perlu Sinkronisasi</p>
                </div>
                <div class="icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <a href="{{ route('kansor.sync.index') }}" class="small-box-footer">Lihat sinkronisasi <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Status sinkronisasi</h3>
            </div>
            <div class="card-body">
                @php
                    $queuedCount = (int) ($syncStatus['queuedCount'] ?? $syncStatus['pendingCount'] ?? 0);
                    $appliedCount = (int) ($syncStatus['appliedCount'] ?? 0);
                    $failedCount = (int) ($syncStatus['failedCount'] ?? 0);
                    $conflictCount = (int) ($syncStatus['conflictCount'] ?? 0);
                    $syncBadgeClass = $failedCount > 0 || $conflictCount > 0 ? 'danger' : ($queuedCount > 0 ? 'warning' : 'success');
                    $syncBadgeLabel = $failedCount > 0 || $conflictCount > 0 ? 'Perlu tindakan' : ($queuedCount > 0 ? 'Queued' : ($appliedCount > 0 ? 'Applied' : 'Siap'));
                @endphp
                <p class="mb-2">
                    <span class="badge badge-{{ $syncBadgeClass }}">
                        {{ $syncBadgeLabel }}
                    </span>
                </p>
                <dl class="mb-0">
                    <dt>Sync terakhir</dt>
                    <dd>{{ $syncStatus['lastRemoteSyncAt'] ? \Illuminate\Support\Carbon::parse($syncStatus['lastRemoteSyncAt'])->format('d M Y H:i') : 'Belum pernah' }}</dd>
                    <dt>Queued</dt>
                    <dd>{{ number_format($queuedCount) }}</dd>
                    <dt>Applied</dt>
                    <dd>{{ number_format($appliedCount) }}</dd>
                    <dt>Failed</dt>
                    <dd>{{ number_format($failedCount) }}</dd>
                    <dt>Konflik</dt>
                    <dd>{{ number_format($conflictCount) }}</dd>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Akses cepat</h3>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @if ($isAdmin)
                        <a href="{{ route('kansor.sales.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-cash-register mr-2 text-primary"></i>Input Transaksi</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.admin.sales.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-check-double mr-2 text-success"></i>Konfirmasi Admin</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.admin.suppliers.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-user mr-2 text-warning"></i>Kelola Pemasok</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.supplier-payouts.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-hand-holding-usd mr-2 text-danger"></i>Payout Pemasok</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.reports.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-chart-pie mr-2 text-info"></i>Laporan Operasional</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.users.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-database mr-2 text-secondary"></i>Data Pengguna Server</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                    @else
                        <a href="{{ route('kansor.sales.create') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-cash-register mr-2 text-primary"></i>Input Transaksi</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.sales.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-history mr-2 text-success"></i>Riwayat Transaksi</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.sync.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-sync-alt mr-2 text-warning"></i>Status Sinkronisasi</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                        <a href="{{ route('kansor.preferences.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-sliders-h mr-2 text-secondary"></i>Preferensi</span>
                            <i class="fas fa-angle-right text-muted"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ringkasan operasional</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="mb-0">
                            <dt>Total transaksi</dt>
                            <dd>{{ number_format($summary['transactionCount'] ?? 0) }}</dd>
                            <dt>Total omzet</dt>
                            <dd>Rp {{ number_format($summary['totalGrossSales'] ?? 0, 0, ',', '.') }}</dd>
                            <dt>Total profit</dt>
                            <dd>Rp {{ number_format($summary['totalProfit'] ?? 0, 0, ',', '.') }}</dd>
                            <dt>Total komisi</dt>
                            <dd>Rp {{ number_format($summary['totalCommission'] ?? 0, 0, ',', '.') }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="mb-0">
                            <dt>User aktif</dt>
                            <dd>{{ number_format($summary['userCount'] ?? 0) }}</dd>
                            <dt>Pembeli aktif</dt>
                            <dd>{{ number_format($summary['activeBuyerCount'] ?? 0) }}</dd>
                            <dt>Simpanan tercatat</dt>
                            <dd>{{ number_format($summary['savingsCount'] ?? 0) }}</dd>
                            <dt>Kembalian pending</dt>
                            <dd>Rp {{ number_format($summary['pendingChangeAmount'] ?? 0, 0, ',', '.') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Transaksi terbaru</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Item</th>
                            <th>Omzet</th>
                            <th>Net supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($summary['recentTransactions'] ?? []) as $transaction)
                            <tr>
                                <td>{{ $transaction['transactionDate'] }}</td>
                                <td>{{ $transaction['supplierName'] }}</td>
                                <td>{{ $transaction['itemName'] }}</td>
                                <td>Rp {{ number_format($transaction['grossSales'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($transaction['supplierNetAmount'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi untuk ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Bucket payout outstanding</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse (($summary['outstandingPayoutBuckets'] ?? []) as $bucket)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $bucket['payoutTermDays'] }} hari</span>
                            <strong>Rp {{ number_format($bucket['totalSupplierNetAmount'], 0, ',', '.') }}</strong>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Belum ada payout outstanding.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

