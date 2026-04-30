@extends('layouts.app')

@section('title', 'Riwayat Transaksi')

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar transaksi</h3>
        <a href="{{ route('pos-kantin.sales.create') }}" class="btn btn-primary btn-sm">Input transaksi</a>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-4">
                <label>Pemasok</label>
                <select name="supplier_id" class="form-control">
                    <option value="">Semua</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Dari</label>
                <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label>Sampai</label>
                <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-primary mr-2">Filter</button>
                <a href="{{ route('pos-kantin.sales.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Pemasok</th>
                    <th>Petugas</th>
                    <th>Total pemasok</th>
                    <th>Total kantin</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sales as $sale)
                    <tr>
                        <td>{{ $sale->date->format('d/m/Y') }}</td>
                        <td>{{ $sale->supplier?->name ?? '-' }}</td>
                        <td>{{ $sale->user?->name ?? '-' }}</td>
                        <td>Rp {{ number_format($sale->total_supplier, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($sale->total_canteen, 0, ',', '.') }}</td>
                        <td>
                            <span class="badge badge-{{ $sale->isLocked() ? 'success' : 'warning' }}">
                                {{ strtoupper($sale->status_i) }} / {{ strtoupper($sale->status_ii) }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('pos-kantin.sales.show', $sale) }}" class="btn btn-sm btn-outline-secondary">Detail</a>
                            @can('update', $sale)
                                <a href="{{ route('pos-kantin.sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $sales->links() }}</div>
</div>
@endsection
