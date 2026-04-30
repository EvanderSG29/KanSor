@extends('layouts.app')

@section('title', 'Data Transaksi Server')

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Filter transaksi</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('pos-kantin.transactions.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">Cari</label>
                        <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Supplier, item, catatan">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="transactionDate">Tanggal</label>
                        <input id="transactionDate" type="date" name="transactionDate" value="{{ $filters['transactionDate'] ?? '' }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="startDate">Mulai</label>
                        <input id="startDate" type="date" name="startDate" value="{{ $filters['startDate'] ?? '' }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="endDate">Sampai</label>
                        <input id="endDate" type="date" name="endDate" value="{{ $filters['endDate'] ?? '' }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="pageSize">Per halaman</label>
                        <select id="pageSize" name="pageSize" class="form-control">
                            @foreach ([10, 25, 50] as $pageSizeOption)
                                <option value="{{ $pageSizeOption }}" @selected(($filters['pageSize'] ?? 10) === $pageSizeOption)>{{ $pageSizeOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block mb-3">Terapkan</button>
                </div>
            </div>
        </form>
    </div>
</div>

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
                <h3>{{ number_format($summary['rowCount'] ?? 0) }}</h3>
                <p>Total baris</p>
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
                <p>Omzet kotor</p>
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
                <h3>Rp {{ number_format($summary['unsettledSupplierNetAmount'] ?? 0, 0, ',', '.') }}</h3>
                <p>Utang supplier terbuka</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar transaksi</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Terjual</th>
                    <th>Omzet</th>
                    <th>Net supplier</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction['transactionDate'] }}</td>
                        <td>{{ $transaction['supplierName'] }}</td>
                        <td>
                            <strong>{{ $transaction['itemName'] }}</strong>
                            <div class="text-muted small">{{ $transaction['unitName'] }}</div>
                        </td>
                        <td>{{ number_format($transaction['quantity']) }}</td>
                        <td>{{ number_format($transaction['soldQuantity']) }}</td>
                        <td>Rp {{ number_format($transaction['grossSales'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($transaction['supplierNetAmount'], 0, ',', '.') }}</td>
                        <td>
                            @php
                                $dueStatus = $transaction['dueStatus'];
                                $badgeClass = match ($dueStatus) {
                                    'overdue' => 'badge badge-danger',
                                    'today' => 'badge badge-warning',
                                    'settled' => 'badge badge-success',
                                    default => 'badge badge-secondary',
                                };
                            @endphp
                            <span class="{{ $badgeClass }}">{{ strtoupper($dueStatus) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada data transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($pagination)
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span>Menampilkan {{ $pagination['startItem'] }}-{{ $pagination['endItem'] }} dari {{ $pagination['totalItems'] }} data</span>
            <div>
                @if ($pagination['hasPrev'])
                    <a href="{{ route('pos-kantin.transactions.index', array_merge(request()->query(), ['page' => $pagination['page'] - 1])) }}" class="btn btn-outline-secondary btn-sm">Sebelumnya</a>
                @endif
                @if ($pagination['hasNext'])
                    <a href="{{ route('pos-kantin.transactions.index', array_merge(request()->query(), ['page' => $pagination['page'] + 1])) }}" class="btn btn-outline-secondary btn-sm">Berikutnya</a>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
