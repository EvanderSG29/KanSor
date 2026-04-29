@extends('layouts.app')

@section('title', 'Detail Transaksi Harian')

@section('content')
@include('pos-kantin.partials.alerts')
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Transaksi #{{ $sale->id }}</h3>
        <div>
            @can('update', $sale)
                <a href="{{ route('pos-kantin.sales.edit', $sale) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            @endcan
            @can('delete', $sale)
                <form action="{{ route('pos-kantin.sales.destroy', $sale) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Batalkan transaksi ini?')">Batalkan</button>
                </form>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt>Tanggal</dt><dd>{{ $sale->date->format('d/m/Y') }}</dd>
                    <dt>Pemasok</dt><dd>{{ $sale->supplier?->name ?? '-' }}</dd>
                    <dt>Petugas utama</dt><dd>{{ $sale->user?->name ?? '-' }}</dd>
                    <dt>Petugas tambahan</dt><dd>{{ collect($sale->additional_users ?? [])->implode(', ') ?: '-' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl>
                    <dt>Total pemasok</dt><dd>Rp {{ number_format($sale->total_supplier, 0, ',', '.') }}</dd>
                    <dt>Total kantin</dt><dd>Rp {{ number_format($sale->total_canteen, 0, ',', '.') }}</dd>
                    <dt>Status I</dt><dd>{{ strtoupper($sale->status_i) }}</dd>
                    <dt>Status II</dt><dd>{{ strtoupper($sale->status_ii) }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Makanan</th>
                    <th>Satuan</th>
                    <th>Jumlah</th>
                    <th>Sisa</th>
                    <th>Harga</th>
                    <th>Total</th>
                    <th>Potongan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->food?->name ?? '-' }}</td>
                        <td>{{ $item->unit }}</td>
                        <td>{{ number_format($item->quantity) }}</td>
                        <td>{{ $item->leftover !== null ? number_format($item->leftover) : '-' }}</td>
                        <td>Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->total_item, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->cut_amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
