@extends('layouts.app')

@section('title', 'Laporan')
@section('page_subtitle', 'Snapshot ringkasan operasional backend POS Kantin untuk kebutuhan monitoring dan tindak lanjut harian.')
@section('page_actions')
    <a href="{{ route('home') }}" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i>
        Kembali ke dashboard
    </a>
@endsection
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard POS</a></li>
    <li class="breadcrumb-item active">Laporan</li>
@endsection

@section('content')
@isset($errorMessage)
@if ($errorMessage)
    <div class="alert alert-danger">
        {{ $errorMessage }}
    </div>
@endif
@endisset

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($summary['transactionCount'] ?? 0) }}</h3>
                <p>Total transaksi</p>
            </div>
            <div class="icon">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp {{ number_format($summary['totalGrossSales'] ?? 0, 0, ',', '.') }}</h3>
                <p>Total omzet</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>Rp {{ number_format($summary['totalProfit'] ?? 0, 0, ',', '.') }}</h3>
                <p>Total profit</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format($summary['overdueSupplierPayoutCount'] ?? 0) }}</h3>
                <p>Payout overdue</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ringkasan eksekutif</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="mb-0">
                            <dt>Transaksi hari ini</dt>
                            <dd>{{ number_format($summary['todayTransactionCount'] ?? 0) }}</dd>
                            <dt>Omzet hari ini</dt>
                            <dd>Rp {{ number_format($summary['todayGrossSales'] ?? 0, 0, ',', '.') }}</dd>
                            <dt>Total komisi</dt>
                            <dd>Rp {{ number_format($summary['totalCommission'] ?? 0, 0, ',', '.') }}</dd>
                            <dt>Supplier aktif</dt>
                            <dd>{{ number_format($summary['activeSuppliers'] ?? 0) }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="mb-0">
                            <dt>Pembeli aktif</dt>
                            <dd>{{ number_format($summary['activeBuyerCount'] ?? 0) }}</dd>
                            <dt>Simpanan tercatat</dt>
                            <dd>{{ number_format($summary['savingsCount'] ?? 0) }}</dd>
                            <dt>Kembalian pending</dt>
                            <dd>Rp {{ number_format($summary['pendingChangeAmount'] ?? 0, 0, ',', '.') }}</dd>
                            <dt>User backend</dt>
                            <dd>{{ number_format($summary['userCount'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted">
                Snapshot dibuat {{ $generatedAt->format('d M Y H:i') }}.
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">Outstanding payout</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse (($summary['outstandingPayoutBuckets'] ?? []) as $bucket)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Termin {{ $bucket['payoutTermDays'] }} hari</span>
                            <strong>Rp {{ number_format($bucket['totalSupplierNetAmount'], 0, ',', '.') }}</strong>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Belum ada payout outstanding.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card-footer">
                <a href="{{ route('pos-kantin.supplier-payouts.index') }}" class="btn btn-outline-warning btn-sm">Kelola pembayaran supplier</a>
            </div>
        </div>
    </div>
</div>

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
                        <td colspan="5" class="text-center text-muted py-4">Belum ada transaksi terbaru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
