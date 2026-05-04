@extends('layouts.app')

@section('title', 'Rekap Total Kantin')

@section('content')
@include('kansor.partials.alerts')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title mb-0">Filter rekap</h3></div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-4">
                        <label>Bulan</label>
                        <input type="month" name="month" class="form-control" value="{{ $filters['month'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label>Dari</label>
                        <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label>Sampai</label>
                        <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                    </div>
                    <div class="col-12 mt-3">
                        <button class="btn btn-outline-primary mr-2">Filter</button>
                        <a href="{{ route('kansor.admin.canteen-totals.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Total kantin</th>
                            <th>Status Rekap Harian</th>
                            <th>Status Tutup Buku / Validasi Akhir</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($canteenTotals as $total)
                            <tr>
                                <td>{{ $total->date->format('d/m/Y') }}</td>
                                <td>Rp {{ number_format($total->total_amount, 0, ',', '.') }}</td>
                                <td>{{ ucfirst($total->status_iii) }}</td>
                                <td>{{ $total->status_iv ? ucfirst($total->status_iv) : '-' }}</td>
                                <td>{{ $total->taken_note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada rekap total kantin.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $canteenTotals->links() }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp {{ number_format($grandTotal, 0, ',', '.') }}</h3>
                <p>Total pendapatan kantin</p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">Rekap per pemasok</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Pemasok</th>
                            <th>Kantin</th>
                            <th>Pemasok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($supplierSummary as $row)
                            <tr>
                                <td>{{ $row['supplier'] }}</td>
                                <td>Rp {{ number_format($row['total_canteen'], 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($row['total_supplier'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

