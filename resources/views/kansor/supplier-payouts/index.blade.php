@extends('layouts.app')

@section('title', 'Payout Pemasok')

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
                <h3>{{ number_format($summary['outstandingCount'] ?? 0) }}</h3>
                <p>Outstanding</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($summary['dueCount'] ?? 0) }}</h3>
                <p>Jatuh tempo</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format($summary['overdueCount'] ?? 0) }}</h3>
                <p>Overdue</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp {{ number_format($summary['settledAmount'] ?? 0, 0, ',', '.') }}</h3>
                <p>Total dibayar</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-double"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payout Pemasok Outstanding</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Periode</th>
                    <th>Jatuh Tempo</th>
                    <th>Transaksi</th>
                    <th>Net Supplier</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($outstanding as $item)
                    <tr>
                        <td>{{ $item['supplierName'] }}</td>
                        <td>{{ $item['periodStart'] }} s/d {{ $item['periodEnd'] }}</td>
                        <td>{{ $item['dueDate'] }}</td>
                        <td>{{ number_format($item['transactionCount']) }}</td>
                        <td>Rp {{ number_format($item['totalSupplierNetAmount'], 0, ',', '.') }}</td>
                        <td>
                            <span class="badge badge-{{ $item['dueStatus'] === 'overdue' ? 'danger' : ($item['dueStatus'] === 'today' ? 'warning' : 'secondary') }}">
                                {{ strtoupper($item['dueStatus']) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada payout outstanding.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat pembayaran</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Periode</th>
                    <th>Dibayar Pada</th>
                    <th>Jumlah</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $item)
                    <tr>
                        <td>{{ $item['supplierNameSnapshot'] }}</td>
                        <td>{{ $item['periodStart'] }} s/d {{ $item['periodEnd'] }}</td>
                        <td>{{ $item['paidAt'] }}</td>
                        <td>Rp {{ number_format($item['totalSupplierNetAmount'], 0, ',', '.') }}</td>
                        <td>{{ $item['paidByName'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Belum ada riwayat pembayaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
